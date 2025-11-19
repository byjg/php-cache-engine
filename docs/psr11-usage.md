---
sidebar_position: 14
---

# PSR-11 Container Usage

You can use a PSR-11 compatible container to retrieve cache keys. Once defined, only the keys defined
in the PSR-11 container will be used for caching.

```php
<?php
$fileCache = new \ByJG\Cache\Psr16\FileSystemCacheEngine();
$fileCache->withKeysFromContainer(new SomePsr11Implementation());
```

After the PSR-11 container is defined, when you run:

```php
$value = $fileCache->get('my-key');
```

The key `my-key` will be retrieved from the PSR-11 container, and
the value retrieved will be used as the cache key.

:::warning
If the key does not exist in the PSR-11 container, an exception will be thrown.
:::
