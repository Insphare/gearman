<?php

class Config_Gearman extends Config {

	public function __construct() {
		$pidFile = Registry::get(Registry::BASE_PATH) . 'pid' . DS . 'gearman.pid';

		$core = array(
			'runAsGearman' => true,
			'callback' => 'Model_Gearman_Callback',
			'servers' => array(
				Gearman_Connector_ServerWorker::LOCALHOST => true,
			),
			'pidFile' => $pidFile,
			'logFile' => '',
			'autoUpdate' => true,
			'maxWorkerLifetime' => '',
			'maxRunsPerWorker' => 3,
			'count' => 3,
			'user' => 'manuel',
			'daemonize' => true,
			'verbose' => 'vvvv',
		);

		/**
		 * Register your workers here.
		 */
		$workers = array(
			'GearmanTest' => array(
				'synchronous' => true,
				'class' => 'Gearman_Test',
				'method' => 'testRun',
				'workerCount' => 1,
				'static' => false,
				'priority' => Gearman_Connector_Client::PRIORITY_NORMAL,
			),
		);

		$this->set('gearman.core', $core);
		$this->set('gearman.workers', $workers);
	}
}
