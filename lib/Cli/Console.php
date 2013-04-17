<?php

class Cli_Console {

	/**
	 * Windows mode
	 */
	const ENVIRONMENT_WINDOWS = 1;

	/**
	 * Unix mode
	 */
	const ENVIRONMENT_UNIX = 2;

	/**
	 * @var bool
	 */
	private $synchronous = true;

	/**
	 * @var array
	 */
	private $commands = array();

	/**
	 * @param bool $executeBackground
	 */
	public function setSynchronous($executeBackground) {
		$this->synchronous = (bool)$executeBackground;
	}

	/**
	 * @return bool
	 */
	public function getSynchronous() {
		return (bool)$this->synchronous;
	}

	/**
	 * @return int
	 */
	private function getEnvironment() {
		$uName = strtolower(php_uname());
		if (substr($uName, 0, 7) == "windows") {
			return self::ENVIRONMENT_WINDOWS;
		}

		return self::ENVIRONMENT_UNIX;
	}

	/**
	 * @param $command
	 */
	public function addCommand($command, $escape = true) {
		if (true === $escape) {
			$command = escapeshellcmd($command);
		}

		$this->commands[] = $command;
	}

	/**
	 * @return array
	 */
	private function getCommands() {
		return $this->commands;
	}

	/**
	 * @param $command
	 * @return int|string
	 */
	public function execute() {
		$output = '';

		foreach ($this->getCommands() as $command) {
			$this->output('Command: ' . ($command));
			$this->output('Async: '  . (int)!$this->getSynchronous());

			if (true === $this->getSynchronous()) {
				$output .= $this->executeSynchronous($command);
			}
			else {
				$output .= $this->executeAsynchronous($command);
			}
		}

		return $output;
	}

	private function output($message) {
		if (PHP_SAPI === 'cli') {
			echo '['.date('Y-m-d H:i:s').'] ' . $message . PHP_EOL;
		}
	}

	/**
	 * @param $string
	 * @return string
	 */
	public function escapeArguments($string) {
		return escapeshellarg($string);
	}

	/**
	 * @param $command
	 * @return int|string
	 */
	private function executeSynchronous($command) {
		if ($this->getEnvironment() === self::ENVIRONMENT_WINDOWS) {
			return pclose(popen("start " . $command, "r"));
		}
		else {
			return shell_exec($command);
		}
	}

	/**
	 * @param $command
	 * @return int|string
	 */
	private function executeAsynchronous($command) {
		if ($this->getEnvironment() === self::ENVIRONMENT_WINDOWS) {
			return pclose(popen("start /B " . $command, "r"));
		}
		else {
			return shell_exec($command . " > /dev/null &");
		}
	}
}