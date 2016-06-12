# Setup Log Handler

This will available for the cache instances:

```php
ByJG\Cache\LogHandler::getInstance()->pushLogHandler(
    new \Monolog\Handler\StreamHandler('php://stderr', Monolog\Logger::DEBUG)
);
```
