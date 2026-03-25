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

v13 aligns with `yajra/laravel-datatables-*` v13 and has no intentional breaking changes beyond the new PHP/Laravel requirements.

## Upgrade from 10.x to 11.x

1. Update the composer.json file and change the version of the package to `^11.0`:

```json
"require": {
    "yajra/laravel-datatables-export": "^11.0"
}
```

2. Run `composer update` to update the package.
