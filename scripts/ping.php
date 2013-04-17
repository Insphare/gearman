<?php
include_once 'base.php';

$gearmanConfig = new Config_Gearman();
$config = $gearmanConfig->get('gearman.core');
$errors = array();
$gearmanServers = $config['servers'];

foreach ($gearmanServers as $server => $active) {
	if (true === $active) {
		$port = GEARMAN_DEFAULT_TCP_PORT;

		if (false !== strpos($server, ':')) {
			list($server, $port) = explode(':', $server);
		}

		unset($ping);
		$ping = new Ping($server, $port);
		$ping->setTimeoutSeconds(1);
		$state = $ping->test();

		if (false === $state) {
			$errors[] = 'Gearman Server ['.$server.'] with port ['.$port.'] are not pingable.' . PHP_EOL;
		}
	}
}

$pidFile = $config['pidFile'];
if (!file_exists($pidFile)) {
	$errors[] = 'Gearman pid-file is missing. That is mean, gearman is not running!';
}

if (count($errors)) {
	echo 'Errors: ' . PHP_EOL;
	foreach ($errors as $error) {
		echo $error;
	}
}
else {
	echo 'Gearman runs! State: OK!';
}

echo PHP_EOL;
