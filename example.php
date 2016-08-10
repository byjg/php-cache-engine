<?php
require "vendor/autoload.php";

session_start();

// Get the 'default' object in the config/cacheconfig.php
$cacheEngine = \ByJG\Cache\CacheContext::factory();
print_r(\ByJG\Cache\CacheContext::getInstance()->getMemcachedConfig());
print_r(\ByJG\Cache\CacheContext::getInstance()->getShmopConfig());

// Use SessionCacheEngine
$sessionCache = new ByJG\Cache\SessionCacheEngine();
$sessionCache->set('key', 'somevalue');
echo $sessionCache->get('key') . "\n";

// Attach log to cache
$cacheTest = new \ByJG\Cache\FileSystemCacheEngine();

ByJG\Cache\LogHandler::getInstance()->pushLogHandler(
    new \Monolog\Handler\StreamHandler('php://stderr', Monolog\Logger::DEBUG)
);

$cacheTest->set('test', 'Message to be cached');

echo 'Key test: ' . $cacheTest->get('test') . "\n";
echo 'Key inexistent: ' . $cacheTest->get('non-existent') . "\n";

echo "\n\n--Shmop\n\n";
$shmop = new \ByJG\Cache\ShmopCacheEngine();
$shmop->set('mykey', 'novo teste');
echo 'Shmop Key: ' . $shmop->get('mykey') . "\n";
echo 'Shmop Key inexistent: ' . $shmop->get('non-existent') . "\n";
$shmop->release('mykey');
echo 'Shmop check released: ' . $shmop->get('mykey') . "\n";
