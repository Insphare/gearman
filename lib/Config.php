<?php

class Config {

	/**
	 * @var array
	 */
	private $data = array();

	/**
	 * @param array $data
	 */
	public function __construct(array $data = array()) {
		$this->setData($data);
	}

	/**
	 * @param  $key
	 * @param  $value
	 */
	public function set($key, $value) {
		$this->data[(string)$key] = $value;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get($key) {
		$key = (string)$key;

		if (!isset($this->data[$key])) {
			return null;
		}

		return $this->data[$key];
	}

	/**
	 * @param string $key
	 */
	public function delete($key) {
		unset($this->data[$key]);
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data) {
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
}