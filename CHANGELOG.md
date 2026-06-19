# Changelog

# Version 3.x

# 3.0.0 (unreleased)

* Raise minimum supported PHP version to `8.4.1`
* Support version `1.x` of `phpstan/phpdoc-parser`
* Allow newer versions of `doctrine/collections` to be used
* Increase phpstan level to `6`
* Use better php(stan) typings.
* Update to PHPUnit `13.x`
* Remove hard dependency on `doctrine/annotations`
  Attributes are out for a while now, so we drop the hard dependency on the annotation library. In case it is still
  required, it can still be installed and used by passing the `Reader` to the `JMSParser`.
* Directly create types and pass them to the `PropertyType*` classes instead of creating them inside the class
* Add rector-php analysis

# Version 2.x

# 2.4.0

* Features which landed in this release got backported to `2.2.2`
* ~~`PhpDocParser` can be non-strict: a phpdoc on an untyped property can be considered nullable even if the `@var` annotation doesn't say that~~
* ~~Add `DoctrineMetadataParser` to extract information about properties using Doctrine's entity mapping metadata (ORM/ODM). For custom DBAL types, a type mapping from DBAL to a serialization type hint can be used~~

# 2.3.0

* Drop support for PHP 8.1
* Add support for psalm/phpstan types in docblocks (e.g. `list<string>` or `array<string, int>`)

# 2.2.3

* Make sure the `DoctrineMetadataParser` can handle metadata from version `2.x` and `3.x` of the package `doctrine/orm`

# 2.2.2

* `PhpDocParser` can be non-strict: a phpdoc on an untyped property can be considered nullable even if the `@var` annotation doesn't say that
* Add `DoctrineMetadataParser` to extract information about properties using Doctrine's entity mapping metadata (ORM/ODM). For custom DBAL types, a type mapping from DBAL to a serialization type hint can be used

# 2.2.1

* `SnakeCasePropertyNamingStrategy` now allows grouping uppercase letters (i.e. acronyms/abbreviations) instead of
  inserting a separator between each letter.

# 2.2.0

* Maintenance of this library has been taken over by rebuy. The package has been  renamed from  `liip/metadata-parser` 
  to `rebuy/metadata-parser`. The PHP namespace `Liip\MetadataParser` is kept unchanged for now to allow a smooth transition.

  All entries below this version were released under the original `liip/metadata-parser` package name and are kept here 
  for historical reference.

# 2.1.2

* Improved phpdoc in additional places

# 2.1.1

* Improve phpdoc for better phpstan validation

# 2.1.0

* Add support for enums
* Drop support for PHP 8.0

# 2.0.0

* Removed `PropertyTypeArray`, which is superseded by `PropertyTypeIterable`.
* Removed the deprecated `PropertyTypeIterable::getCollectionClass`. Use `PropertyTypeIterable::getTraversableClass`
* Removed the deprecated `PropertyTypeIterable::isCollection`. Use `PropertyTypeIterable::isTraversable`
* `JMSTypeParser::getTraversableClass` returns `Traversable::class` instead of `Doctrine\Common\Collections\Collection` for general traversable properties.
* Dropped support for PHP 7
* Adjusted code to not trigger warnings with PHP 8.4
* Removed deprecated `getDeserializeFormat` method from date time
* Use Attribute instead of Annotation
  * Replaced `Liip\MetadataParser\ModelParser\LiipMetadataAnnotationParser` with `Liip\MetadataParser\ModelParser\LiipMetadataAttributeParser`
  * Replaced `@Preferred` annotation with the `#[Preferred]` attribute
* Replaced `PropertyCollection::useIdenticalNamingStrategy` static method with the `PropertyNamingStrategyInterface`.
  If you need to change the property naming strategy, remove the call to the static method and instead pass a strategy instance to the parser.
  The library provides two implementations for the property naming strategy:
  * `IdenticalPropertyNamingStrategy`
  * `SnakeCasePropertyNamingStrategy` (default).
* Add support for (union) discriminators and their related JMS attributes `#[UnionDiscriminator]` and `#[Discriminator]`

# Version 1.x

# 1.2.1

* Test with PHP 8.3 - 8.5.

# 1.2.0

* DateTimeOptions now features a list of deserialization formats instead of a single string one. Passing a string instead of an array to its `__construct`or is deprecated, and will be forbidden in the next version
  Similarly, `getDeserializeFormat(): ?string` is deprecated in favor of `getDeserializeFormats(): ?array`
* Added `PropertyTypeIterable`, which generalizes `PropertyTypeArray` to allow merging Collection informations like one would with arrays, including between interfaces and concrete classes
* Deprecated `PropertyTypeArray`, please prefer using `PropertyTypeIterable` instead
* `PropertyTypeArray::isCollection()` and `PropertyTypeArray::getCollectionClass()` are deprecated, including in its child classes, in favor of `isTraversable()` and `getTraversableClass()`
* Added a model parser `VisibilityAwarePropertyAccessGuesser` that tries to guess getter and setter methods for non-public properties.

# 1.1.0

* Drop support for PHP 7.2 and PHP 7.3
* Support doctrine annotations `2.x`

# 1.0.0

No changes since 0.6.1.

# 0.6.1

* Do not ignore methods that have no phpdoc but do have a PHP 8.1 attribute to make them virtual properties.

# 0.6.0

* When running with PHP 8, process attributes in addition to the phpdoc annotations.
* Support doctrine collections
* Support `identical` property naming strategy
* Add support for the `MaxDepth` annotation from JMS

# 0.5.0

* Support JMS Serializer `ReadOnlyProperty` in addition to `ReadOnly` to be compatible with serializer 3.14 and newer.
* Support PHP 8.1 (which makes ReadOnly a reserved keyword)

# 0.4.1

* Allow installation with psr/log 2 and 3, to allow installation with Symfony 6

# 0.4.0

* Handle property type declarations in reflection parser.
* [Bugfix] Upgrade array type with `@var Type[]` annotation
* [Bugfix] When extending class redefines a property, use phpdoc from extending class rather than base class
* [Bugfix] Use correct context for relative class names in inherited properties/methods

# 0.3.0

* Support PHP 8, drop support for PHP 7.1
* Support JMS 3 and drop support for JMS 1
* [BC Break] Only if you wrote your own PropertyMetadata class and overwrote `getCustomInformation`: That method now has the return type `:mixed` specified.

# 0.2.1

* [Bugfix] Look in parent classes and traits for imports

# 0.2.0

* [Feature] Support to track custom metadata

# 0.1.0

* Initial release
