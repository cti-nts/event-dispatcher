<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Visibility\Rector\ClassMethod\ExplicitPublicClassMethodRector;

return static function (RectorConfig $rectorConfig): void {
    // $rectorConfig->importNames();

    $rectorConfig->paths([
        __DIR__ . '/Application',
        __DIR__ . '/config',
        __DIR__ . '/Infrastructure',
        __DIR__ . '/tests'
    ]);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
    ]);

    // skip rules
    $rectorConfig->skip([
        CatchExceptionNameMatchingTypeRector::class,
        EncapsedStringsToSprintfRector::class,
        ExplicitBoolCompareRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        RemoveNonExistingVarAnnotationRector::class,
        UseClassKeywordForClassNameResolutionRector::class,
    ]);

    $rectorConfig->rules([
        ExplicitPublicClassMethodRector::class,
        PrivatizeLocalGetterToPropertyRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
    ]);
};
