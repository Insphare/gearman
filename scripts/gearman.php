<?php
include_once 'base.php';

$command = null;
if (!empty($argv[1])) {
	$command = $argv[1];
}

$command = strtolower($command);

$config = new Config_Gearman();
$config = $config->get('gearman.core');
$pidFile = $config['pidFile'];

switch ($command) {
	case 'start':
		start($pidFile);
		break;

	case 'stop':
		stop($pidFile);
		break;

	case 'restart':
		stop($pidFile);
		start($pidFile);
		break;

	default:
		echo PHP_EOL;
		echo PHP_EOL;
		echo('No command given! Please use start,stop,restart');
		echo PHP_EOL;
		die();
}

echo PHP_EOL;
echo PHP_EOL;

function start($pidFile) {
	if (file_exists($pidFile)) {
		die('Gearman is already running!');
	}

	new Gearman_Manager();
}

function stop($pidFile) {
	if (!file_exists($pidFile)) {
		echo ('Gearman is not running!') . PHP_EOL;
		return;
	}

	$pidNumber = trim(file_get_contents($pidFile));
	if (!posix_getsid($pidNumber)) {
		unlink($pidFile);
	}

	posix_kill($pidNumber, SIGTERM);

	$dieTime = time() + 25;
	while(true) {
		if ($dieTime < time()) {
			echo 'Error: Gearman can not stopped.' . PHP_EOL;
			break;
		}
		usleep(3000);
		if (!file_exists($pidFile)) {
			echo 'Gearman stopped now!' . PHP_EOL;
			break;
		}
	}

}
