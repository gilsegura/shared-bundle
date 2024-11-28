<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::PHP_83,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
    ]);

    $rectorConfig->skip([
        CatchExceptionNameMatchingTypeRector::class,
        PrivatizeFinalClassMethodRector::class,
        PrivatizeLocalGetterToPropertyRector::class,
        CombineIfRector::class,
    ]);
};