# Basic Usage

## Get an element from cache

```php
$pool = CacheContext::psrFactory();
$item = $pool->getItem('mykey');
if (!$item->isHit()) {
    // Do the operations will be cached
    // ....
    // And set variable '$value'
    $value = ...;
    $item->set($value);
    $item->expiresAt(3600);

    $pool->save($item);
}
return $item->get();
```

## Clear an element from cache

```php
$pool = CacheContext::psrFactory();
$pool->deleteItem('mykey');
```
