<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $config) {
    $services = $config->services()
        ->defaults()
        ->public()
    ;

    // typesense.client_prototype
    $services->set('typesense.client_prototype', ACSEO\TypesenseBundle\Client\TypesenseClient::class)
        ->abstract()
        ->args([
            null, // host
            null, // key
            service('typesense.logger')->nullOnInvalid(), // logger
        ])
    ;

    // typesense.finder (generic)
    $services->set('typesense.finder', ACSEO\TypesenseBundle\Finder\CollectionFinder::class)
        ->abstract()
        ->args([
            service('typesense.collection_client'),
            service('doctrine.orm.entity_manager'),
            null, // collection name
        ])
    ;

    // typesense.specificfinder
    $services->set('typesense.specificfinder', ACSEO\TypesenseBundle\Finder\SpecificCollectionFinder::class)
        ->abstract()
        ->args([
            null, // generic finder class
            null, // finder arguments
        ])
    ;

    // autocomplete controller
    $services->set('typesense.autocomplete_controller', ACSEO\TypesenseBundle\Controller\TypesenseAutocompleteController::class)
        ->args([
            null, // routes
        ])
    ;

    // Logger
    $services->set('typesense.logger', ACSEO\TypesenseBundle\Logger\TypesenseLogger::class)
        ->public(false)
    ;
    $services->alias(ACSEO\TypesenseBundle\Logger\TypesenseLogger::class, 'typesense.logger');

    // Data collector
    $services->set('typesense.data_collector', ACSEO\TypesenseBundle\DataCollector\TypesenseDataCollector::class)
        ->public(false)
        ->args([
            service('typesense.logger'),
        ])
        ->tag('data_collector', [
            'template' => '@ACSEOTypesense/DataCollector/typesense.html.twig',
            'id'       => 'typesense',
            'priority' => 300,
        ])
    ;

    // typesense.collection_client
    $services->set('typesense.collection_client', ACSEO\TypesenseBundle\Client\CollectionClient::class)
        ->args([
            service('typesense.client'),
            service('typesense.logger')->nullOnInvalid()
        ])
    ;
    $services->alias(ACSEO\TypesenseBundle\Client\CollectionClient::class, 'typesense.collection_client');
    $services->alias(ACSEO\TypesenseBundle\Client\TypesenseClient::class, 'typesense.client');

    // typesense.collection_manager
    $services->set('typesense.collection_manager', ACSEO\TypesenseBundle\Manager\CollectionManager::class)
        ->args([
            service('typesense.collection_client'),
            service('typesense.transformer.doctrine_to_typesense'),
            null, // collections
        ])
    ;
    $services->alias(ACSEO\TypesenseBundle\Manager\CollectionManager::class, 'typesense.collection_manager');

    // typesense.document_manager
    $services->set('typesense.document_manager', ACSEO\TypesenseBundle\Manager\DocumentManager::class)
        ->args([
            service('typesense.client'),
        ])
    ;

    // doctrine indexer listener
    $services->set('typesense.listener.doctrine_indexer', ACSEO\TypesenseBundle\EventListener\TypesenseIndexer::class)
        ->args([
            service('typesense.collection_manager'),
            service('typesense.document_manager'),
            service('typesense.transformer.doctrine_to_typesense'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'postPersist', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate',  'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove',  'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postRemove', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postFlush',  'priority' => 500, 'connection' => 'default'])
    ;

    // DoctrineToTypesense transformer
    $services->set('typesense.transformer.doctrine_to_typesense', ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer::class)
        ->args([
            null, // collections
            service('property_accessor'),
            service('service_container'),
        ])
    ;

    // commands
    $services->set('typesense.command.create', ACSEO\TypesenseBundle\Command\CreateCommand::class)
        ->public()
        ->tag('console.command')
        ->args([
            service('typesense.collection_manager'),
        ])
    ;

    $services->set('typesense.command.import', ACSEO\TypesenseBundle\Command\ImportCommand::class)
        ->public()
        ->tag('console.command')
        ->args([
            service('doctrine.orm.entity_manager'),
            service('typesense.collection_manager'),
            service('typesense.document_manager'),
            service('typesense.transformer.doctrine_to_typesense'),
        ])
    ;
};
