# Changelog - Version 6.0

## Overview

Version 6.0 represents a major update focused on modernizing the codebase for PHP 8.3+ compatibility, improving code quality with strict typing, and enhancing developer experience with updated tooling.

## Breaking Changes

| Area | Before (5.x) | After (6.0) | Description |
|------|--------------|-------------|-------------|
| **PHP Version** | `>=8.1 <8.4` | `>=8.3 <8.6` | Minimum PHP version raised from 8.1 to 8.3. PHP 8.1 and 8.2 are no longer supported. Added support for PHP 8.4 and 8.5. |
| **PHPUnit** | `^9.6` | `^10.5\|^11.5` | PHPUnit 9.6 support dropped. Minimum version is now 10.5. Added support for PHPUnit 11.5. |
| **PSR-3 Logger** | `^1.0\|^1.1\|^2.0` | `^1.0\|^2.0\|^3.0` | Added PSR-3 version 3.0 support. Removed version 1.1 constraint (covered by 1.0). |
| **Psalm** | `^5.9` | `^5.9\|^6.13` | Added support for Psalm 6.13 while maintaining backward compatibility with 5.9. |

## New Features

### Code Quality Enhancements
- **PHP 8.3+ Override Attribute**: Added `#[Override]` attributes to all overridden methods across the codebase for better code clarity and IDE support
- **Strict Typing**: Enhanced type declarations throughout the codebase for improved type safety
- **Psalm SARIF Reporting**: Added SARIF (Static Analysis Results Interchange Format) output support for better CI/CD integration

### Developer Experience
- **Composer Scripts**: Added convenient composer scripts:
  - `composer test` - Run PHPUnit tests
  - `composer psalm` - Run Psalm static analysis with single thread for stability
- **Gitpod Support**: Added `.gitpod.yml` configuration for cloud-based development environment
- **VSCode Configuration**: Added `.vscode/launch.json` with debugging configurations
- **Improved Documentation**: Enhanced all documentation files with better examples and clearer explanations

### Testing Improvements
- **Test Class Refactoring**: Reorganized test class hierarchy for better maintainability
  - Introduced `TestBase` class as the foundation for all cache tests
  - Renamed test classes for clarity and consistency
  - Migrated PHPUnit data providers to PHP 8.1+ syntax
- **Enhanced CI/CD**: Updated GitHub Actions workflows with better PHP version matrix testing

### Engine Improvements
- **Consistent Key Handling**: Fixed key consistency issues in `MemcachedEngine` for more reliable caching
- **FileSystemCacheEngine**: Improved path handling and directory creation logic

## Bug Fixes

- Fixed unit test issues related to session handling in GitHub Actions environment
- Improved Memcached availability testing in CI/CD pipelines
- Fixed test execution issues requiring `--stderr` parameter for SessionCacheEngine tests
- Enhanced error handling and edge cases in various cache engines

## Documentation Updates

All documentation files have been updated to reflect version 6.0 changes:
- Updated code examples to use PHP 8.3+ syntax
- Improved atomic operations documentation
- Enhanced PSR-16 and PSR-6 usage guides
- Updated all engine-specific documentation pages
- Refreshed README with clearer quick start examples
- Added mermaid diagrams for dependency visualization

## Migration Path from 5.x to 6.0

### Step 1: Update PHP Version
Ensure your environment is running PHP 8.3 or later:
```bash
php -v  # Should show 8.3.x, 8.4.x, or 8.5.x
```

If you're on PHP 8.1 or 8.2, you must upgrade your PHP version before migrating to version 6.0.

### Step 2: Update Dependencies
Update your `composer.json`:
```bash
composer require byjg/cache-engine:^6.0
composer update
```

### Step 3: Update Development Dependencies (Optional)
If you're using PHPUnit or Psalm in your project:

**For PHPUnit:**
```bash
composer require --dev phpunit/phpunit:^10.5
# or
composer require --dev phpunit/phpunit:^11.5
```

**For Psalm:**
```bash
composer require --dev vimeo/psalm:^6.13
```

### Step 4: Test Your Application
Run your existing tests to ensure compatibility:
```bash
vendor/bin/phpunit
```

### Step 5: Optional Enhancements
Consider adding the `#[Override]` attribute to your own classes that extend cache engines for better IDE support and code clarity:

```php
class MyCustomCache extends BaseCacheEngine
{
    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        // Your implementation
    }
}
```

### Step 6: Update CI/CD Pipelines
Update your CI/CD configuration to use PHP 8.3+ in your testing matrix. Remove PHP 8.1 and 8.2 from your test matrix.

### Common Migration Issues

**Issue**: Application fails with "PHP version requirement not satisfied"
**Solution**: Upgrade your PHP version to 8.3 or later

**Issue**: PHPUnit tests fail to run
**Solution**: Upgrade PHPUnit to version 10.5 or later: `composer require --dev phpunit/phpunit:^10.5`

**Issue**: Psalm reports new errors
**Solution**: If using Psalm 6.x, review and address the stricter type checking. You can temporarily stay on Psalm 5.9 during migration.

### Testing Your Migration
After completing the migration steps, verify everything works:

```bash
# Run tests
composer test

# Run static analysis
composer psalm

# If using docker-compose
docker compose up -d
composer test
docker compose down
```

## Notes

- All cache engines maintain backward compatibility at the API level
- No changes to PSR-6 or PSR-16 interface implementations
- Existing cache data remains compatible across versions
- The upgrade primarily affects development-time requirements (PHP version, testing tools)

## Links

- [Full Commit History](https://github.com/byjg/php-cache-engine/compare/5.0.4...6.0.0)
- [Documentation](https://github.com/byjg/php-cache-engine/tree/master/docs)
- [Report Issues](https://github.com/byjg/php-cache-engine/issues)
