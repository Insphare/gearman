<?php

/**
 * Contains all information about the gearman job.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Job {

	/**
	 * Job is running in background mode.
	 */
	const TYPE_DO_BACKGROUND = 1;

	/**
	 * Job is a partial task.
	 */
	const TYPE_TASK = 2;

	/**
	 * @var GearmanJob|Gearman_Job_Imitation
	 */
	private $gearmanJob;

	/**
	 * @var mixed
	 */
	private $log;

	/**
	 * @param Gearman_Job
	 */
	public function __construct($job) {
		$this->setGearmanJob($job);
	}

	/**
	 * @param GearmanJob $gearmanJob
	 */
	public function setGearmanJob($gearmanJob) {
		$this->gearmanJob = $gearmanJob;
	}

	/**
	 * @param $log
	 */
	public function setLog(&$log) {
		$this->log = $log;
	}

	/**
	 * Unserialize a workload and returns.
	 *
	 * @return array|mixed
	 */
	public function getParams() {
		return unserialize($this->getWorkload());
	}

	/**
	 * Returns the workload as string.
	 *
	 * @return string
	 */
	public function getWorkload() {
		return $this->gearmanJob->workload();
	}

	/**
	 * Returns the worker name.
	 *
	 * @return string
	 */
	public function getFunctionName() {
		return $this->gearmanJob->functionName();
	}

	/**
	 * Returns the handle identifier.
	 *
	 * @return string
	 */
	public function getHandle() {
		return $this->gearmanJob->handle();
	}

	/**
	 * Returns the unique identifier.
	 *
	 * @return string
	 */
	public function getUnique() {
		return $this->gearmanJob->unique();
	}

	/**
	 * Returns the length of workload string.
	 *
	 * @return int
	 */
	public function getWorkloadSize() {
		return $this->gearmanJob->workloadSize();
	}

	/**
	 * @param $currentStep
	 * @param $stepTotal
	 */
	public function sendStatus($currentStep, $stepTotal) {
		$this->gearmanJob->sendStatus($currentStep, $stepTotal);
	}

	/**
	 * @param $data
	 */
	public function sendStatusData($data) {
		$this->gearmanJob->sendData($data);
	}

	/**
	 * @param $message
	 * @throws Gearman_Worker_Exception
	 */
	public function sendException($message) {
		$this->gearmanJob->sendException($message);
		$this->gearmanJob->sendFail();
		throw new Gearman_Worker_Exception($message);
	}

	/**
	 * @param $result
	 */
	public function setResult($result) {
		$this->gearmanJob->sendComplete(serialize($result));
	}

	/**
	 * Returns the type of this worker job.
	 *
	 * @return int
	 */
	public function getType() {
		$type = self::TYPE_DO_BACKGROUND;

		$args = $this->getParams();
		if (isset($args[Gearman_Deliver::TASK_INDEX])) {
			$type = self::TYPE_TASK;
		}

		return $type;
	}

	/**
	 * Checks whether the job is a task.
	 *
	 * @return bool
	 */
	public function isTypeTask() {
		return $this->getType() === self::TYPE_TASK;
	}

	/**
	 * Returns the type of job as key name.
	 *
	 * @return string
	 */
	public function getTypeName() {
		switch ($this->getType()) {
			case self::TYPE_DO_BACKGROUND:
				return 'Background';

			case self::TYPE_TASK:
				return 'Task';

			default:
				return '[Unkown] ' . $this->getType();
		}
	}

	/**
	 * Returns the hash of this job. Consisting of the function name, workload string and the type.
	 *
	 * @return string
	 */
	public function getJobHashString() {
		return md5($this->getFunctionName() . $this->getWorkload() . $this->getType());
	}
}