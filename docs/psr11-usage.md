## Use a PSR-11 container to retrieve the cache keys

You can use a PSR-11 compatible to retrieve the cache keys. Once is defined, only the keys defined
in the PSR-11 will be used to cache.

```php
<?php
$fileCache = new \ByJG\Cache\Psr16\FileSystemCacheEngine()
$fileCache->withKeysFromContainer(new SomePsr11Implementation());
```

After the PSR-11 container is defined, when I run:

```php
$value = $fileCache->get('my-key');
```

The key `my-key` will be retrieved from the PSR-11 container and
the value retrieved will be used as the cache key.
If it does not exist in the PSR-11 container, an exception will be thrown.
