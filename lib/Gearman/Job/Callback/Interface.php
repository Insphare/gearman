<?php

/**
 * Interface for own callback classes.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
interface Gearman_Job_Callback_Interface {

	/**
	 * Call on every new gearman job they begins now.
	 *
	 * @param Gearman_Job $job
	 * @return mixed
	 */
	public function created(Gearman_Job $job);

	/**
	 * Call when a gearman job is finish.
	 *
	 * @param Gearman_Job $job
	 * @param mixed $result
	 * @return mixed
	 */
	public function complete(Gearman_Job $job, $result);

	/**
	 * Call when a gearman job fails.
	 *
	 * @param Gearman_Job $job
	 * @param string $errorMessage
	 * @return mixed
	 */
	public function fail(Gearman_Job $job, $errorMessage);

	/**
	 * Not implemented until further notice.
	 * It is initially a meaningful usage needed and a solution for that.
	 *
	 * @param Gearman_Job $job
	 * @param $currentStep
	 * @param $maxSteps
	 * @return mixed
	 */
	public function status(Gearman_Job $job, $currentStep, $maxSteps);
}