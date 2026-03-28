# UPGRADE GUIDE

## Upgrade from 12.x to 13.x

1. Use **PHP 8.3+** and **Laravel 13.x** ([Yajra DataTables upgrade guide](https://yajrabox.com/docs/laravel-datatables/master/upgrade)).

2. Update `composer.json`:

```json
"require": {
    "yajra/laravel-datatables-export": "^13.0"
}
```

3. Run `composer update yajra/laravel-datatables-export` (or `composer update`).

4. Re-publish config and views if you customize them:

```bash
php artisan vendor:publish --tag=datatables-export --force
```

### Dependency requirements (v13)

Composer will not resolve until your app matches these **peer-style** constraints (they are declared as direct `require` entries on this package):

| Package | Minimum constraint | Notes |
|--------|-------------------|--------|
| [Livewire](https://livewire.laravel.com/docs/upgrading) | **^4.0** | Livewire v2 and v3 are no longer supported by this package. Upgrade the app to Livewire 4 first. |
| [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) | **^5.0** | Used for `NumberFormat` constants in config and column `exportFormat` examples. PhpSpreadsheet 4.x and older are not installed alongside current releases. |
| [OpenSpout](https://github.com/openspout/openspout/blob/5.x/UPGRADE.md) | **^5** | Used internally for queued export writing. If another package pins OpenSpout 4.x, resolve the conflict (usually by upgrading that package or aligning on OpenSpout 5). |

There is no application code migration for the Livewire export button beyond meeting Livewire 4’s upgrade steps in your app. Published Blade views under `resources/views/vendor/datatables-export` should be re-published or diffed after major Livewire upgrades.

## Upgrade from 10.x to 11.x

1. Update the composer.json file and change the version of the package to `^11.0`:

```json
"require": {
    "yajra/laravel-datatables-export": "^11.0"
}
```

2. Run `composer update` to update the package.
