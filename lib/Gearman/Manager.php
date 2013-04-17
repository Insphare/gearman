<?php

class Gearman_Manager extends Gearman_Manager_Abstract {

	/**
	 * Servers that workers connect to
	 */
	private $servers = array();

	/**
	 * @var
	 */
	private $startTime;

	/**
	 * Verbosity level for the running script. Set via -v option
	 */
	protected $verbose = 0;

	/**
	 * @param array $workerList
	 */
	protected function startLibWorker(array $workerList) {
		$this->servers = array();
		$worker = new Gearman_Connector_ServerWorker($this->servers);

		foreach ($workerList as $workerName) {
			$this->logger->log("Adding job {$workerName}", Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
			$worker->addFunction($workerName, array(
				$this,
				'doJob'
			));
		}

		$this->startTime = time();
		$start = time();
		while (!$this->stopWork) {
			//if( @$worker->work() ||
			if ($worker->work() || $worker->returnCode() == GEARMAN_IO_WAIT || $worker->returnCode() == GEARMAN_NO_JOBS
			) {
				if ($worker->returnCode() == GEARMAN_SUCCESS) {
					continue;
				}

				if (!$worker->wait()) {
					if ($worker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
						sleep(5);
					}
				}
			}

			/**
			 * Check the running time of the current child. If it has
			 * been too long, stop working.
			 */
			if ($this->maxRunTime > 0 && time() - $start > $this->maxRunTime) {
				$this->logger->log('Been running too long, exiting', Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
				$this->stopWork = true;
			}

			$config = $this->getConfig();
			$maxRunsPerWorker = $config->getMaxRunsPerWorker();
			if (!empty($maxRunsPerWorker) && $this->jobExecutionCount >= $maxRunsPerWorker) {
				$code = Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO;
				$this->logger->log("Ran $this->jobExecutionCount jobs which is over the maximum({$maxRunsPerWorker}), exiting", $code);
				$this->stopWork = true;
			}
		}
	}

	/**
	 * @param GearmanJob $job
	 * @return null
	 */
	public function doJob(GearmanJob $job) {
		$result = null;
		static $objects;

		if ($objects === null) {
			$objects = array();
		}

		$workLoad = $job->workload();
		$handle = $job->handle();
		$jobName = $job->functionName();

		$func = $jobName;

		$reMapNaming = explode('_', $jobName);
		$reMapNaming = $reMapNaming[0];
		$reMapNaming = ucfirst($reMapNaming);

		$config = Gearman_Worker_Config::getConfigByConfigKey($reMapNaming);

		$success = false;
		try {
			/**
			 * @var $autoLoader Autoloader
			 */
			$autoLoader = Registry::get(Registry::AUTO_LOADER);
			$autoLoader->classExists($func);
			$success = true;
		}
		catch (Exception $e) {
			// silent
		}

		if (empty($objects[$jobName]) && $success) {
			$keys = array_keys($this->functions);

			$jobNameFound = false;
			foreach ($keys as $keyName) {
				if (ucfirst(strtolower($keyName)) == $reMapNaming) {
					$jobNameFound = true;
					break;
				}
			}

			if (false === $jobNameFound) {
				$this->logger->log("Function $reMapNaming is not a registered job name");
				return null;
			}

			error_reporting(E_ALL);

			$configKey = $config->getKey();
			$this->logger->log("Registry a $func object", Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
			$objects[$jobName] = $configKey;
		}

		$this->logger->log("($handle) Starting Job: $jobName", Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
		$this->logger->log("($handle) Workload: $workLoad", Gearman_Manager_Log::LOG_LEVEL_DEBUG);
		$log = array();

		/**
		 * Run the real function here
		 */
		if (isset($objects[$jobName])) {
			$this->logger->log("($handle) Calling object for $jobName.", Gearman_Manager_Log::LOG_LEVEL_DEBUG);
			Gearman_Worker_Process::process($config, new Gearman_Job($job), $log);
		}
		else {
			$this->logger->log("($handle) FAILED to find a function or class for $jobName.", Gearman_Manager_Log::LOG_LEVEL_INFO);
		}

		if (!empty($log)) {
			foreach ($log as $logValue) {
				if (!is_scalar($logValue)) {
					$logValue = explode(PHP_EOL, trim(print_r($logValue, true)));
				}
				elseif (strlen($logValue) > 256) {
					$logValue = substr($logValue, 0, 256) . "...(truncated)";
				}

				if (is_array($logValue)) {
					foreach ($logValue as $ln) {
						$this->logger->log("($handle) $ln", Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
					}
				}
				else {
					$this->logger->log("($handle) $logValue", Gearman_Manager_Log::LOG_LEVEL_WORKER_INFO);
				}
			}
		}

		$resultLog = $result;

		if (!is_scalar($resultLog)) {
			$resultLog = explode(PHP_EOL, trim(print_r($resultLog, true)));
		}
		elseif (strlen($resultLog) > 256) {
			$resultLog = substr($resultLog, 0, 256) . "...(truncated)";
		}

		if (is_array($resultLog)) {
			foreach ($resultLog as $ln) {
				$this->logger->log("($handle) $ln", Gearman_Manager_Log::LOG_LEVEL_DEBUG);
			}
		}
		else {
			$this->logger->log("($handle) $resultLog", Gearman_Manager_Log::LOG_LEVEL_DEBUG);
		}

		/**
		 * Workaround for PECL bug #17114
		 * http://pecl.php.net/bugs/bug.php?id=17114
		 */
		$type = gettype($result);
		settype($result, $type);

		$this->jobExecutionCount++;

		return $result;
	}

	/**
	 * Validates the compatible worker files/functions
	 */
	protected function validateLibWorkers() {
		/**
		 * Validate functions
		 */
		foreach ($this->functions as $index => $func) {
			if (true !== Gearman_Validator::check($index)) {
				$this->logger->log("Class $index not found in {$func['path']} or run method not present");
				posix_kill($this->pid, SIGUSR2);
				exit();
			}
		}
	}
}
