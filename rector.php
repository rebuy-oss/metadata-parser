<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php83\Rector\Class_\ReadOnlyAnonymousClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Class_\ScalarTypedPropertyFromJMSSerializerAttributeTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddParamTypeBasedOnPHPUnitDataProviderRector::class,
        ReadOnlyAnonymousClassRector::class,
        ReadOnlyPropertyRector::class => [
            __DIR__.'/tests',
        ],
        ScalarTypedPropertyFromJMSSerializerAttributeTypeRector::class => [
            __DIR__.'/tests',
        ],
        StringReturnTypeFromStrictScalarReturnsRector::class => [
            __DIR__.'/tests',
        ],
        ReturnTypeFromReturnNewRector::class => [
            __DIR__.'/tests',
        ],

        // Ignored since some properties have to stay untyped for the tests to work as intended.
        __DIR__.'/tests/ModelParser/Fixtures',
        __DIR__.'/tests/ModelParser/Model',
    ])
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withPhpSets()
    ->withSets([SetList::TYPE_DECLARATION])
    ->withImportNames(importShortClasses: false)
;
