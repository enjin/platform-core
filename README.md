# Enjin Platform

The core package for the Enjin Platform.

[![License: LGPL 3.0](https://img.shields.io/badge/license-LGPL_3.0-purple)](https://opensource.org/license/lgpl-3-0/)
[![Tests](https://github.com/enjin/platform-core/workflows/Run%20Tests/badge.svg)](https://github.com/enjin/platform-core/actions?query=workflow%3ARun%20Tests)


Enjin Platform is the most powerful and advanced open-source framework for building NFT Platforms.

## Requirements

When using [Platform-UI](https://github.com/enjin/platform-ui) make sure you have Node and NPM installed:
```bash
node -v      
# v16.17.0
npm -v      
# 9.2.0
```

## Installation

You can install it with Composer in your Laravel application:

```bash
composer require enjin/platform-core
```

This package will load its migrations automatically, you need to execute them by running:

```bash
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="platform-core-config"
```

## Usage

First, you should sync your platform with a snapshot of Enjin state:
```bash
php artisan platform:sync
```

After that you need to start fetching the blocks from the blockchain:
```bash
php artisan platform:ingest
```

Then you should start the processor to update your local database:
```bash
php artisan queue:work

# Or, if you're using Laravel Horizon
php artisan horizon
```

Finally, you may start the development server to access the API by running:
```bash
php artisan serve
```

You will find the GraphiQL playground on:
```
http://localhost:8000/graphiql
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Enjin](https://github.com/enjin)
- [All Contributors](../../contributors)

## License

The LGPL 3.0 License. Please see [License File](LICENSE.md) for more information.
