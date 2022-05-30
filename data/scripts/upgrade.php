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
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');

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
    // Update tables.
    $sql = <<<'SQL'
DROP INDEX `IDX_20B8FF218CDE5729` ON `stat`;
DROP INDEX `UNIQ_20B8FF218CDE5729F47645AE` ON `stat`;
ALTER TABLE `stat`
    CHANGE `type` `type` VARCHAR(8) NOT NULL,
    CHANGE `url` `url` VARCHAR(1024) NOT NULL COLLATE `latin1_general_cs`,
    CHANGE `modified` `modified` DATETIME NOT NULL;
CREATE INDEX `IDX_20B8FF218CDE5729` ON `stat` (`type`);
CREATE UNIQUE INDEX `UNIQ_20B8FF218CDE5729F47645AE` ON `stat` (`type`, `url`);

DROP INDEX `IDX_5AD22641C44967C5` ON `hit`;
DROP INDEX `IDX_5AD22641ED646567` ON `hit`;
ALTER TABLE `hit`
    ADD `site_id` INT DEFAULT 0 NOT NULL AFTER `entity_name`,
    CHANGE `url` `url` VARCHAR(1024) NOT NULL COLLATE `latin1_general_cs`,
    CHANGE `entity_id` `entity_id` INT DEFAULT 0 NOT NULL,
    CHANGE `entity_name` `entity_name` VARCHAR(190) DEFAULT '' NOT NULL,
    CHANGE `user_id` `user_id` INT DEFAULT 0 NOT NULL,
    CHANGE `ip` `ip` VARCHAR(45) DEFAULT '' NOT NULL,
    CHANGE `referrer` `referrer` VARCHAR(1024) DEFAULT '' NOT NULL COLLATE `latin1_general_cs`,
    CHANGE `user_agent` `user_agent` VARCHAR(1024) DEFAULT '' NOT NULL COLLATE `latin1_general_ci`,
    CHANGE `accept_language` `accept_language` VARCHAR(190) DEFAULT '' NOT NULL COLLATE `latin1_general_ci`;
CREATE INDEX `IDX_5AD22641F6BD1646` ON `hit` (`site_id`);
CREATE INDEX `IDX_5AD22641C44967C5` ON `hit` (`user_agent`);
CREATE INDEX `IDX_5AD22641ED646567` ON `hit` (`referrer`);
SQL;
    $connection->executeStatement($sql);

    // Get list of site ids/slugs.
    $siteSlugs = $api->search('sites', [], ['returnScalar' => 'slug'])->getContent();

    // Fill sites.
    foreach ($siteSlugs as $siteId => $siteSlug) {
        $bind = ['site_id' => $siteId, 'slug_eq' => "/s/$siteSlug", 'slug_like' => "/s/$siteSlug/%"];
        $types = ['site_id' => \Doctrine\DBAL\ParameterType::INTEGER, 'slug_eq' => \Doctrine\DBAL\ParameterType::STRING, 'slug_like' => \Doctrine\DBAL\ParameterType::STRING];
        $sql = <<<'SQL'
UPDATE `hit`
SET
    `hit`.`site_id` = :site_id
WHERE
    (`hit`.`url` = :slug_eq OR `hit`.`url` LIKE :slug_like)
    AND `hit`.`site_id` = 0
;
SQL;
        $connection->executeStatement($sql, $bind, $types);
    }

    // Fill site pages.
    foreach ($siteSlugs as $siteId => $siteSlug) {
        // Get list of site page ids/slugs.
        $pageSlugs = $api->search('site_pages', ['site_id' => $siteId], ['returnScalar' => 'slug'])->getContent();
        foreach ($pageSlugs as $pageId => $pageSlug) {
            $bind = ['site_id' => $siteId, 'page_id' => $pageId, 'page_url' => "/s/$siteSlug/page/$pageSlug"];
            $types = ['site_id' => \Doctrine\DBAL\ParameterType::INTEGER, 'page_id' => \Doctrine\DBAL\ParameterType::INTEGER, 'page_url' => \Doctrine\DBAL\ParameterType::STRING];
            $sql = <<<'SQL'
    UPDATE `hit`
    SET
        `hit`.`entity_name` = "site_pages",
        `hit`.`entity_id` = :page_id
    WHERE
        `hit`.`url` = :page_url
        AND `hit`.`site_id` = :site_id
        AND `hit`.`entity_name` = ""
        AND `hit`.`entity_id` = 0
    ;
    SQL;
            $connection->executeStatement($sql, $bind, $types);

            unset($bind['site_id'], $types['site_id']);
            $sql = <<<'SQL'
    UPDATE `stat`
    SET
        `stat`.`entity_name` = "site_pages",
        `stat`.`entity_id` = :page_id
    WHERE
        `stat`.`url` = :page_url
        AND `stat`.`entity_name` = ""
        AND `stat`.`entity_id` = 0
    ;
    SQL;
            $connection->executeStatement($sql, $bind, $types);
        }
    }
}
