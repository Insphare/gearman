<?php

/**
 * This class manages the synchronous and asynchronous gearman calls on a simplified manner.
 * They can deal with priorities, synchronicity and tasks.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Connector_Client extends Gearman_Connector_Abstract {

	/**
	 * Priority is high.
	 *
	 * @var int
	 */
	const PRIORITY_HIGH = 1;

	/**
	 * Priority is normal.
	 *
	 * @var int
	 */
	const PRIORITY_NORMAL = 2;

	/**
	 * Priority is low.
	 *
	 * @var int
	 */
	const PRIORITY_LOW = 3;

	/**
	 * Contains the current priority of tasks and jobs.
	 *
	 * @var int
	 */
	private $priority = self::PRIORITY_NORMAL;

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->delegate = new GearmanClient();
		parent::__construct($config);
	}

	/**
	 * Runs a job asynchronous.
	 *
	 * @param string $workerName
	 * @param array $conditions
	 * @param null $unique
	 * @return string
	 * @throws Gearman_Connector_Exception
	 */
	public function runAsynchronous($workerName, array $conditions, $unique = null) {
		$workerMethodName = $this->getWorkerMethodName($workerName);

		switch ($this->getPriority()) {
			case self::PRIORITY_NORMAL:
				return $this->delegate->doBackground($workerMethodName, serialize($conditions), $unique);

			case self::PRIORITY_HIGH:
				return $this->delegate->doHighBackground($workerMethodName, serialize($conditions), $unique);

			case self::PRIORITY_LOW:
				return $this->delegate->doLowBackground($workerMethodName, serialize($conditions), $unique);
		}

		throw new Gearman_Connector_Exception('Invalid value "' . $this->getPriority() . '" for priority!');
	}

	/**
	 * Runs a job synchronous.
	 *
	 * @param string $workerName
	 * @param array $conditions
	 * @param null $unique
	 * @return string
	 * @throws Gearman_Connector_Exception
	 */
	public function runSynchronous($workerName, array $conditions, $unique = null) {
		$workerMethodName = $this->getWorkerMethodName($workerName);

		switch ($this->getPriority()) {
			case self::PRIORITY_NORMAL:
			case self::PRIORITY_LOW:
				$result = $this->delegate->doLow($workerMethodName, serialize($conditions), $unique);
				return $result;

			case self::PRIORITY_HIGH:
				$result = $this->delegate->doHigh($workerMethodName, serialize($conditions), $unique);
				return $result;
		}

		throw new Gearman_Connector_Exception('Invalid value "' . $this->getPriority() . '" for priority!');
	}

	/**
	 * Collect all tasks for synchronous mode and manages the unique identifier.
	 *
	 * @param string $workerName
	 * @param array $conditions
	 * @param null $unique
	 * @return GearmanTask
	 * @throws Gearman_Connector_Exception
	 */
	public function addTaskSynchronous($workerName, array $conditions, $unique = null) {
		$workerMethodName = $this->getWorkerMethodName($workerName);

		if (null === $unique) {
			$unique = md5(serialize($conditions)) . '_';
			if (isset($conditions[Gearman_Deliver::TASK_INDEX])) {
				$unique .= $conditions[Gearman_Deliver::TASK_INDEX];
			}
			else {
				$unique .= uniqid();
			}
		}

		switch ($this->getPriority()) {
			case self::PRIORITY_NORMAL:
				return $this->delegate->addTask($workerMethodName, serialize($conditions), null, $unique);

			case self::PRIORITY_HIGH:
				return $this->delegate->addTaskHigh($workerMethodName, serialize($conditions), null, $unique);

			case self::PRIORITY_LOW:
				return $this->delegate->addTaskLow($workerMethodName, serialize($conditions), null, $unique);
		}

		throw new Gearman_Connector_Exception('Invalid value "' . $this->getPriority() . '" for priority!');
	}

	/**
	 * Collect all tasks for asynchronous mode and manages the unique identifier.
	 *
	 * @param string $workerName
	 * @param array $conditions
	 * @param null $unique
	 * @return GearmanTask
	 * @throws Gearman_Connector_Exception
	 */
	public function addTaskAsynchronous($workerName, array $conditions, $unique = null) {
		$workerMethodName = $this->getWorkerMethodName($workerName);

		if (null === $unique) {
			$unique = md5(serialize($conditions)) . '_';
			if (isset($conditions[Gearman_Deliver::TASK_INDEX])) {
				$unique .= $conditions[Gearman_Deliver::TASK_INDEX];
			}
			else {
				$unique .= uniqid();
			}
		}

		switch ($this->getPriority()) {
			case self::PRIORITY_NORMAL:
				return $this->delegate->addTaskBackground($workerMethodName, serialize($conditions), null, $unique);

			case self::PRIORITY_HIGH:
				return $this->delegate->addTaskHighBackground($workerMethodName, serialize($conditions), null, $unique);

			case self::PRIORITY_LOW:
				return $this->delegate->addTaskLowBackground($workerMethodName, serialize($conditions), null, $unique);
		}

		throw new Gearman_Connector_Exception('Invalid value "' . $this->getPriority() . '" for priority!');
	}

	/**
	 * Run the collected tasks in parallel (assuming multiple workers)
	 *
	 * @throws Gearman_Connector_Exception
	 */
	public function runTasks() {
		if (!$this->delegate->runTasks()) {
			throw new Gearman_Connector_Exception($this->delegate->error());
		}
	}

	/**
	 * Sets the priority.
	 *
	 * @param int $priority
	 * @throws Gearman_Connector_Exception
	 * @return bool
	 */
	public function setPriority($priority) {

		switch ($priority) {
			case self::PRIORITY_NORMAL:
			case self::PRIORITY_HIGH:
			case self::PRIORITY_LOW:
				$this->priority = $priority;
				return true;

			default:
		}

		throw new Gearman_Connector_Exception('Invalid value "' . $this->getPriority() . '" to set priority!');
	}

	/**
	 * Gets the current priority.
	 *
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}
}
