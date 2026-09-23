# Changelog

## Unreleased

- Preserve PHP 7.1.3 and Laravel 5.4 compatibility while testing through Laravel 13 and PHP 8.5.
- Fix service provider bindings for the facade and container alias.
- Read both historical package cache formats and share cached responses between vendor and package methods.
- Apply the configured package cache lifetime to package details.
- Handle unavailable packages, invalid responses, and missing statistics without PHP warnings.
- Add a connection timeout and optional bounded retries while keeping the existing default of one request.
- Add `packagist:install` and `packagist:update` commands that preserve existing application files.
- Replace the Travis installation check with GitHub Actions tests, linting, coverage, and a dependency audit.
- Update Scrutinizer configuration, documentation, and light/dark README banners.

Existing public methods, configuration keys, cache keys, and translation messages are retained. No routes or frontend dependencies are installed. Package errors still return translated strings; unavailable vendor lists return empty collections, and vendor totals omit unavailable packages.
