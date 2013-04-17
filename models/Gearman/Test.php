<?php

class Gearman_Test {

	/**
	 * @var string
	 */
	private $test = '';

	/**
	 * @var string
	 */
	private $someVar = '';

	/**
	 * Construct.
	 */
	public function __construct($someVar) {
		$this->someVar = $someVar;
		$this->test = get_class($this);
	}

	/**
	 * @return string
	 */
	public function testRun() {
		$this->test .= ' -> testRun() works. Runs now.';
		return $this->test;
	}

}