<?php

/**
 * This is only an example. You should copy this class in your application and
 * do your wanted stuff to do as handle.
 *
 * Hint: If you have a different location for your callback handler
 * you have to change the call for the callback-class in your config.
 */

class Model_Gearman_Callback implements Gearman_Job_Callback_Interface {

	/**
	 * Construct.
	 */
	public function __construct() {
		// do your stuff here, log into your db etc.
	}

	/**
	 * @param Gearman_Job $job
	 * @return mixed|void
	 */
	public function created(Gearman_Job $job) {
		// do your stuff here, log into your db etc.
	}

	/**
	 * @param Gearman_Job $job
	 * @param mixed $result
	 * @return mixed|void
	 */
	public function complete(Gearman_Job $job, $result) {
		// do your stuff here, log into your db etc.
	}

	/**
	 * @param Gearman_Job $job
	 * @param string $errorMessage
	 * @return mixed|void
	 */
	public function fail(Gearman_Job $job, $errorMessage) {
		// do your stuff here, log into your db etc.
	}

	/**
	 * @param Gearman_Job $job
	 * @param $currentStep
	 * @param $maxSteps
	 * @return mixed|void
	 */
	public function status(Gearman_Job $job, $currentStep, $maxSteps) {
		// do your stuff here, log into your db etc.
	}

}
