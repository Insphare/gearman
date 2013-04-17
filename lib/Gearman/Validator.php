<?php

/**
 * Checks whether a worker by given key can be called.
 */
class Gearman_Validator {

	/**
	 * @param string $index key name from gearman worker config.
	 *
	 * @return bool
	 */
	public static function check($index) {
		$config = Gearman_Worker_Config::getConfigByConfigKey($index);

		$className = $config->getClass();

		$success = false;
		try {
			/**
			 * @var $autoLoader Autoloader
			 */
			$autoLoader = Registry::get(Registry::AUTO_LOADER);
			$autoLoader->classExists($className);
			$success = true;
		}
		catch (Exception $e) {
			// silent catch
		}

		if (true === $success && true === class_exists($className) && true === method_exists($className, $config->getMethod())
		) {
			return true;
		}

		return false;
	}
}
