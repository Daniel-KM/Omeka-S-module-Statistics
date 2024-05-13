<?php declare(strict_types=1);

namespace Statistics;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.57')) {
    $message = new \Omeka\Stdlib\Message(
        $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
        'Common', '3.4.57'
    );
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
}

if (version_compare($oldVersion, '3.3.4.2', '<')) {
    $settings->set('statistics_public_allow_browse', $settings->get('statistics_public_allow_browse_pages', false));
    $settings->delete('statistics_public_allow_browse_pages');
    $settings->delete('statistics_public_allow_browse_resources');
    $settings->delete('statistics_public_allow_browse_downloads');
    $settings->delete('statistics_public_allow_browse_fields');

    $message = new Message(
        'To control access to files, you must add a rule in file .htaccess at the root of Omeka. See %sreadme%s.', // @translate
        '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Access" target="_blank" rel="noopener">', '</a>'
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
    ADD `site_id` INT DEFAULT 0 NOT NULL AFTER `entity_name`;
ALTER TABLE `hit`
    CHANGE `url` `url` VARCHAR(1024) NOT NULL COLLATE `latin1_general_cs`,
    CHANGE `entity_id` `entity_id` INT DEFAULT 0 NOT NULL,
    CHANGE `entity_name` `entity_name` VARCHAR(190) DEFAULT '' NOT NULL,
    CHANGE `user_id` `user_id` INT DEFAULT 0 NOT NULL,
    CHANGE `ip` `ip` VARCHAR(45) DEFAULT '' NOT NULL,
    CHANGE `query` `query` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)',
    CHANGE `referrer` `referrer` VARCHAR(1024) DEFAULT '' NOT NULL COLLATE `latin1_general_cs`,
    CHANGE `user_agent` `user_agent` VARCHAR(1024) DEFAULT '' NOT NULL COLLATE `latin1_general_ci`,
    CHANGE `accept_language` `accept_language` VARCHAR(190) DEFAULT '' NOT NULL COLLATE `latin1_general_ci`;
CREATE INDEX `IDX_5AD22641F6BD1646` ON `hit` (`site_id`);
CREATE INDEX `IDX_5AD22641C44967C5` ON `hit` (`user_agent`);
CREATE INDEX `IDX_5AD22641ED646567` ON `hit` (`referrer`);
SQL;
    $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
    foreach ($sqls as $sql) {
        try {
            $connection->executeStatement($sql);
        } catch (\Exception $e) {
            $messenger->addError($e->getMessage());
        }
    }

    // Url decode queries and parse them. Paginate them, because query may be big.
    $requestGet = [];
    // Api cannot be used during upgrade.
    // $hitIds = $api->search('hits', ['not_empty' => 'query'], ['returnScalar' => 'id'])->getContent();
    $hitIds = $connection->executeQuery('SELECT `hit`.`id` FROM `hit` WHERE `hit`.`query` IS NOT NULL AND `hit`.`query` != "";')->fetchFirstColumn();
    $sql = <<<'SQL'
UPDATE `hit`
SET `hit`.`query` = :query
WHERE `hit`.`id` = :id;
SQL;
    foreach (array_chunk($hitIds, 100) as $chunk) {
        // $queries = $api->search('hits', ['id' => $chunk], ['returnScalar' => 'query'])->getContent();
        $queries = $connection->executeQuery('SELECT `hit`.`id`, `hit`.`query` FROM `hit` WHERE `hit`.`id` IN (:ids)', ['ids' => $chunk], ['ids' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAllKeyValue();
        foreach ($queries as $id => $query) {
            if (is_null($query) || $query === '') {
                $query = null;
            } else {
                // url_decode() is automatically run.
                parse_str($query, $requestGet);
                $query = $requestGet ? json_encode($requestGet) : null;
            }
            $connection->executeStatement($sql, ['id' => $id, 'query' => $query], ['id' => \Doctrine\DBAL\ParameterType::INTEGER, 'query' => \Doctrine\DBAL\ParameterType::STRING]);
        }
    }

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

    $message = new Message(
        'There are now analytics by period for properties.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.3.5', '<')) {
    $message = new Message(
        'There are now statistics about resources and values.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.7', '<')) {
    // Fill site pages in hit.
    $sql = <<<'SQL'
UPDATE `hit`
INNER JOIN `site_page`
    ON `site_page`.`slug` = SUBSTRING(`hit`.`url`, LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 4)) + 2)
INNER JOIN `site`
    ON `site`.`slug` = SUBSTRING(`hit`.`url`, LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 2)) + 2, LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 3)) - LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 2)) - 1)
        AND `site`.`id` = `site_page`.`site_id`
SET
    `entity_name` = "site_pages",
    `entity_id` = `site_page`.`id`
WHERE `entity_name` = ""
    AND `entity_id` = 0
    AND `url` LIKE "/s/%/page/%"
;
SQL;
    $connection->executeStatement($sql);

    // Fill site pages in stat.
    $sql = <<<'SQL'
UPDATE `stat`
INNER JOIN `site_page`
    ON `site_page`.`slug` = SUBSTRING(`stat`.`url`, LENGTH(SUBSTRING_INDEX(`stat`.`url`, "/", 4)) + 2)
INNER JOIN `site`
    ON `site`.`slug` = SUBSTRING(`stat`.`url`, LENGTH(SUBSTRING_INDEX(`stat`.`url`, "/", 2)) + 2, LENGTH(SUBSTRING_INDEX(`stat`.`url`, "/", 3)) - LENGTH(SUBSTRING_INDEX(`stat`.`url`, "/", 2)) - 1)
        AND `site`.`id` = `site_page`.`site_id`
SET
    `entity_name` = "site_pages",
    `entity_id` = `site_page`.`id`
WHERE `entity_name` = ""
    AND `entity_id` = 0
    AND `url` LIKE "/s/%/page/%"
;
SQL;
    $connection->executeStatement($sql);

    // Fill sites in hit.
    $sql = <<<'SQL'
UPDATE `hit`
INNER JOIN `site`
    ON `site`.`slug` = SUBSTRING(`hit`.`url`, LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 2)) + 2, LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 3)) - LENGTH(SUBSTRING_INDEX(`hit`.`url`, "/", 2)) - 1)
SET
    `site_id` = `site`.`id`
WHERE `site_id` = 0
    AND `url` LIKE "/s/%/page/%"
;
SQL;
    $connection->executeStatement($sql);
}
