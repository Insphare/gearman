<?php

$basePath = dirname(dirname((__FILE__)));
$bootstrapFile = $basePath . '/bootstrap.php';
$bootstrapFile = str_replace('/', DIRECTORY_SEPARATOR, $bootstrapFile);
include_once $bootstrapFile;
