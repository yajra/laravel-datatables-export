# Laravel DataTables Export Plugin CHANGELOG.

## UNRELEASED

## v0.14.1 - 09-14-2023

- fix: return null value for empty/null strings #53

## v0.14.0 - 09-08-2022

- Fix export on columns from eloquent relation #29
- Add auto download feature #30

## v0.13.0 - 08-04-2022

- Performance improvement #27

## v0.12.0 - 07-06-2022

- Add feature to set sheet name #25

## v0.11.1 - 07-05-2022

- Fix compatibility with PHP7.4 and DataTables v9 #24

## v0.11.0 - 07-04-2022

- Add github actions ci/cd #21
- Add phpstan 
- Add php-cs-fixer 
- Add basic tests 
- Fix prioritization of expected formats 
- Fix days old purge config is always 1 day

## v0.10.0 - 07-04-2022

- Add ability to force cell as text. #22

## v0.9.0 - 07-01-2022

- Migrate to OpenSpout v3 #20

## v0.8.0 - 06-29-2022

- Add option to use cursor or lazy to iterate with the results #17
- Fix export error when path does not exist yet
- Fix [Feature Request] Make storage path configurable #16

## v0.7.1 - 06-15-2022

- Use lazy to fix support with eager loaded relations.

## v0.7.0 - 05-13-2022

- Add support for L9

## v0.6.1 - 10-14-2021

- Fix export title, strip html tags.
- Use CellTypeHelper to determine date values.

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
