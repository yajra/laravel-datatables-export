# [13.3.0](https://github.com/yajra/laravel-datatables-export/compare/v13.2.2...v13.3.0) (2026-08-13)


### Bug Fixes

* **export:** harden queued export protocol ([6dfd1e4](https://github.com/yajra/laravel-datatables-export/commit/6dfd1e4881d60176ba7c1b61d7693131cb0b4ac8))
* **export:** report row-level export progress ([3f409c3](https://github.com/yajra/laravel-datatables-export/commit/3f409c3a9b71e04a533f3f0c443c8cae3285fa49))


### Features

* add non-livewire queued exports ([3348433](https://github.com/yajra/laravel-datatables-export/commit/3348433249839681e4b368dda0a8d038e3865269))

## [13.2.2](https://github.com/yajra/laravel-datatables-export/compare/v13.2.1...v13.2.2) (2026-07-24)


### Bug Fixes

* **deps:** require patched phpspreadsheet ([9e9366d](https://github.com/yajra/laravel-datatables-export/commit/9e9366d3b5e2a6e05929527c36d309b4a3965db7))

## [13.2.1](https://github.com/yajra/laravel-datatables-export/compare/v13.2.0...v13.2.1) (2026-06-25)


### Bug Fixes

* duplicated export styles ([#95](https://github.com/yajra/laravel-datatables-export/issues/95)) ([7215308](https://github.com/yajra/laravel-datatables-export/commit/721530895ec2c6f8cf9b75f97ca347fd9bcbb3e7))

# [13.2.0](https://github.com/yajra/laravel-datatables-export/compare/v13.1.1...v13.2.0) (2026-05-28)


### Bug Fixes

* pint :robot: ([861ed6c](https://github.com/yajra/laravel-datatables-export/commit/861ed6cf0aee87642b5885b3deb7b08c9bee19f0))


### Features

* make queue configurable ([#94](https://github.com/yajra/laravel-datatables-export/issues/94)) ([629ad35](https://github.com/yajra/laravel-datatables-export/commit/629ad356c7933ecba451baaf319dead40bd45d09))

## [13.1.1](https://github.com/yajra/laravel-datatables-export/compare/v13.1.0...v13.1.1) (2026-03-28)


### Bug Fixes

* Do not replace empty values by null ([d672b6d](https://github.com/yajra/laravel-datatables-export/commit/d672b6d64a1f8fa5fd181e5afc47932fd4d32e07))

# [13.1.0](https://github.com/yajra/laravel-datatables-export/compare/v13.0.0...v13.1.0) (2026-03-28)


### Bug Fixes

* backward compatibility for 8.3 ([e39685e](https://github.com/yajra/laravel-datatables-export/commit/e39685e56ae3a8976f9d3cd346dabe87793f87dd))
* static analysis ([7ab3d22](https://github.com/yajra/laravel-datatables-export/commit/7ab3d222c5879c7a41a795f0c84d3eadbed18a95))
* static analysis - bump deps ([3db1847](https://github.com/yajra/laravel-datatables-export/commit/3db1847f6b307db91bc2eab8abe42fd878544ea0))


### Features

* openspout v5 ([d2585d8](https://github.com/yajra/laravel-datatables-export/commit/d2585d8bf5756d56734ec4dacb4318170ffa1ef9))


## v13.0.0 - 2026-03-25

- Laravel 13.x support
- PHP 8.3+ required (aligned with Yajra DataTables v13)
- Require `yajra/laravel-datatables-buttons` ^13.0
