# Setup Log Handler

This will available for the cache instances:

```php
<?php
$logger = new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG);
$cache = new \ByJG\Cache\Psr16\FileSystemCacheEngine(null, $logger);
```
