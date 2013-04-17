<?php

/**
 * Collects all tasks.
 *
 * Is used in Gearman_Deliver::addTasks();
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Task {

	/**
	 * Contains all tasks.
	 *
	 * @var
	 */
	private $tasks = array();

	/**
	 * @param       $gearmanIndexKey
	 * @param array $methodArguments
	 * @param array $constructorArguments
	 * @param null $unique
	 */
	public function addTask($gearmanIndexKey, array $methodArguments = array(), array $constructorArguments = array(), $unique = null) {
		$this->tasks[] = array(
			'germanIndexKey' => $gearmanIndexKey,
			Gearman_Deliver::METHOD_ARGS => $methodArguments,
			Gearman_Deliver::CONSTRUCTOR_ARGS => $constructorArguments,
			'unique' => $unique
		);
	}

	/**
	 * Returns all collected tasks.
	 *
	 * @return array
	 */
	public function getTasks() {
		return $this->tasks;
	}

	/**
	 * Returns the count of tasks.
	 *
	 * @return int
	 */
	public function getCountTasks() {
		return count($this->tasks);
	}
}
