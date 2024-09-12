# Setup Log Handler

You can add a PSR Log compatible to the constructor in order to get Log of the operations.

## Example

```php
<?php
$logger = new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG);
$cache = new \ByJG\Cache\Psr16\FileSystemCacheEngine(null, $logger);
```
