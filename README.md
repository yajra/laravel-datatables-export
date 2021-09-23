# Laravel DataTables Export Plugin

[![Laravel 8.x](https://img.shields.io/badge/Laravel-8.x-orange.svg)](http://laravel.com)
[![Latest Stable Version](https://img.shields.io/packagist/v/yajra/laravel-datatables-export.svg)](https://packagist.org/packages/yajra/laravel-datatables-export)
[![Build Status](https://travis-ci.org/yajra/laravel-datatables-export.svg?branch=master)](https://travis-ci.org/yajra/laravel-datatables-export)
[![Total Downloads](https://img.shields.io/packagist/dt/yajra/laravel-datatables-export.svg)](https://packagist.org/packages/yajra/laravel-datatables-export)
[![License](https://img.shields.io/github/license/mashape/apistatus.svg)](https://packagist.org/packages/yajra/laravel-datatables-export)

This package is a plugin of [Laravel DataTables](https://github.com/yajra/laravel-datatables) for handling server-side exporting using Queue and Livewire.

## Requirements

- [PHP >=7.4](http://php.net/)
- [Laravel 8.x](https://github.com/laravel/framework)
- [Laravel Livewire](https://laravel-livewire.com/)
- [Laravel DataTables 9.x](https://github.com/yajra/laravel-datatables)
- [jQuery DataTables v1.10.x](http://datatables.net/)

## Documentations

- [Laravel DataTables Documentation](http://yajrabox.com/docs/laravel-datatables)

## NOTE

This version is still on experimental stage.

## Quick Installation

`composer require yajra/laravel-datatables-export`

#### Service Provider (Optional on Laravel 5.5)

`Yajra\DataTables\ExportServiceProvider::class`

#### Configuration and Assets (Optional)

`$ php artisan vendor:publish --tag=datatables-export --force`

## Usage

1. Add the export-button livewire component on your view file that uses dataTable class.

```phpt
<livewire:export-button :table-id="$dataTable->getTableAttribute('id')" />
```

2. On your `DataTable` class instance, use `WithExportQueue`

```phpt
use Yajra\DataTables\WithExportQueue;

class PermissionsDataTable extends DataTable
{
    use WithExportQueue;
    
    ...
}
```

3. Run your queue worker via `php artisan queue:work`.

## Contributing

Please see [CONTRIBUTING](https://github.com/yajra/laravel-datatables-export/blob/master/.github/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [aqangeles@gmail.com](mailto:aqangeles@gmail.com) instead of using the issue tracker.

## Credits

- [Arjay Angeles](https://github.com/yajra)
- [All Contributors](https://github.com/yajra/laravel-datatables-export/graphs/contributors)
- [Laravel Daily](https://github.com/LaravelDaily/Laravel-Excel-Export-Import-Large-Files)

## License

The MIT License (MIT). Please see [License File](https://github.com/yajra/laravel-datatables-export/blob/master/LICENSE.md) for more information.
