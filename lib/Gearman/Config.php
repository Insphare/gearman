<?php

/**
 * Contains the configuration from gearman with getter and setter methods.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Config {

	/**
	 * Contains the config.
	 *
	 * @var array
	 */
	private static $config = array();

	/**
	 * Contains the information whether gearman really is to be used.
	 *
	 * @var bool
	 */
	private $runAsGearman;

	/**
	 * Contains the name from the own callback adapter class.
	 *
	 * @var string
	 */
	private $callback;

	/**
	 * Contains all servers they are given.
	 *
	 * @var array
	 */
	private $servers = array();

	/**
	 * @var string
	 */
	private $pidFile;

	/**
	 * @var string
	 */
	private $logFile;

	/**
	 * @var bool
	 */
	private $autoUpdate;

	/**
	 * @var int
	 */
	private $maxWorkerLifetime;

	/**
	 * @var int
	 */
	private $maxRunsPerWorker;

	/**
	 * @var int
	 */
	private $count;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var bool
	 */
	private $daemonize;

	/**
	 * @var int
	 */
	private $verbose;

	/**
	 * Loads the config, sets the configuration in our member variable for the getter methods.
	 */
	public function __construct() {
		$config = self::getGearmanRawConfig();

		$this->setCallback($config->callback);
		$this->setRunAsGearman($config->runAsGearman);

		if (empty($config->servers)) {
			$servers = array(
				Gearman_Connector_ServerWorker::LOCALHOST => true,
			);
		}
		else {
			$servers = $config->servers;
		}

		$this->setServers($servers);
		$this->setAutoUpdate((bool)$config->autoUpdate);
		$this->setCount((int)$config->count);
		$this->setDaemonize((bool)$config->daemonize);
		$this->setMaxRunsPerWorker((int)$config->maxRunsPerWorker);
		$this->setMaxWorkerLifetime((int)$config->maxWorkerLifetime);
		$this->setLogFile((string)$config->logFile);
		$this->setPidFile((string)$config->pidFile);
		$this->setUser((string)$config->user);
		$this->setVerbose((string)$config->verbose);
	}

	/**
	 * @return array|object
	 */
	private static function getGearmanRawConfig() {
		if (empty(self::$config)) {
			$config = new Config_Gearman();
			$gearmanConfig = $config->get('gearman.core');

			self::$config = (object)$gearmanConfig;
		}

		return self::$config;
	}

	/**
	 * @param string $callback
	 */
	private function setCallback($callback) {
		$this->callback = $callback;
	}

	/**
	 * @return string
	 */
	public function getCallbackClassName() {
		return $this->callback;
	}

	/**
	 * @param boolean $runAsGearman
	 */
	private function setRunAsGearman($runAsGearman) {
		$this->runAsGearman = $runAsGearman;
	}

	/**
	 * @return boolean
	 */
	public function getRunAsGearman() {
		return $this->runAsGearman;
	}

	/**
	 * @param array $servers
	 */
	private function setServers($servers) {
		$this->servers = $servers;
	}

	/**
	 * @return array
	 */
	public function getServers() {
		return $this->servers;
	}

	/**
	 * @return int
	 */
	public function getVerbose() {
		return $this->verbose;
	}

	/**
	 * @return string
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getPidFile() {
		return $this->pidFile;
	}

	/**
	 * @return int
	 */
	public function getMaxRunsPerWorker() {
		return $this->maxRunsPerWorker;
	}

	/**
	 * @return int
	 */
	public function getMaxWorkerLifetime() {
		return $this->maxWorkerLifetime;
	}

	/**
	 * @return string
	 */
	public function getLogFile() {
		return $this->logFile;
	}

	/**
	 * @return boolean
	 */
	public function getDaemonize() {
		return $this->daemonize;
	}

	/**
	 * @return int
	 */
	public function getCount() {
		return $this->count;
	}

	/**
	 * @return boolean
	 */
	public function getAutoUpdate() {
		return $this->autoUpdate;
	}

	/**
	 * @param boolean $autoUpdate
	 */
	private function setAutoUpdate($autoUpdate) {
		$this->autoUpdate = $autoUpdate;
	}

	/**
	 * @param int $count
	 */
	private function setCount($count) {
		$this->count = $count;
	}

	/**
	 * @param boolean $daemonize
	 */
	private function setDaemonize($daemonize) {
		$this->daemonize = $daemonize;
	}

	/**
	 * @param string $logFile
	 */
	private function setLogFile($logFile) {
		$this->logFile = $logFile;
	}

	/**
	 * @param int $maxRunsPerWorker
	 */
	private function setMaxRunsPerWorker($maxRunsPerWorker) {
		$this->maxRunsPerWorker = $maxRunsPerWorker;
	}

	/**
	 * @param int $maxWorkerLifetime
	 */
	private function setMaxWorkerLifetime($maxWorkerLifetime) {
		$this->maxWorkerLifetime = $maxWorkerLifetime;
	}

	/**
	 * @param string $pidFile
	 */
	private function setPidFile($pidFile) {
		$this->pidFile = $pidFile;
	}

	/**
	 * @param string $user
	 */
	private function setUser($user) {
		$this->user = $user;
	}

	/**
	 * @param int $verbose
	 */
	private function setVerbose($verbose) {
		$this->verbose = $verbose;
	}
}
