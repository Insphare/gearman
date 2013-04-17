<?php

/**
 * Contains the config of workers with getter and setter methods.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Worker_Config {

	/**
	 * Contains the current config class.
	 *
	 * @var Gearman_Worker_Config[]
	 */
	private static $instance = array();

	/**
	 * Contains the config.
	 *
	 * @var array
	 */
	private static $config = array();

	/**
	 * Contains the information about the synchronicity.
	 *
	 * @var bool
	 */
	private $synchronous = false;

	/**
	 * Contains the name from the real class.
	 *
	 * @var string
	 */
	private $class;

	/**
	 * Contains the name from the method from the real class.
	 *
	 * @var string
	 */
	private $method;

	/**
	 * Contains the amount of workers.
	 *
	 * @var int
	 */
	private $workerCount;

	/**
	 * Contains the index key from this worker from the config.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Contains the information whether the call is static.
	 *
	 * @var bool
	 */
	private $isStatic = false;

	/**
	 * Contains the information how the priority is.
	 *
	 * @var int
	 */
	private $priority = Gearman_Connector_Client::PRIORITY_NORMAL;

	/**
	 * Factory to get a config class by config key.
	 *
	 * @param $key
	 *
	 * @return Gearman_Worker_Config
	 *
	 * @throws Gearman_Exception
	 */
	public static function getConfigByConfigKey($key) {
		if (!isset(self::$instance[$key])) {
			$config = self::getWorkerRawConfig();
			$key = strtolower($key);
			if (!isset($config[$key])) {
				throw new Gearman_Exception('This gearman config key "' . $key . '" does not exists.');
			}

			$workerConfig = $config[$key];
			$configClass = new Gearman_Worker_Config($workerConfig);
			$configClass->setKey($key);
			self::$instance[$key] = $configClass;
		}

		return self::$instance[$key];
	}

	/**
	 * Loads the config once and return the configuration.
	 *
	 * @return array|object
	 */
	public static function getWorkerRawConfig() {
		if (empty(self::$config)) {
			$config = new Config_Gearman();
			$tmpConfig = array();
			$gearmanConfig = $config->get('gearman.workers');

			foreach ($gearmanConfig as $key => $data) {
				$key = strtolower($key);
				$tmpConfig[$key] = (object)$data;
			}

			self::$config = $tmpConfig;
		}

		return self::$config;
	}

	/**
	 * Set all config values by given config stdClass.
	 *
	 * @param stdClass $config
	 */
	public function __construct(stdClass $config) {
		if (isset($config->synchronous)) {
			$this->setSynchronous((bool)$config->synchronous);
		}

		if (isset($config->class)) {
			$this->setClass((string)$config->class);
		}

		if (isset($config->method)) {
			$this->setMethod((string)$config->method);
		}

		if (isset($config->workerCount)) {
			$this->setWorkerCount((int)$config->workerCount);
		}

		if (isset($config->static)) {
			$this->setIsStatic((bool)$config->static);
		}

		if (isset($config->priority)) {
			$this->setPriority((int)$config->priority);
		}
	}

	/**
	 * @param string $class
	 */
	private function setClass($class) {
		$this->class = $class;
	}

	/**
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @param string $method
	 */
	private function setMethod($method) {
		$this->method = $method;
	}

	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @param boolean $synchronous
	 */
	private function setSynchronous($synchronous) {
		$this->synchronous = $synchronous;
	}

	/**
	 * @return boolean
	 */
	public function getSynchronous() {
		return $this->synchronous;
	}

	/**
	 * @param int $workerCount
	 */
	private function setWorkerCount($workerCount) {
		$this->workerCount = $workerCount;
	}

	/**
	 * @return int
	 */
	public function getWorkerCount() {
		return $this->workerCount;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @param boolean $isStatic
	 */
	public function setIsStatic($isStatic) {
		$this->isStatic = $isStatic;
	}

	/**
	 * @return boolean
	 */
	public function getIsStatic() {
		return $this->isStatic;
	}

	/**
	 * @param int $priority
	 */
	public function setPriority($priority) {
		$this->priority = $priority;
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}
}
