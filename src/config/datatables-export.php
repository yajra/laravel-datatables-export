<?php

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

return [

    /**
     * Default export format for date.
     */
    'default_date_format' => 'yyyy-mm-dd',

    /**
     * List of valid date formats to be used for auto-detection.
     */
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
];
