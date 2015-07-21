<?php

require "vendor/autoload.php";

$cacheEngine = \ByJG\Cache\HttpContext::factory();

var_dump(\ByJG\Cache\HttpContext::getInstance()->getMemcachedConfig());

// ------------------

$cacheTest = \ByJG\Cache\FileSystemCacheEngine::getInstance();

ByJG\Cache\LogHandler::getInstance()->pushLogHandler(new \Monolog\Handler\StreamHandler('php://stderr', Monolog\Logger::DEBUG));

$cacheTest->set('test', 'Message to be cached');

echo 'Key test: ' . $cacheTest->get('test')  . "\n";
echo 'Key inexistent: ' . $cacheTest->get('non-existent') . "\n";