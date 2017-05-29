# Basic Usage

All implementations are PDR-16. So, just create an instance:

```php
<?php
$cacheEngine = new \ByJG\Cache\Psr16\FileSystemCacheEngine();

$result = $cacheEngine->get($key);
if (!empty($result))
{
    // Do the operations will be cached
    // ....
    // And set variable result
    $result = "...";

    // Set the cache:
    $cacheEngine->set($key, $result, 60);
}
return $result;
```
