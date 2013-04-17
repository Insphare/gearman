<?php

class Gearman_Manager_Log {

	/**
	 * Log levels can be enabled with -v, -vv, -vvv
	 */
	const LOG_LEVEL_INFO = 1;
	const LOG_LEVEL_PROC_INFO = 2;
	const LOG_LEVEL_WORKER_INFO = 3;
	const LOG_LEVEL_DEBUG = 4;
	const LOG_LEVEL_CRAZY = 5;

	/**
	 * The filename to log to
	 */
	protected $logFile;

	/**
	 * Holds the resource for the log file
	 */
	protected $logFileHandle;

	/**
	 * Flag for logging to syslog
	 */
	protected $logSysLog = false;

	/**
	 * PID
	 *
	 * @var
	 */
	private $pid;



	/**
	 * @param Gearman_Config       $config
	 * @param Gearman_Manager_User $user
	 * @param                      $pid
	 */
	public function __construct(Gearman_Config $config, Gearman_Manager_User $user, $pid) {
		$this->logFile = $config->getLogFile();
		$this->pid = $pid;
		$verbose = $config->getVerbose();

		switch ($verbose) {
			case false:
				$this->verbose = self::LOG_LEVEL_INFO;
				break;
			case 'v':
				$this->verbose = self::LOG_LEVEL_PROC_INFO;
				break;
			case 'vv':
				$this->verbose = self::LOG_LEVEL_WORKER_INFO;
				break;
			case 'vvv':
				$this->verbose = self::LOG_LEVEL_DEBUG;
				break;
			case 'vvvv':
			default:
				$this->verbose = self::LOG_LEVEL_CRAZY;
				break;
		}

		if ($this->logFile) {
			$this->openLogFile($this->logFile);
		}

		if (!empty($this->logFileHandle)) {
			if (!chown($this->logFile, $user->getUserId())) {
				$this->log('Unable to chown log file to ' . $user->getUsername(), self::LOG_LEVEL_PROC_INFO);
			}
		}
	}

	/**
	 *
	 */
	public function refreshLogFile() {
		if ($this->logFile) {
			$this->openLogFile($this->logFile);
		}
	}

	/**
	 * Opens the logfile.  Will assign to $this->log_file_handle
	 *
	 * @param   string $file     The config filename.
	 *
	 */
	protected function openLogFile($file) {
		if ($this->logFileHandle) {
			fclose($this->logFileHandle);
		}
		$this->logFileHandle = fopen($file, 'a');
		if (!$this->logFileHandle) {
			die('Could not open log file ' . $file);
		}
	}

	/**
	 * Logs data to disk or stdout
	 */
	public function log($message, $level = self::LOG_LEVEL_INFO) {
		static $init = false;

		if ($level > $this->verbose) {
			return;
		}

		if ($this->logSysLog) {
			$this->sysLog($message, $level);
			return;
		}

		list($ts, $ms) = explode('.', sprintf('%f', microtime(true)));
		$ds = date('Y-m-d H:i:s') . '.' . str_pad($ms, 6, 0);

		if (!$init) {
			$init = true;

			if ($this->logFileHandle) {
				fwrite($this->logFileHandle, 'Date                         PID   Type   Message' . PHP_EOL);
			}
			else {
				echo 'PID   Type   Message' . PHP_EOL;
			}
		}

		$label = '';

		switch ($level) {
			case self::LOG_LEVEL_INFO;
				$label = 'INFO  ';
				break;
			case self::LOG_LEVEL_PROC_INFO:
				$label = 'PROC  ';
				break;
			case self::LOG_LEVEL_WORKER_INFO:
				$label = 'WORKER';
				break;
			case self::LOG_LEVEL_DEBUG:
				$label = 'DEBUG ';
				break;
			case self::LOG_LEVEL_CRAZY:
				$label = 'CRAZY ';
				break;
		}

		$logPid = str_pad($this->pid, 5, ' ', STR_PAD_LEFT);

		if ($this->logFileHandle) {
			$prefix = "[$ds] $logPid $label";
			fwrite($this->logFileHandle, $prefix . ' ' . str_replace(PHP_EOL, "\n$prefix ", trim($message)) . PHP_EOL);
		}
		else {
			$prefix = "$logPid $label";
			echo $prefix . ' ' . str_replace(PHP_EOL, "\n$prefix ", trim($message)) . PHP_EOL;
		}
	}

	/**
	 * Logs data to syslog
	 */
	protected function sysLog($message, $level) {
		switch ($level) {
			case self::LOG_LEVEL_INFO;
			case self::LOG_LEVEL_PROC_INFO:
			case self::LOG_LEVEL_WORKER_INFO:
			default:
				$priority = LOG_INFO;
				break;
			case self::LOG_LEVEL_DEBUG:
				$priority = LOG_DEBUG;
				break;
		}

		if (!syslog($priority, $message)) {
			echo 'Unable to write to syslog' . PHP_EOL;
		}
	}
}
