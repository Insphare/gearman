<?php

declare(ticks = 1);
error_reporting(E_ALL | E_STRICT);

abstract class Gearman_Manager_Abstract {

	/**
	 * Default config section name
	 */
	const DEFAULT_CONFIG = 'Gearman_Test';

	/**
	 * Defines job priority limits
	 */
	const MIN_PRIORITY = -5;
	const MAX_PRIORITY = 5;

	/**
	 * @var Gearman_Config
	 */
	private $config;

	/**
	 * Boolean value that determines if the running code is the parent or a child
	 */
	protected $isParent = true;

	/**
	 * When true, workers will stop look for jobs and the parent process will
	 * kill off all running children
	 */
	protected $stopWork = false;

	/**
	 * The timestamp when the signal was received to stop working
	 */
	protected $stopTime = 0;

	/**
	 * The array of running child processes
	 */
	protected $children = array();

	/**
	 * The array of jobs that have workers running
	 */
	protected $jobs = array();

	/**
	 * The PID of the running process. Set for parent and child processes
	 */
	protected $pid = 0;

	/**
	 * The PID of the parent process, when running in the forked helper.
	 */
	protected $parentPid = 0;

	/**
	 * PID file for the parent process
	 */
	protected $pidFile = '';

	/**
	 * PID of helper child
	 */
	protected $helperPid = 0;

	/**
	 * The user to run as.
	 *
	 * @var Gearman_Manager_User
	 */
	protected $user = null;

	/**
	 * If true, the worker code directory is checked for updates and workers
	 * are restarted automatically.
	 */
	protected $checkCode = false;

	/**
	 * Holds the last timestamp of when the code was checked for updates
	 */
	protected $lastCheckTime = 0;

	/**
	 * When forking helper children, the parent waits for a signal from them
	 * to continue doing anything
	 */
	protected $waitForSignal = false;

	/**
	 * Number of workers that do all jobs
	 */
	protected $doAllCount = 0;

	/**
	 * Maximum time a worker will run
	 */
	protected $maxRunTime = 3600;

	/**
	 * Maximum number of jobs this worker will do before quitting
	 */
	protected $maxJobCount = 0;

	/**
	 * Maximum job iterations per worker
	 */
	protected $maxRunsPerWorker = null;

	/**
	 * Number of times this worker has run a job
	 */
	protected $jobExecutionCount = 0;

	/**
	 * List of functions available for work
	 */
	protected $functions = array();

	/**
	 * @var Gearman_Manager_Log
	 */
	protected $logger;

	/**
	 *
	 */
	public function __construct() {
		$this->checkRequirements();
		$this->pid = getmypid();
		$this->bootstrap();
		$this->registerTicks();
		$this->loadWorkers();
		$this->begin();

		/**
		 * Main processing loop for the parent process
		 */
		while (!$this->stopWork || count($this->children)) {
			$this->processLoop();

			/**
			 * php will eat up your cpu if you don't have this
			 */
			usleep(50000);
		}

		/**
		 * Kill the helper if it is running
		 */
		if (isset($this->helperPid)) {
			posix_kill($this->helperPid, SIGKILL);
		}

		$this->logger->log('Exiting');
	}

