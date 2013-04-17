<?php

/**
 * Calls the callback methods from a own callback adapter.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Job_Callback {

	/**
	 * @var Gearman_Job_Callback_Interface
	 */
	private $callbackAdapter;

	/**
	 * Constructor. Loads the callback-class name from config and instantiated the callback class.
	 */
	public function __construct() {
		$config = new Gearman_Config();
		$model = $config->getCallbackClassName();
		$this->callbackAdapter = new $model();
	}

	/**
	 * Call on every new gearman job they begins now.
	 *
	 * @param Gearman_Job $job
	 */
	public function created(Gearman_Job $job) {
		$this->callbackAdapter->created($job);
	}

	/**
	 * Call when a gearman job is finish.
	 *
	 * @param Gearman_Job $job
	 * @param $result
	 */
	public function complete(Gearman_Job $job, $result) {
		$this->callbackAdapter->complete($job, $result);
	}

	/**
	 * Call when a gearman job fails.
	 *
	 * @param Gearman_Job $job
	 * @param $errorMessage
	 */
	public function fail(Gearman_Job $job, $errorMessage) {
		$this->callbackAdapter->fail($job, $errorMessage);
	}

	/**
	 * Not implemented until further notice.
	 * It is initially a meaningful usage needed and a solution for that.
	 *
	 * @param Gearman_Job $job
	 * @param $currentStep
	 * @param $maxSteps
	 */
	public function status(Gearman_Job $job, $currentStep, $maxSteps) {
		$this->callbackAdapter->status($job, $currentStep, $maxSteps);
	}
}
