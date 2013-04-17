<?php

/**
 * Imitates a GearmanJob class.
 * This is necessary when the application evades of a shell background execution.
 * This class is required for Gearman_Job when the bin script gearman_background is used.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Job_Imitation {

	/**
	 * @var string
	 */
	private $handle;

	/**
	 * @var string
	 */
	private $unique;

	/**
	 * @var string
	 */
	private $functionName;

	/**
	 * @var string
	 */
	private $workload;

	/**
	 * Returns the opaque job handle assigned by the job server.
	 *
	 * @link http://php.net/manual/en/gearmanjob.handle.php
	 * @return string An opaque job handle
	 */
	public function handle() {
		return $this->handle;
	}

	/**
	 * Returns the function name for this job. This is the function the work will
	 * execute to perform the job.
	 *
	 * @link http://php.net/manual/en/gearmanjob.functionname.php
	 * @return string The name of a function
	 */
	public function functionName() {
		return $this->functionName;
	}

	/**
	 * Returns the unique identifier for this job. The identifier is assigned by the
	 * client.
	 *
	 * @link http://php.net/manual/en/gearmanjob.unique.php
	 * @return string An opaque unique identifier
	 */
	public function unique() {
		return $this->unique;
	}

	/**
	 * Returns the workload for the job. This is serialized data that is to be
	 * processed by the worker.
	 *
	 * @link http://php.net/manual/en/gearmanjob.workload.php
	 * @return string Serialized data
	 */
	public function workload() {
		return $this->workload;
	}

	/**
	 * Returns the size of the job's work load (the data the worker is to process) in
	 * bytes.
	 *
	 * @link http://php.net/manual/en/gearmanjob.workloadsize.php
	 * @return int The size in bytes
	 */
	public function workloadSize() {
		return strlen($this->workload);
	}

	/**
	 * @param string $functionName
	 */
	public function setFunctionName($functionName) {
		$this->functionName = $functionName;
	}

	/**
	 * @param string $handle
	 */
	public function setHandle($handle) {
		$this->handle = $handle;
	}

	/**
	 * @param string $unique
	 */
	public function setUnique($unique) {
		$this->unique = $unique;
	}

	/**
	 * @param string $workload
	 */
	public function setWorkload($workload) {
		$this->workload = $workload;
	}
}