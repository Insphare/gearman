<?php

/**
 * Factory for Gearman calls for very simplified usage.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Deliver {

	/**
	 * @var Gearman_Connector_Client
	 */
	private static $client;

	/**
	 * Name of index key for constructor.
	 *
	 * @var string
	 */
	const CONSTRUCTOR_ARGS = 'constructorArgs';

	/**
	 * Name of index key for task index.
	 *
	 * @var string
	 */
	const TASK_INDEX = 'taskIndex';

	/**
	 * Name of index key for methods.
	 *
	 * @var string
	 */
	const METHOD_ARGS = 'methodArgs';

	/**
	 * Adds tasks to gearman queue by given Gearman_Tasks instance.
	 *
	 * @param Gearman_Task $tasks
	 */
	public static function addTasks(Gearman_Task $tasks) {
		$taskList = $tasks->getTasks();

		foreach ($taskList as $taskIndex => $task) {
			self::addTask($task['germanIndexKey'], $task[self::METHOD_ARGS], $task[self::CONSTRUCTOR_ARGS], $taskIndex, $task['unique']);
		}

		$client = self::getClient();
		$client->runTasks();
	}

	/**
	 * @param $gearmanIndexKey
	 * @param array $methodArguments
	 * @param array $constructorArguments
	 * @param $taskIndex
	 * @param null $unique
	 * @return GearmanTask
	 */
	private static function addTask($gearmanIndexKey, array $methodArguments = array(), array $constructorArguments = array(), $taskIndex, $unique = null) {
		/**
		 * Wrong key -> exception.
		 */
		$config = self::getConfig($gearmanIndexKey);

		$client = self::getClient();
		$client->setPriority($config->getPriority());
		$workload = self::getWorkload($constructorArguments, $methodArguments, $taskIndex);

		$client->setPriority($config->getPriority());

		if (true === $config->getSynchronous()) {
			$client->addTaskSynchronous($gearmanIndexKey, $workload, $unique);
		}
		else {
			return $client->addTaskAsynchronous($gearmanIndexKey, $workload, $unique);
		}

		return null;
	}

	/**
	 * Adds a job to gearman queue by given params.
	 *
	 * @param string $gearmanIndexKey    key name from configuration file.
	 * @param array $methodArguments
	 * @param array $constructorArguments
	 * @param null $unique
	 * @return string
	 */
	public static function add($gearmanIndexKey, array $methodArguments = array(), array $constructorArguments = array(), $unique = null) {
		/**
		 * Wrong key -> exception.
		 */
		$config = self::getConfig($gearmanIndexKey);

		$client = self::getClient();
		$client->setPriority($config->getPriority());

		$workload = self::getWorkload($constructorArguments, $methodArguments);

		if (true === $config->getSynchronous()) {
			return $client->runSynchronous($gearmanIndexKey, $workload, $unique);
		}
		else {
			return $client->runAsynchronous($gearmanIndexKey, $workload, $unique);
		}
	}

	/**
	 * Generates a unit composition of the workload and returns an array for our gearman logic.
	 *
	 * @param $constructorArguments
	 * @param $methodArguments
	 * @param null $taskIndex
	 * @return array
	 */
	private static function getWorkload($constructorArguments, $methodArguments, $taskIndex = null) {
		$workload = array(
			self::CONSTRUCTOR_ARGS => $constructorArguments,
			self::METHOD_ARGS => $methodArguments,
		);

		if (null !== $taskIndex) {
			$workload[self::TASK_INDEX] = ((int)$taskIndex) + 1;
		}

		return $workload;
	}

	/**
	 * @param $gearmanIndexKey
	 *
	 * @return Gearman_Worker_Config
	 */
	private static function getConfig($gearmanIndexKey) {
		return Gearman_Worker_Config::getConfigByConfigKey($gearmanIndexKey);
	}

	/**
	 * Returns our connection instance with consideration of the config value
	 * how the worker should execute.
	 *
	 * @return Gearman_Connector_Client
	 */
	public static function getClient() {
		if (null == self::$client) {
			if (true === self::runAsRealGearmanJob()) {
				self::$client = new Gearman_Connector_Client(array());
			}
			else {
				self::$client = new Gearman_Connector_Background(array());
			}
		}

		return self::$client;
	}

	/**
	 * @return bool
	 */
	private static function runAsRealGearmanJob() {
		$config = new Gearman_Config();
		return (bool)$config->getRunAsGearman();
	}
}
