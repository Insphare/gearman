<?php

class Ping {

	/**
	 * Default port usage.
	 */
	const DEFAULT_PORT = 80;

	/**
	 * Default time out
	 */
	const DEFAULT_TIMEOUT = 2;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * @var float
	 */
	private $timeout;

	/**
	 * @var bool
	 */
	private $success;

	/**
	 * @var string
	 */
	private $errorString;

	/**
	 * @var int
	 */
	private $errorCode;

	/**
	 * @static
	 *
	 * @param string $host
	 * @param int    $port
	 * @param int    $timeout
	 *
	 * @return bool
	 */
	public static function executeQuickTest($host, $port = self::DEFAULT_PORT, $timeout = self::DEFAULT_TIMEOUT) {
		$ping = new self($host, $port, $timeout);
		return $ping->test();
	}

	/**
	 * @param string $host
	 * @param int    $port
	 * @param int    $timeout
	 */
	public function __construct($host, $port = self::DEFAULT_PORT, $timeout = self::DEFAULT_TIMEOUT) {
		$this->setPort($port);
		$this->setHost($host);
		$this->setTimeoutSeconds($timeout);
	}

	/**
	 * Set timeout in seconds.
	 *
	 * @param $int
	 */
	public function setTimeoutSeconds($int) {
		$this->timeout = (int)$int;
	}

	/**
	 * Sett timeout in milli seconds.
	 *
	 * @param $mSeconds
	 */
	public function setTimeoutMilliSeconds($mSeconds) {
		$this->timeout = (float)(($mSeconds) / 1000);
	}

	/**
	 * Set host.
	 *
	 * @param $host
	 */
	public function setHost($host) {
		if (strpos($host, ':') !== false) {
			list($host, $port) = explode(':', $host);
			$this->port = $port;
		}

		$this->host = $host;
	}

	/**
	 * Set port.
	 *
	 * @param $port
	 */
	public function setPort($port) {
		$this->port = (int)$port;
	}

	/**
	 * Tests the server.
	 *
	 * @return bool
	 */
	public function test() {
		$errorReportingLevel = error_reporting(0);
		$this->success = fsockopen($this->host, $this->port, $this->errorCode, $this->errorString, $this->timeout);
		error_reporting($errorReportingLevel);

		return false !== $this->success;
	}

	/**
	 * Gets the error string if exists.
	 *
	 * @return string
	 */
	public function getErrorString() {
		return $this->errorString;
	}

	/**
	 * Get error code
	 *
	 * @return int
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}

	/**
	 * Checks whether an error occurred.
	 *
	 * @return bool
	 */
	public function hasError() {
		if (false === $this->success && $this->errorCode === 0) {
			return true;
		}

		return false;
	}
}