<?php
include_once 'base.php';

$functionName = $argv[1];
$unique = $argv[2];
$fileName = $argv[3];

$workloadData = unserialize(file_get_contents($fileName));

$job = new Gearman_Job_Imitation();
$job->setFunctionName($functionName);
$job->setWorkload(serialize($workloadData));
$job->setHandle('shell_'.time() . '_' . uniqid());
$job->setUnique($unique);

$gearmanJob = new Gearman_Job($job);

$log = '';
$gearmanConfig = Gearman_Worker_Config::getConfigByConfigKey($functionName);
$result = Gearman_Worker_Process::process($gearmanConfig, $gearmanJob, $log);

if (!empty($result)) {
	// note: print output to shell
	echo serialize($result);
}

