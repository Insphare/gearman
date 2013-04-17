<?php

/**
 * This class manages the server side gearman workers.
 *
 * @author Manuel Will <insphare@gmail.com>
 */

class Gearman_Connector_ServerWorker extends Gearman_Connector_Abstract {

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->delegate = new GearmanWorker();

		parent::__construct($config);
	}

	/**
	 * @param string $workerName
	 * @param $callback
	 */
	public function addFunction($workerName, $callback) {
		$context = null;
		$timeout = 0;
		$workerMethodName = $this->getWorkerMethodName($workerName);

		$this->delegate->addFunction($workerMethodName, $callback, $context, $timeout);
	}

	/**
	 * @return bool
	 */
	public function work() {
		return @$this->delegate->work();
	}

	/**
	 * @return int
	 */
	public function returnCode() {
		return $this->delegate->returnCode();
	}

	/**
	 * @return bool
	 */
	public function wait() {
		return $this->delegate->wait();
	}
}