<?php

/**
 * Delegate design pattern abstract class for gearman instances.
 * For example client, worker, background.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
abstract class Gearman_Connector_Abstract {

	const LOCALHOST = '127.0.0.1';

	/**
	 * @var GearmanClient||GearmanServer||Gearman_Connector_Background_Shell
	 */
	protected $delegate;

	/**
	 * @var array
	 */
	protected $serverList = array();

	/**
	 * Error when called a method which is not implemented in their class.
	 */
	const ERROR_NOT_IMPLEMENTED_METHOD = 1;

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		if (empty($config)) {
			$gearmanConfig = new Gearman_Config();
			$config = $gearmanConfig->getServers();
		}

		foreach ($config as $server => $active) {
			if ($active) {
				if (empty($server)) {
					$server = self::LOCALHOST;
				}
				$this->serverList[$server] = $server;
			}
		}

		/**
		 * Gearman's round robin is somewhat obscure
		 * It seems that there is no equal or random round robin
		 * so we help a little bit to get a better distribution across multiple servers
		 */
		shuffle($this->serverList);
		foreach ($this->serverList as $server) {
			$port = GEARMAN_DEFAULT_TCP_PORT;

			if (strpos($server, ':') !== false) {
				list($server, $port) = explode(':', $server);
			}

			$this->delegate->addServer($server, $port);
		}

		$this->delegate->setTimeout(5000);
	}

	/**
	 * @return int
	 */
	protected function getCountServers() {
		return count($this->serverList);
	}

	/**
	 * @param $methodName
	 * @param $args
	 * @throws Gearman_Connector_Exception
	 */
	public function __call($methodName, $args) {
		$message = 'You use a method they called: "%s" in the class "%s". This method is not implemented!';
		$message = sprintf($message, $methodName, get_class($this));
		throw new Gearman_Connector_Exception($message, self::ERROR_NOT_IMPLEMENTED_METHOD);
	}

	/**
	 * Generates a unique gearman worker name in the current environment and the current user.
	 * The name consists of the worker name, current user and development mode.
	 *
	 * The only exception is in the productive mode. Therein will not use the name of the current user.
	 *
	 * @param $workerName
	 * @return string
	 */
	protected function getWorkerMethodName($workerName) {
		$name = array();
		$config = new Gearman_Config();
		$usageName = $config->getUser();
		$usageName = trim($usageName);
		$usageName = preg_replace('~\s~', '', $usageName);
		$isDev = Registry::get(Registry::IS_DEVELOPMENT_MODE);

		if (false === $isDev) {
			$usageName = 'live';
		}

		$name[] = strtolower($workerName);
		$name[] = $isDev ? 'development' : 'productive';
		$name[] = $usageName;
		$name[] = 'gearman_worker';

		$result = implode('_', $name);
		return $result;
	}
}
