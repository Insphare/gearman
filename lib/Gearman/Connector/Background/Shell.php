<?php

/**
 * This class imitates a german worker process and execute a worker by shell.
 *
 * This class is used as alternative when your workers should not run as real gearman worker.
 * For example in development mode.
 *
 * @author Manuel Will <insphare@gmail.com>
 */

final class Gearman_Connector_Background_Shell {

	/**
	 * @var string
	 */
	private $lastTempFile = '';

	/**
	 * @param $methodName
	 * @param $arguments
	 *
	 * @return bool|null|string
	 * @throws Gearman_Connector_Exception
	 */
	public function __call($methodName, $arguments) {
		$excludeFunctions = array(
			'runTasks' => 'runTasks',
			'error' => 'error',
		);

		$rc = new ReflectionClass('Gearman_Connector_Background_Interface');
		$methods = $rc->getMethods();
		$allowedMethods = array();

		foreach ($methods as $data) {
			$allowedMethods[$data->getName()] = $data->getName();
		}

		if (isset($excludeFunctions[$methodName])) {
			return true;
		}

		if (!isset($allowedMethods[$methodName])) {
			throw new Gearman_Connector_Exception('Call to undefined method ' . get_class($this) . '::' . $methodName . '()');
		}

		$isSynchronous = preg_match('~background~i', $methodName) == 0;
		$basePath = Registry::get(Registry::BASE_PATH);

		$functionIndexName = $arguments[0];
		$functionIndexName = explode('_', $functionIndexName);
		$functionIndexName = $functionIndexName[0];
		$uniqueKey = 'shell_unique_key_' . escapeshellcmd(uniqid());

		$cmd = 'cd ' . $basePath . 'scripts;';
		$cmd .= 'php gearman_background.php ' . $functionIndexName . ' '. $uniqueKey .' '  . $arguments[2];
		$cmd = trim($cmd);

		if (true === $isSynchronous) {
			return $this->runSync($cmd, $arguments[1]);
		}
		else {
			$this->runAsync($cmd, $arguments[1]);
		}

		return null;
	}

	/**
	 * Runs the cmd.
	 *
	 * @param string $cmd
	 * @param $workload
	 * @param string $outputFile
	 */
	private function runAsync($cmd, $workload, $outputFile = "/dev/null") {
		$tempName = $this->getTempName();
		file_put_contents($tempName, $workload);

		$console = new Cli_Console();
		$console->addCommand("{$cmd} $tempName", false);
		$console->addCommand("rm $tempName");
		$console->execute();
		//2>&1 > {$outputFile}
	}

	/**
	 * Runs the cmd.
	 *
	 * @param string $cmd
	 * @param $workload
	 * @return string
	 */
	private function runSync($cmd, $workload) {
		$tempName = $this->getTempName();
		file_put_contents($tempName, $workload);

		$console = new Cli_Console();
		$console->addCommand("{$cmd} $tempName", false);
		$result = $console->execute();
		unlink($tempName);
		return $result;
	}

	/**
	 * @return string
	 */
	private function getTempName() {
		$this->lastTempFile = tempnam('/tmp', 'gearmanqueue');
		return $this->lastTempFile;
	}

	/**
	 * @return string
	 */
	public function getLastTempFile() {
		return $this->lastTempFile;
	}
}