	/**
	 *
	 */
	private function bootstrap() {
		$config = $this->getConfig();

		$user = $config->getUser();
		if (!empty($user)) {
			$this->user = new Gearman_Manager_User($user);
		}

		$this->checkCode = $config->getAutoUpdate();

		$maxWorkerLifeTime = $config->getMaxWorkerLifetime();
		if (!empty($maxWorkerLifeTime)) {
			$this->maxRunTime = $maxWorkerLifeTime;
		}

		$this->doAllCount = $config->getCount();

		/**
		 * If we want to daemonize, fork here and exit
		 */
		$daemonize = $config->getDaemonize();
		if (!empty($daemonize)) {
			$pid = pcntl_fork();
			if ($pid > 0) {
				$this->isParent = false;
				exit();
			}

			$this->pid = getmypid();
			posix_setsid();
		}

		$pidFile = $config->getPidFile();

		if (!empty($pidFile)) {
			$fp = fopen($pidFile, 'w');
			if ($fp) {
				fwrite($fp, $this->pid);
				fclose($fp);
			}
			else {
				$this->throwErrorAndDie('Unable to write PID to ' . $pidFile);
			}

			$this->pidFile = $pidFile;
		}

		$this->logger = new Gearman_Manager_Log($config, $this->user, $this->pid);

		if ($this->user instanceof Gearman_Manager_User) {
			/**
			 * Ensure new uid can read/write pid and log files
			 */
			if (!empty($this->pidFile)) {
				if (!chown($this->pidFile, $this->user->getUserId())) {
					$this->logger->log('Unable to chown PID file to ' . $this->user->getUsername(), Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
				}
			}

			posix_setuid($this->user->getUserId());
			if (posix_geteuid() != $this->user->getUserId()) {
				$this->throwErrorAndDie('Unable to change user to ' . $this->user->getUsername() . ' (UID: ' . $this->user->getUserId() . ').');
			}

			$this->logger->log('User set to ' . $this->user->getUsername() . '', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
		}
	}

	/**
	 *
	 */
	private function begin() {
		/**
		 * Validate workers in the helper process
		 */
		$this->forkMe('validateWorkers');
		$this->logger->log('Started with pid ' . $this->pid, Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);

		$functionCount = array();

		/**
		 * If we have 'do_all' workers, start them first
		 * do_all workers register all functions
		 */
		if (!empty($this->doAllCount) && is_int($this->doAllCount)) {

			for ($iterator = 0; $iterator < $this->doAllCount; $iterator++) {
				$this->startWorker();
			}

			foreach ($this->functions as $worker => $settings) {
				if (empty($settings['dedicated_only'])) {
					$functionCount[$worker] = $this->doAllCount;
				}
			}
		}

		/**
		 * Next we loop the workers and ensure we have enough running
		 * for each worker
		 */
		foreach ($this->functions as $worker => $config) {

			/**
			 * If we don't have do_all workers, this won't be set, so we need
			 * to init it here
			 */
			if (empty($functionCount[$worker])) {
				$functionCount[$worker] = 0;
			}

			while ($functionCount[$worker] < $config['count']) {
				$this->startWorker($worker);
				$functionCount[$worker]++;
			}

			/**
			 * php will eat up your cpu if you don't have this
			 */
			usleep(50000);
		}

		/**
		 * Set the last code check time to now since we just loaded all the code
		 */
		$this->lastCheckTime = time();
	}

	/**
	 * Forks the process and runs the given method. The parent then waits
	 * for the child process to signal back that it can continue
	 *
	 * @param   string $method  Class method to run after forking
	 *
	 */
	protected function forkMe($method) {
		$this->waitForSignal = true;
		$pid = pcntl_fork();
		switch ($pid) {
			case 0:
				$this->isParent = false;
				$this->parentPid = $this->pid;
				$this->pid = getmypid();
				$this->$method();
				break;
			case -1:
				$this->logger->log('Failed to fork');
				$this->stopWork = true;
				break;
			default:
				$this->helperPid = $pid;
				while ($this->waitForSignal && !$this->stopWork) {
					usleep(5000);
					pcntl_waitpid($pid, $status, WNOHANG);

					if (pcntl_wifexited($status) && $status) {
						$this->logger->log('Child exited with non-zero exit code ' . $status);
						exit(1);
					}
				}
				break;
		}
	}

	/**
	 * @return Gearman_Config
	 */
	protected function getConfig() {
		if (null === $this->config) {
			$this->config = new Gearman_Config();
		}

		return $this->config;
	}

	/**
	 *
	 */
	private function checkRequirements() {
		if (!function_exists('posix_kill')) {
			$this->throwErrorAndDie('The function posix_kill was not found. Please ensure POSIX functions are installed');
		}

		if (!function_exists('pcntl_fork')) {
			$this->throwErrorAndDie('The function pcntl_fork was not found. Please ensure Process Control functions are installed');
		}
	}

	/**
	 * Registers the process signal listeners
	 */
	protected function registerTicks($parent = true) {
		if ($parent) {
			$this->logger->log('Registering signals for parent', Gearman_Manager_Log::LOG_LEVEL_DEBUG);
			pcntl_signal(SIGTERM, array(
				$this,
				'signal'
			));
			pcntl_signal(SIGINT, array(
				$this,
				'signal'
			));
			pcntl_signal(SIGUSR1, array(
				$this,
				'signal'
			));
			pcntl_signal(SIGUSR2, array(
				$this,
				'signal'
			));
			pcntl_signal(SIGCONT, array(
				$this,
				'signal'
			));
			pcntl_signal(SIGHUP, array(
				$this,
				'signal'
			));
		}
		else {
			$this->logger->log('Registering signals for child', Gearman_Manager_Log::LOG_LEVEL_DEBUG);
			$res = pcntl_signal(SIGTERM, array(
				$this,
				'signal'
			));
			if (!$res) {
				exit();
			}
		}
	}

	/**
	 *
	 */
	protected function loadWorkers() {
		$functions = array();
		$config = Gearman_Worker_Config::getWorkerRawConfig();
		/** @var $autoLoader Autoloader */
		$autoLoader = Registry::get(Registry::AUTO_LOADER);

		foreach ($config as $key => $data) {
			$file = $autoLoader->getFilePath($data->class);
			$functions[strtolower($key)] = array(
				'count' => $data->workerCount,
				'path' => $file,
				'priority' => 0,
			);
		}

		$this->functions = $functions;
		$this->checkWorkersAreAvailable($this->pid);
	}

	/**
	 * @param string $worker
	 */
	protected function startWorker($worker = 'all') {
		static $allWorkers;

		if ($worker == 'all') {
			if (is_null($allWorkers)) {
				$allWorkers = array();
				foreach ($this->functions as $func => $settings) {
					if (empty($settings['dedicated_only'])) {
						$allWorkers[] = $func;
					}
				}
			}
			$workerList = $allWorkers;
		}
		else {
			$workerList = array($worker);
		}

		$pid = pcntl_fork();

		switch ($pid) {
			case 0:
				$this->isParent = false;
				$this->registerTicks(false);
				$this->pid = getmypid();

				if (count($workerList) > 1) {

					// shuffle the list to avoid queue preference
					shuffle($workerList);

					// sort the shuffled array by priority
					uasort($workerList, array(
						$this,
						'sortPriority'
					));
				}

				$this->startLibWorker($workerList);
				$this->logger->log('Child exiting', Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
				exit();
				break;

			case -1:
				$this->logger->log('Could not fork');
				$this->stopWork = true;
				$this->stopChildren();
				break;

			default:
				// parent
				$this->logger->log('Started child ' . $pid . ' (' . implode(',', $workerList) . ')', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
				$this->children[$pid] = $worker;
		}
	}

	/**
	 * Sorts the function list by priority
	 */
	public function sortPriority($a, $b) {
		$funcA = $this->functions[$a];
		$funcB = $this->functions[$b];

		if (!isset($funcA['priority'])) {
			$funcA['priority'] = 0;
		}
		if (!isset($funcB['priority'])) {
			$funcB['priority'] = 0;
		}
		if ($funcA['priority'] == $funcB['priority']) {
			return 0;
		}
		return ($funcA['priority'] > $funcB['priority']) ? -1 : 1;
	}

	/**
	 * Forked method that validates the worker code and checks it if desired
	 *
	 */
	protected function validateWorkers() {
		$this->logger->log('Helper forked', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
		$this->loadWorkers();
		$this->checkWorkersAreAvailable($this->parentPid);
		$this->validateLibWorkers();

		/**
		 * Since we got here, all must be ok, send a CONTINUE
		 */
		posix_kill($this->parentPid, SIGCONT);

		if ($this->checkCode) {
			$this->logger->log('Running loop to check for new code', Gearman_Manager_Log::LOG_LEVEL_DEBUG);
			$lastCheckTime = 0;
			while (1) {
				$maxTime = 0;
				foreach ($this->functions as $func) {
					clearstatcache();
					$mtime = filemtime($func['path']);
					$maxTime = max($maxTime, $mtime);
					$this->logger->log("{$func['path']} - $mtime $lastCheckTime", Gearman_Manager_Log::LOG_LEVEL_CRAZY);
					if ($lastCheckTime != 0 && $mtime > $lastCheckTime) {
						$this->logger->log('New code found. Sending SIGHUP', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
						posix_kill($this->parentPid, SIGHUP);
						break;
					}
				}
				$lastCheckTime = $maxTime;
				sleep(5);
			}
		}
		else {
			exit();
		}
	}

	private function checkWorkersAreAvailable($pid) {
		if (empty($this->functions)) {
			$this->logger->log('No workers found');
			posix_kill($pid, SIGUSR1);
			exit();
		}
	}

	/**
	 * Stops all running children
	 */
	protected function stopChildren($signal = SIGTERM) {
		$this->logger->log('Stopping children', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);

		foreach ($this->children as $pid => $worker) {
			$this->logger->log('Stopping child ' . $pid . ' (' . $worker . ')', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
			posix_kill($pid, $signal);
		}
	}

	/**
	 * Handles signals
	 */
	public function signal($sigNo) {
		static $termCount = 0;

		if (!$this->isParent) {
			$this->stopWork = true;
		}
		else {
			switch ($sigNo) {
				case SIGUSR1:
					$this->throwErrorAndDie('No worker files could be found');
					break;
				case SIGUSR2:
					$this->throwErrorAndDie('Error validating worker functions');
					break;
				case SIGCONT:
					$this->waitForSignal = false;
					break;
				case SIGINT:
				case SIGTERM:
					$this->logger->log('Shutting down...');
					$this->stopWork = true;
					$this->stopTime = time();
					$termCount++;
					if ($termCount < 5) {
						$this->stopChildren();
					}
					else {
						$this->stopChildren(SIGKILL);
					}
					break;
				case SIGHUP:
					$this->logger->log('Restarting children', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
					$this->logger->refreshLogFile();
					$this->stopChildren();
					break;
				default:
					// handle all other signals
			}
		}
	}

	/**
	 *
	 */
	protected function processLoop() {
		$status = null;

		/**
		 * Check for exited children
		 */
		$exited = pcntl_wait($status, WNOHANG);

		/**
		 * We run other children, make sure this is a worker
		 */
		if (isset($this->children[$exited])) {
			/**
			 * If they have exited, remove them from the children array
			 * If we are not stopping work, start another in its place
			 */
			if ($exited) {
				$worker = $this->children[$exited];
				unset($this->children[$exited]);
				$this->logger->log('Child ' . $exited . ' exited (' . $worker . ')', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
				if (!$this->stopWork) {
					$this->startWorker($worker);
				}
			}
		}

		if ($this->stopWork && time() - $this->stopTime > 60) {
			$this->logger->log('Children have not exited, killing.', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
			$this->stopChildren(SIGKILL);
		}
	}

	/**
	 * @param $message
	 */
	private function throwErrorAndDie($message) {
		echo PHP_EOL;
		echo $message;
		echo PHP_EOL;
		echo PHP_EOL;
		exit;
	}

	/**
	 * Handles anything we need to do when we are shutting down
	 */
	public function __destruct() {
		if ($this->isParent) {
			if (!empty($this->pidFile) && file_exists($this->pidFile)) {
				if (!unlink($this->pidFile)) {
					$this->logger->log('Could not delete PID file', Gearman_Manager_Log::LOG_LEVEL_PROC_INFO);
				}
			}
		}
	}
}
