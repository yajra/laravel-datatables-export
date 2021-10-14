# Laravel DataTables Export Plugin CHANGELOG.

## UNRELEASED

## v0.6.0 - 10-13-2021

- Use box/spout instead of Laravel Excel. #13
- Add support for number format and text with leading zeroes.

## v0.5.2 - 10-13-2021

- Fix export when used with Eloquent Builder. #12
- Fix support for Query Builder.

## v0.5.1 - 10-13-2021

- Fix render method override #11

## v0.5.0 - 10-12-2021

- Passing attributes with self::class #10
- Allow reconstruction of DataTable instance with custom attributes set via controller.

## v0.4.0 - 10-12-2021

- Add cast to array for variable $row. #9
- Add support for DB Query.

## v0.3.0 - 10-07-2021

- Add batch job name. #6
- Set default export to xlsx. #7

## v0.2.0 - 10-06-2021

- Add option to set exportFormat from Column builder. #3
- Add ability to auto-detect date fields / formats. #3

## v0.1.0 - 10-04-2021

- Initial version release.
- Queued Export using Laravel Excel.
