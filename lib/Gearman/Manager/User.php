<?php

class Gearman_Manager_User {

	/**
	 * @var int
	 */
	private $userId = 0;

	/**
	 * @var string
	 */
	private $userName;

	/**
	 * @param $userName
	 */
	public function __construct($userName) {
		$user = posix_getpwnam($userName);
		if (!$user || !isset($user['uid'])) {
			die('User (' . $userName . ') not found.');
		}

		$this->userId = $user['uid'];
		$this->userName = $userName;
	}

	/**
	 * @return int
	 */
	public function getUserId() {
		return (int)$this->userId;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->userName;
	}
}
