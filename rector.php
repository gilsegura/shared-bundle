<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::PHP_84,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::EARLY_RETURN,
    ]);

    $rectorConfig->skip([
        CatchExceptionNameMatchingTypeRector::class,
        CombineIfRector::class,
    ]);
};
