<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\DependencyInjection;

use ACSEO\TypesenseBundle\DependencyInjection\ACSEOTypesenseExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ACSEOTypesenseExtensionTest extends TestCase
{
    private function buildContainerFromConfig(string $configFile): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension($extension = new ACSEOTypesenseExtension());
        $containerBuilder->setParameter('kernel.debug', true);

        $phpLoader = new PhpFileLoader($containerBuilder,     new FileLocator(__DIR__ . '/../../../src/Resources/config'));
        $phpLoader->load('services.php');

        $yamlLoader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__.'/fixtures'));
        $yamlLoader->load($configFile);

        $extensionConfig = $containerBuilder->getExtensionConfig($extension->getAlias());
        $extension->load($extensionConfig, $containerBuilder);

        return $containerBuilder;
    }

    public function testTypesenseClientDefinition()
    {
        $containerBuilder = $this->buildContainerFromConfig('acseo_typesense.yml');

        $this->assertTrue($containerBuilder->hasDefinition('typesense.client'));

        $clientDefinition = $containerBuilder->findDefinition('typesense.client');
        $this->assertSame('http://localhost:8108', $clientDefinition->getArgument(0));
        $this->assertSame('ACSEO', $clientDefinition->getArgument(1));
    }

    public function testFinderServiceDefinition()
    {
        $containerBuilder = $this->buildContainerFromConfig('acseo_typesense.yml');

        $this->assertTrue($containerBuilder->hasDefinition('typesense.finder'));
        $this->assertTrue($containerBuilder->hasDefinition('typesense.finder.books'));

        $finderBooksDefinition = $containerBuilder->findDefinition('typesense.finder.books');
        $args = $finderBooksDefinition->getArgument(2);

        $this->assertSame('books', $args['typesense_name']);
        $this->assertSame('books', $args['name']);
    }

    public function testFinderServiceDefinitionWithCollectionPrefix()
    {
        $containerBuilder = $this->buildContainerFromConfig('acseo_typesense_collection_prefix.yml');

        $this->assertTrue($containerBuilder->hasDefinition('typesense.finder'));
        $this->assertTrue($containerBuilder->hasDefinition('typesense.finder.books'));

        $finderBooksDefinition = $containerBuilder->findDefinition('typesense.finder.books');
        $args = $finderBooksDefinition->getArgument(2);

        $this->assertSame('acseo_prefix_books', $args['typesense_name']);
        $this->assertSame('books', $args['name']);
    }

    public function testEmbeddingConfigurationWithCustomService()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension($extension = new ACSEOTypesenseExtension());
        $containerBuilder->setParameter('kernel.debug', true);

        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__.'/fixtures'));
        $loader->load('acseo_typesense.yml');

        $extensionConfig = $containerBuilder->getExtensionConfig($extension->getAlias());
        $extension->load($extensionConfig, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('typesense.client'));
        $this->assertTrue($containerBuilder->hasDefinition('typesense.finder.books'));

        // Verify that embed configuration is properly stored at field level
        $managerDefinition = $containerBuilder->getDefinition('typesense.collection_manager');
        $collections = $managerDefinition->getArgument(2);

        $this->assertArrayHasKey('books', $collections);
        $this->assertArrayHasKey('embeddings', $collections['books']['fields']);

        $embeddingsField = $collections['books']['fields']['embeddings'];
        $this->assertArrayHasKey('embed', $embeddingsField);
        $this->assertArrayHasKey('from', $embeddingsField['embed']);
        $this->assertArrayHasKey('model_config', $embeddingsField['embed']);
        $this->assertEquals(['title'], $embeddingsField['embed']['from']);
        $this->assertEquals('openai/test-model', $embeddingsField['embed']['model_config']['model_name']);
        $this->assertEquals('test-api-key', $embeddingsField['embed']['model_config']['api_key']);
        $this->assertEquals('http://test-url:8080', $embeddingsField['embed']['model_config']['url']);

        // Verify that there is NO embed parameter at collection level
        $this->assertArrayNotHasKey('embed', $collections['books']);
    }
}
