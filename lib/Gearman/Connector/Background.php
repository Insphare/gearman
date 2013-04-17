<?php

/**
 * This class inherited the methods from Gearman_Connector_Client
 * and use as delegated class the Gearman_Connector_Background_Shell class.
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Gearman_Connector_Background extends Gearman_Connector_Client {

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->delegate = new Gearman_Connector_Background_Shell();
	}
}