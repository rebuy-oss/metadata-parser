<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\ManagerRegistry;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\ModelParser\DoctrineMetadataParser;
use Liip\MetadataParser\ModelParser\NamingStrategy\SnakeCasePropertyNamingStrategy;
use Liip\MetadataParser\ModelParser\RawMetadata\RawClassMetadata;
use Liip\MetadataParser\ModelParser\ReflectionParser;
use PHPUnit\Framework\TestCase;
use Tests\Liip\MetadataParser\ModelParser\Model\Course;
use Tests\Liip\MetadataParser\ModelParser\Model\EmptyModel;
use Tests\Liip\MetadataParser\ModelParser\Model\Student;

class DoctrineMetadataParserTest extends TestCase
{
    protected DoctrineMetadataParser $parser;
    protected ReflectionParser $reflectionParser;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();

        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registry->expects(self::atLeastOnce())
            ->method('getManagerForClass')
            ->willReturn($em)
        ;

        $this->parser = new DoctrineMetadataParser($registry);
        $this->reflectionParser = new ReflectionParser();
    }

    public function testEmpty(): void
    {
        $c = new EmptyModel();

        $classMetadata = new RawClassMetadata(\get_class($c));
        $this->parser->parse($classMetadata, new SnakeCasePropertyNamingStrategy());

        $this->assertSame(\get_class($c), $classMetadata->getClassName());
        $this->assertCount(0, $classMetadata->getPropertyCollections(), 'Number of properties should match');
    }

    public function testStringProperty(): void
    {
        $namingStrategy = new SnakeCasePropertyNamingStrategy();
        $classMetadata = new RawClassMetadata(Student::class);
        $this->reflectionParser->parse($classMetadata, $namingStrategy);
        $this->parser->parse($classMetadata, $namingStrategy);

        $property = $classMetadata->getPropertyVariation('lastName');
        self::assertInstanceOf(PropertyTypePrimitive::class, $property->getType(), 'Property Student::lastName was expected to be a string');
    }

    public function testJsonProperty(): void
    {
        $namingStrategy = new SnakeCasePropertyNamingStrategy();
        $classMetadata = new RawClassMetadata(Course::class);
        $this->reflectionParser->parse($classMetadata, $namingStrategy);
        $this->parser->parse($classMetadata, $namingStrategy);

        $property = $classMetadata->getPropertyVariation('tags');
        self::assertInstanceOf(PropertyTypeIterable::class, $property->getType(), 'Property Course::tags was expected to be a JSON array');
    }

    public function testManyToOneAssociation(): void
    {
        $namingStrategy = new SnakeCasePropertyNamingStrategy();
        $classMetadata = new RawClassMetadata(Student::class);
        $this->reflectionParser->parse($classMetadata, $namingStrategy);
        $this->parser->parse($classMetadata, $namingStrategy);

        $property = $classMetadata->getPropertyVariation('mainCourse');
        self::assertInstanceOf(PropertyTypeClass::class, $property->getType(), 'Property Student::mainCourse was expected to be a typed property of class Course');
    }

    public function getEntityManager(): EntityManagerInterface
    {
        $config = new Configuration();
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(sys_get_temp_dir().'/LiipDoctrineTestProxies');
        $config->setProxyNamespace('Tests\Liip\Doctrine\Proxies');
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__.'/Model'], true));

        $conn = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        return new EntityManager($conn, $config);
    }
}
