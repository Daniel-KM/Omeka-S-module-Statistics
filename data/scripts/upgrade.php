<?php declare(strict_types=1);

namespace Statistics;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;

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
$connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
// $plugins = $services->get('ControllerPluginManager');
// $api = $plugins->get('api');

if (version_compare($oldVersion, '3.3.4.2', '<')) {
    $settings->set('statistics_public_allow_browse', $settings->get('statistics_public_allow_browse_pages', false));
    $settings->delete('statistics_public_allow_browse_pages');
    $settings->delete('statistics_public_allow_browse_resources');
    $settings->delete('statistics_public_allow_browse_downloads');
    $settings->delete('statistics_public_allow_browse_fields');

    $messenger = new Messenger;
    $message = new Message(
        'To control access to files, you must add a rule in file .htaccess at the root of Omeka. See %sreadme%s.', // @translate
        '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-AccessResource" target="_blank">', '</a>'
    );
    $message->setEscapeHtml(false);
    $messenger->addWarning($message);
}

if (version_compare($oldVersion, '3.3.4.3', '<')) {
    // Update table.
    $sql = <<<'SQL'
DROP INDEX `IDX_5AD22641ED646567` ON `hit`;
DROP INDEX `IDX_5AD22641C44967C5` ON `hit`;
ALTER TABLE `hit`
    ADD `site_id` INT DEFAULT 0 NOT NULL AFTER `entity_name`,
    CHANGE `entity_id` `entity_id` INT DEFAULT 0 NOT NULL,
    CHANGE `entity_name` `entity_name` VARCHAR(190) DEFAULT '' NOT NULL,
    CHANGE `user_id` `user_id` INT DEFAULT 0 NOT NULL,
    CHANGE `ip` `ip` VARCHAR(45) DEFAULT '' NOT NULL,
    CHANGE `referrer` `referrer` VARCHAR(1024) DEFAULT '' NOT NULL,
    CHANGE `user_agent` `user_agent` VARCHAR(1024) DEFAULT '' NOT NULL,
    CHANGE `accept_language` `accept_language` VARCHAR(190) DEFAULT '' NOT NULL,
    CHANGE `created` `created` DATETIME NOT NULL;
CREATE INDEX `IDX_5AD22641F6BD1646` ON `hit` (`site_id`);
CREATE INDEX `IDX_5AD22641ED646567` ON `hit` (`referrer`);
CREATE INDEX `IDX_5AD22641C44967C5` ON `hit` (`user_agent`);
SQL;
    $connection->executeStatement($sql);
}
