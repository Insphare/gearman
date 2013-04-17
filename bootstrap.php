<?php

define('DS', DIRECTORY_SEPARATOR);

$includePath = get_include_path();
$basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR;

$includePathDirectories = array(
	$basePath . 'config',
	$basePath . 'lib',
	$basePath . 'models',
);

set_include_path(get_include_path() . ':' . implode(':', $includePathDirectories));
include_once $basePath . 'lib' . DIRECTORY_SEPARATOR . 'Autoloader.php';

$autoLoader = new Autoloader($basePath);
spl_autoload_register(array($autoLoader, 'loadFileByClassName'));

if (!isset($isDevMode)) {
	$isDevMode = true;
}

Registry::set(Registry::BASE_PATH, $basePath);
Registry::set(Registry::AUTO_LOADER, $autoLoader);
Registry::set(Registry::IS_DEVELOPMENT_MODE, (bool) $isDevMode);