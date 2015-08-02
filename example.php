<?php

require "vendor/autoload.php";

ob_start();


// Get the 'default' object in the config/cacheconfig.php
$cacheEngine = \ByJG\Cache\CacheContext::factory();
var_dump(\ByJG\Cache\CacheContext::getInstance()->getMemcachedConfig());

// Use SessionCacheEngine
$sessionCache = new ByJG\Cache\SessionCacheEngine();
$sessionCache->set('key', 'somevalue');
echo $sessionCache->get('key') . "\n";

// Attach log to cache
$cacheTest = new \ByJG\Cache\FileSystemCacheEngine();

ByJG\Cache\LogHandler::getInstance()->pushLogHandler(new \Monolog\Handler\StreamHandler('php://stderr', Monolog\Logger::DEBUG));

$cacheTest->set('test', 'Message to be cached');

echo 'Key test: ' . $cacheTest->get('test')  . "\n";
echo 'Key inexistent: ' . $cacheTest->get('non-existent') . "\n";
