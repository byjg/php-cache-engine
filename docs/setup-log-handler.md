---
sidebar_position: 13
---

# Setup Log Handler

You can add a PSR-3 compatible logger to the constructor to log cache operations.

## Example

```php
<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('cache');
$logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

// Pass the logger to the cache engine constructor
$cache = new \ByJG\Cache\Psr16\FileSystemCacheEngine('cache', null, $logger);
```

:::tip
Most cache engines accept a PSR-3 logger as a constructor parameter. Check the specific engine documentation for the exact parameter position.
:::
