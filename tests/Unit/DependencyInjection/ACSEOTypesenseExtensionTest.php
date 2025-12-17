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
}
