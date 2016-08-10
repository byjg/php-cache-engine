# Basic Usage

```php
$cacheEngine = CacheContext::factory();

$result = $cacheEngine->get($key, 60);
if ($result === false)
{
    // Do the operations will be cached
    // ....
    // And set variable result
    $result = ...;

    // Set the cache:
    $cacheEngine->set($key, $result, 60);
}
return $result;
```

