<?php declare(strict_types=1);

namespace Statistics;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
// $config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
// $connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
// $plugins = $services->get('ControllerPluginManager');
// $api = $plugins->get('api');

if (version_compare($oldVersion, '3.3.4.2', '<')) {
    $settings->set('statistics_public_allow_browse', $settings->get('statistics_public_allow_browse_pages', false));
    $settings->delete('statistics_public_allow_browse_pages');
    $settings->delete('statistics_public_allow_browse_resources');
    $settings->delete('statistics_public_allow_browse_downloads');
    $settings->delete('statistics_public_allow_browse_fields');
}
