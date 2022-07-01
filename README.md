# Laravel DataTables Export Plugin

[![Laravel 8|9](https://img.shields.io/badge/Laravel-8|9-orange.svg)](http://laravel.com)
[![Latest Stable Version](https://img.shields.io/packagist/v/yajra/laravel-datatables-export.svg)](https://packagist.org/packages/yajra/laravel-datatables-export)
[![Build Status](https://travis-ci.org/yajra/laravel-datatables-export.svg?branch=master)](https://travis-ci.org/yajra/laravel-datatables-export)
[![Total Downloads](https://img.shields.io/packagist/dt/yajra/laravel-datatables-export.svg)](https://packagist.org/packages/yajra/laravel-datatables-export)
[![License](https://img.shields.io/github/license/mashape/apistatus.svg)](https://packagist.org/packages/yajra/laravel-datatables-export)

This package is a plugin of [Laravel DataTables](https://github.com/yajra/laravel-datatables) for handling server-side exporting using Queue, OpenSpout and Livewire.

## Requirements

- [PHP >=7.4](http://php.net/)
- [Laravel 8+](https://github.com/laravel/framework)
- [Laravel Livewire](https://laravel-livewire.com/)
- [OpenSpout](https://github.com/openspout/openspout)
- [Laravel DataTables 9.x](https://github.com/yajra/laravel-datatables)
- [jQuery DataTables v1.10.x](http://datatables.net/)

## Documentations

- [Laravel DataTables Documentation](http://yajrabox.com/docs/laravel-datatables)

## Quick Installation

`composer require yajra/laravel-datatables-export -W`

The package also requires batch job:

```shell
php artisan queue:batches-table
php artisan migrate
```

#### Service Provider (Optional since Laravel 5.5+)

`Yajra\DataTables\ExportServiceProvider::class`

#### Configuration and Assets (Optional)

`$ php artisan vendor:publish --tag=datatables-export --force`

## Usage

1. Add the export-button livewire component on your view file that uses dataTable class.

```php
<livewire:export-button :table-id="$dataTable->getTableId()" />
```

2. On your `DataTable` class, use `WithExportQueue`

```php
use Yajra\DataTables\WithExportQueue;

class PermissionsDataTable extends DataTable
{
    use WithExportQueue;
    
    ...
}
```

3. Run your queue worker. Ex: `php artisan queue:work`

## Purging exported files

On `app\Console\Kernel.php`, register the purge command

```php
$schedule->command('datatables:purge-export')->weekly();
```

## Export Filename

You can set the export filename by setting the property.

```php
<livewire:export-button :table-id="$dataTable->getTableId()" filename="my-table.xlsx" />
<livewire:export-button :table-id="$dataTable->getTableId()" filename="my-table.csv" />

<livewire:export-button :table-id="$dataTable->getTableId()" :filename="$filename" />
```

## Export Type

You can set the export type by setting the property to `csv` or `xlsx`. Default value is `xlsx`.

```php
<livewire:export-button :table-id="$dataTable->getTableId()" type="xlsx" />
<livewire:export-button :table-id="$dataTable->getTableId()" type="csv" />
```

## Formatting Columns

You can format the column by setting it via Column definition on you DataTable service class.

```php
Column::make('mobile')->exportFormat('00000000000'),
```

The format above will treat mobile numbers as text with leading zeroes.

## Numeric Fields Formatting

The package will auto-detect numeric fields and can be used with custom formats.

```php
Column::make('total')->exportFormat('0.00'),
Column::make('count')->exportFormat('#,##0'),
Column::make('average')->exportFormat('#,##0.00),
```

## Date Fields Formatting

The package will auto-detect date fields when used with a valid format or is a DateTime instance.

```php
Column::make('report_date')->exportFormat('mm/dd/yyyy'),
Column::make('created_at'),
Column::make('updated_at')->exportFormat(NumberFormat::FORMAT_DATE_DATETIME),
```

## Valid Date Formats

Valid date formats can be adjusted on `datatables-export.php` config file.

```php
    'date_formats' => [
        'mm/dd/yyyy',
        NumberFormat::FORMAT_DATE_DATETIME,
        NumberFormat::FORMAT_DATE_YYYYMMDD,
        NumberFormat::FORMAT_DATE_XLSX22,
        NumberFormat::FORMAT_DATE_DDMMYYYY,
        NumberFormat::FORMAT_DATE_DMMINUS,
        NumberFormat::FORMAT_DATE_DMYMINUS,
        NumberFormat::FORMAT_DATE_DMYSLASH,
        NumberFormat::FORMAT_DATE_MYMINUS,
        NumberFormat::FORMAT_DATE_TIME1,
        NumberFormat::FORMAT_DATE_TIME2,
        NumberFormat::FORMAT_DATE_TIME3,
        NumberFormat::FORMAT_DATE_TIME4,
        NumberFormat::FORMAT_DATE_TIME5,
        NumberFormat::FORMAT_DATE_TIME6,
        NumberFormat::FORMAT_DATE_TIME7,
        NumberFormat::FORMAT_DATE_XLSX14,
        NumberFormat::FORMAT_DATE_XLSX15,
        NumberFormat::FORMAT_DATE_XLSX16,
        NumberFormat::FORMAT_DATE_XLSX17,
        NumberFormat::FORMAT_DATE_YYYYMMDD2,
        NumberFormat::FORMAT_DATE_YYYYMMDDSLASH,
    ]
```

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
