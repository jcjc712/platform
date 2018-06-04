<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SubresourceContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var SubresourceContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new SubresourceContext($this->configProvider, $this->metadataProvider);
    }

    /**
     * @param array $data
     *
     * @return Config
     */
    protected function getConfig(array $data = [])
    {
        $result = new Config();
        foreach ($data as $sectionName => $config) {
            $result->set($sectionName, $config);
        }

        return $result;
    }

    public function testParentClassName()
    {
        self::assertNull($this->context->getParentClassName());

        $this->context->setParentClassName('test');
        self::assertEquals('test', $this->context->getParentClassName());
        self::assertEquals('test', $this->context->get(SubresourceContext::PARENT_CLASS_NAME));
    }

    public function testParentId()
    {
        self::assertNull($this->context->getParentId());

        $this->context->setParentId('test');
        self::assertEquals('test', $this->context->getParentId());
        self::assertEquals('test', $this->context->get(SubresourceContext::PARENT_ID));
    }

    public function testAssociationName()
    {
        self::assertNull($this->context->getAssociationName());

        $this->context->setAssociationName('test');
        self::assertEquals('test', $this->context->getAssociationName());
        self::assertEquals('test', $this->context->get(SubresourceContext::ASSOCIATION));
    }

    public function testIsCollection()
    {
        self::assertFalse($this->context->isCollection());
        self::assertTrue($this->context->has(SubresourceContext::COLLECTION));
        self::assertFalse($this->context->get(SubresourceContext::COLLECTION));

        $this->context->setIsCollection(true);
        self::assertTrue($this->context->isCollection());
        self::assertTrue($this->context->get(SubresourceContext::COLLECTION));
    }

    public function testParentEntity()
    {
        self::assertNull($this->context->getParentEntity());
        self::assertFalse($this->context->hasParentEntity());

        $entity = new \stdClass();
        $this->context->setParentEntity($entity);
        self::assertSame($entity, $this->context->getParentEntity());
        self::assertSame($entity, $this->context->get(SubresourceContext::PARENT_ENTITY));
        self::assertTrue($this->context->hasParentEntity());

        $this->context->setParentEntity(null);
        self::assertNull($this->context->getParentEntity());
        self::assertTrue($this->context->hasParentEntity());
    }

    public function testGetParentConfigExtras()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        self::assertNull($this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS));

        $expectedParentConfigExtras = [
            new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
            new CustomizeLoadedDataConfigExtra(),
            new DataTransformersConfigExtra(),
            new FilterFieldsConfigExtra(
                [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
            )
        ];
        self::assertEquals(
            $expectedParentConfigExtras,
            $this->context->getParentConfigExtras()
        );
        self::assertEquals(
            $expectedParentConfigExtras,
            $this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS)
        );
    }

    public function testSetParentConfigExtras()
    {
        $this->context->setParentConfigExtras([new EntityDefinitionConfigExtra('get_list')]);
        self::assertEquals(
            [new EntityDefinitionConfigExtra('get_list')],
            $this->context->getParentConfigExtras()
        );
        self::assertEquals(
            [new EntityDefinitionConfigExtra('get_list')],
            $this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS)
        );
    }

    public function testRemoveParentConfigExtras()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->context->setParentConfigExtras([]);
        self::assertNull($this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS));

        $expectedParentConfigExtras = [
            new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
            new CustomizeLoadedDataConfigExtra(),
            new DataTransformersConfigExtra(),
            new FilterFieldsConfigExtra(
                [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
            )
        ];
        self::assertEquals(
            $expectedParentConfigExtras,
            $this->context->getParentConfigExtras()
        );
        self::assertEquals(
            $expectedParentConfigExtras,
            $this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Config\ConfigExtraInterface".
     */
    public function testSetInvalidParentConfigExtras()
    {
        $this->context->setParentConfigExtras(['test']);
    }

    public function testLoadParentConfig()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new CustomizeLoadedDataConfigExtra(),
                    new DataTransformersConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasParentConfig());

        self::assertEquals($config, $this->context->getParentConfig()); // load config
        self::assertTrue($this->context->hasParentConfig());
        self::assertTrue($this->context->has(SubresourceContext::PARENT_CONFIG));
        self::assertEquals($config, $this->context->get(SubresourceContext::PARENT_CONFIG));

        // test that a config is loaded only once
        self::assertEquals($config, $this->context->getParentConfig());
    }

    public function testLoadParentConfigWhenExceptionOccurs()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';
        $exception = new \RuntimeException('some error');

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new CustomizeLoadedDataConfigExtra(),
                    new DataTransformersConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willThrowException($exception);

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasParentConfig());

        try {
            $this->context->getParentConfig(); // load config
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasParentConfig());
        self::assertTrue($this->context->has(SubresourceContext::PARENT_CONFIG));
        self::assertNull($this->context->get(SubresourceContext::PARENT_CONFIG));

        // test that a config is loaded only once
        self::assertNull($this->context->getParentConfig());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parent entity class name must be set in the context before a configuration is loaded.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadParentConfigWhenNoParentClassName()
    {
        $this->context->getParentConfig();
    }

    public function testParentConfigWhenItIsSetExplicitly()
    {
        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setParentConfig($config);

        self::assertTrue($this->context->hasParentConfig());
        self::assertEquals($config, $this->context->getParentConfig());
        self::assertTrue($this->context->has(SubresourceContext::PARENT_CONFIG));
        self::assertEquals($config, $this->context->get(SubresourceContext::PARENT_CONFIG));

        // test remove config
        $this->context->setParentConfig();
        self::assertFalse($this->context->hasParentConfig());
    }

    public function testGetParentMetadataExtras()
    {
        self::assertNull($this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS));

        self::assertEquals(
            [],
            $this->context->getParentMetadataExtras()
        );
        self::assertEquals(
            [],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );
    }

    public function testGetParentMetadataExtrasWhenActionExistsInContext()
    {
        $action = 'test_action';
        $this->context->setAction($action);

        self::assertNull($this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS));

        self::assertEquals(
            [new ActionMetadataExtra($action)],
            $this->context->getParentMetadataExtras()
        );
        self::assertEquals(
            [new ActionMetadataExtra($action)],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );
    }

    public function testSetParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras([new TestMetadataExtra('test')]);
        self::assertEquals(
            [new TestMetadataExtra('test')],
            $this->context->getParentMetadataExtras()
        );
        self::assertEquals(
            [new TestMetadataExtra('test')],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );
    }

    public function testRemoveParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras([]);
        self::assertNull($this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS));
        self::assertEquals(
            [],
            $this->context->getParentMetadataExtras()
        );
        self::assertEquals(
            [],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".
     */
    public function testSetInvalidParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras(['test']);
    }

    public function testLoadParentMetadata()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new CustomizeLoadedDataConfigExtra(),
                    new DataTransformersConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasParentMetadata());

        self::assertSame($metadata, $this->context->getParentMetadata()); // load metadata
        self::assertTrue($this->context->hasParentMetadata());
        self::assertTrue($this->context->has(SubresourceContext::PARENT_METADATA));
        self::assertSame($metadata, $this->context->get(SubresourceContext::PARENT_METADATA));

        self::assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getParentMetadata());
    }

    public function testLoadParentMetadataWhenNoParentClassName()
    {
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->context->getParentMetadata());
        self::assertTrue($this->context->hasParentMetadata());
    }

    public function testLoadParentMetadataWhenExceptionOccurs()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';
        $exception = new \RuntimeException('some error');

        $config = new EntityDefinitionConfig();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new CustomizeLoadedDataConfigExtra(),
                    new DataTransformersConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willThrowException($exception);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasParentMetadata());

        try {
            $this->context->getParentMetadata(); // load metadata
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasParentMetadata());
        self::assertTrue($this->context->has(SubresourceContext::PARENT_METADATA));
        self::assertNull($this->context->get(SubresourceContext::PARENT_METADATA));

        self::assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        self::assertNull($this->context->getParentMetadata());
    }

    public function testMetadataWhenItIsSetExplicitly()
    {
        $metadata = new EntityMetadata();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->context->setParentMetadata($metadata);

        self::assertTrue($this->context->hasParentMetadata());
        self::assertSame($metadata, $this->context->getParentMetadata());
        self::assertTrue($this->context->has(SubresourceContext::PARENT_METADATA));
        self::assertSame($metadata, $this->context->get(SubresourceContext::PARENT_METADATA));

        // test remove metadata
        $this->context->setParentMetadata();
        self::assertFalse($this->context->hasParentMetadata());
    }
}
