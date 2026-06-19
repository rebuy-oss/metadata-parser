# Upgrade Guide

## UPGRADE FROM 2.x to 3.x

> [!NOTE]
> metadata-parser 3 requires PHP 8.4 or higher

### `doctrine/annotations` is no longer required

`doctrine/annotations` is no longer a hard dependency of this library. Attribute-based
models work out of the box.

* **If your JMS models use PHP attributes** (e.g. `#[JMS\Type('string')]`), no change
  is required. Create the parser without any arguments: `new JMSParser()`.
* **If your JMS models still use Doctrine annotations** (e.g. `@JMS\Type("string")`):
  install `doctrine/annotations` yourself and pass a `Reader` to the parser:

  ```bash
  composer require doctrine/annotations
  ```

  ```php
  use Doctrine\Common\Annotations\AnnotationReader;
  use Liip\MetadataParser\ModelParser\JMSParser;

  $jmsParser = new JMSParser(new AnnotationReader());
  ```
