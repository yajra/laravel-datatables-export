<?php

declare(strict_types=1);

namespace Yajra\DataTables\Support;

use OpenSpout\Common\Entity\Style\Style;

/**
 * OpenSpout 4 uses mutable {@see Style::setFormat()}, v5 uses immutable {@see Style::withFormat()}.
 * Excluded from PHPStan: analysis runs against one installed major at a time.
 */
final class OpenSpoutExportStyle
{
    public static function forFormat(string $format): Style
    {
        $style = new Style;

        if (method_exists($style, 'withFormat')) {
            return $style->withFormat($format);
        }

        return $style->setFormat($format);
    }
}
