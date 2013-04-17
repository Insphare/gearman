<?php

/**
 * This interface will only used by reflection class to get all allowed methods.
 *
 * @see Gearman_Connector_Background_Shell
 * @author Manuel Will <insphare@gmail.com>
 */

interface Gearman_Connector_Background_Interface {

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function doBackground($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function doHighBackground($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function doLowBackground($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function doLow($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function doHigh($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function addTask($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function addTaskHigh($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function addTaskLow($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function addTaskBackground($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function addTaskHighBackground($workerMethodName, $workload, $context, $unique);

	/**
	 * @param $workerMethodName
	 * @param $workload
	 * @param $context
	 * @param $unique
	 * @return mixed
	 */
	public function addTaskLowBackground($workerMethodName, $workload, $context, $unique);

	/**
	 * @return mixed
	 */
	public function runTasks();

	/**
	 * @return mixed
	 */
	public function error();
}
