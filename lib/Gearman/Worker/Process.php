<?php

/**
 * This is the real procedure when a worker work!
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Worker_Process {

	/**
	 * @var Gearman_Worker_Process[]
	 */
	private static $instance;

	/**
	 * @var Gearman_Worker_Config
	 */
	private $config;

	/**
	 * @var Gearman_Job_Callback
	 */
	private $callback;

	/**
	 * @var Gearman_Job
	 */
	private $job;

	/**
	 * @param Gearman_Worker_Config $config
	 */
	public function __construct(Gearman_Worker_Config $config) {
		$this->config = $config;
	}

	/**
	 * @return Gearman_Job_Callback
	 */
	public function getCallback() {
		if (null === $this->callback) {
			$this->callback = new Gearman_Job_Callback();
		}

		return $this->callback;
	}

	/**
	 * @param Gearman_Job $job
	 */
	public function setJob(Gearman_Job $job) {
		$this->job = $job;
	}

	/**
	 * @return Gearman_Job
	 */
	public function getJob() {
		return $this->job;
	}

	/**
	 * @param Gearman_Worker_Config $config
	 *
	 * @return Gearman_Worker_Process
	 */
	private static function getWorkerClassByConfig(Gearman_Worker_Config $config) {
		if (!isset(self::$instance[$config->getKey()])) {
			self::$instance[$config->getKey()] = new Gearman_Worker_Process($config);
		}

		return self::$instance[$config->getKey()];
	}

	/**
	 * @param Gearman_Worker_Config $config
	 * @param Gearman_Job $job
	 * @param                       $log
	 * @return mixed
	 */
	public static function process(Gearman_Worker_Config $config, Gearman_Job $job, &$log) {
		$worker = self::getWorkerClassByConfig($config);

		try {
			$worker->setJob($job);
			$worker->getCallback()->created($worker->getJob());
			$arguments = $worker->getJob()->getParams();

			if (true == $config->getIsStatic()) {
				$result = call_user_func($config->getClass() . "::" . $config->getMethod(), $arguments[Gearman_Deliver::METHOD_ARGS]);
			}
			else {
				$rc = new ReflectionClass($config->getClass());
				if (null !== $rc->getConstructor()) {
					$realClass = $rc->newInstanceArgs($arguments[Gearman_Deliver::CONSTRUCTOR_ARGS]);
				}
				else {
					$realClass = $rc->newInstance();
				}

				$result = call_user_func_array(array(
					$realClass,
					$config->getMethod()
				), $arguments[Gearman_Deliver::METHOD_ARGS]);
			}

			$worker->getCallback()->complete($worker->getJob(), $result);

			return $result;
		}
		catch (Exception $e) {
			$worker->getCallback()->fail($worker->getJob(), $e->getMessage());
		}

		return null;
	}
}
