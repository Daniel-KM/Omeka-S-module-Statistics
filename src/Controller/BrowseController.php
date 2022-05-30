<?php declare(strict_types=1);

namespace Statistics\Controller;

use Doctrine\DBAL\Connection;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Stdlib\Message;
use Statistics\Entity\Stat;

/**
 * Controller to browse Stats.
 */
class BrowseController extends AbstractActionController
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Forward to the summary controller.
     */
    public function indexAction()
    {
        return $this->forward()->dispatch(SummaryController::class);
    }

    /**
     * Redirect to the 'by-page' action.
     */
    public function browseAction()
    {
        $query = $this->params()->fromRoute();
        $query['action'] = 'by-page';
        $isSiteRequest = $this->status()->isSiteRequest();
        return $this->redirect()->toRoute($isSiteRequest ? 'site/statistics/default' : 'admin/statistics/default', $query);
    }

    public function bySiteAction()
    {
        // FIXME Stats by site has not been fully checked.
        // TODO Add a column "site_id" in table "stat".
        // TODO Factorize with byItemSetAction?
        // TODO Move the process into view helper Statistic.
        // TODO Enlarge byItemSet to byResource (since anything is resource).

        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        if ($userStatus === 'anonymous') {
            $whereStatus = "\nAND hit.user_id = 0";
        } elseif ($userStatus === 'identified') {
            $whereStatus = "\nAND hit.user_id <> 0";
        } else {
            $whereStatus = '';
        }

        $query = $this->params()->fromQuery();
        $year = empty($query['year']) || !is_numeric($query['year']) ? null : (int) $query['year'];
        $month = empty($query['month']) || !is_numeric($query['month']) ? null : (int) $query['month'];

        $appendDates = $this->whereDate($year, $month, [], []);
        $bind = $appendDates['bind'];
        $types = $appendDates['types'];
        $force = $appendDates['force'];
        $whereYear = $appendDates['whereYear'];
        $whereMonth = $appendDates['whereMonth'];

        $sql = <<<SQL
SELECT hit.site_id, COUNT(hit.id) AS total_hits
FROM hit hit $force
WHERE hit.entity_name = "items"$whereStatus$whereYear$whereMonth
GROUP BY hit.site_id
ORDER BY total_hits
;
SQL;
        $hitsPerSite = $this->connection->executeQuery($sql, $bind, $types)->fetchAllKeyValue();

        $removedSite = $this->translate('[Removed site #%d]'); // @translate

        $api = $this->api();
        $results = [];
        foreach ($hitsPerSite as $siteId => $hits) {
            try {
                $siteTitle = $api->read('sites', ['id' => $siteId])->getContent()->title();
            } catch (\Exception $e) {
                $siteTitle = sprintf($removedSite, $siteId);
            }
            $results[] = [
                'site' => $siteTitle,
                'hits' => $hits,
                'hitsInclusive' => '',
            ];
        }

        $this->paginator(count($results));

        // TODO Manage special sort fields.
        $sortBy = $query['sort_by'] ?? null;
        if (empty($sortBy) || !in_array($sortBy, ['site', 'hits', 'hitsInclusive'])) {
            $sortBy = 'hitsInclusive';
        }
        $sortOrder = $query['sort_order'] ?? null;
        if (empty($sortOrder) || $sortOrder !== 'asc') {
            $sortOrder = 'desc';
        }

        usort($results, function ($a, $b) use ($sortBy, $sortOrder) {
            $cmp = strnatcasecmp($a[$sortBy], $b[$sortBy]);
            return $sortOrder === 'desc' ? -$cmp : $cmp;
        });

        $years = $this->listYears(null, null, false);

        $view = new ViewModel([
            'type' => 'site',
            'results' => $results,
            'years' => $years,
            'yearFilter' => $year,
            'monthFilter' => $month,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-site' : 'statistics/site/browse/by-site');
    }

    /**
     * Browse rows by page action.
     */
    public function byPageAction()
    {
        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        $defaultSorts = ['anonymous' => 'total_hits_anonymous', 'identified' => 'total_hits_identified'];
        $userStatusBrowse = $defaultSorts[$userStatus] ?? 'total_hits';
        $this->setBrowseDefaults($userStatusBrowse);

        $query = $this->params()->fromQuery();
        $query['type'] = Stat::TYPE_PAGE;
        $query['user_status'] = $userStatus;

        $response = $this->api()->search('stats', $query);
        $this->paginator($response->getTotalResults());
        $stats = $response->getContent();

        $view = new ViewModel([
            'resources' => $stats,
            'stats' => $stats,
            'userStatus' => $userStatus,
            'type' => Stat::TYPE_PAGE,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-stat' : 'statistics/site/browse/by-stat');
    }

    /**
     * Browse rows by resource action.
     */
    public function byResourceAction()
    {
        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        $defaultSorts = ['anonymous' => 'total_hits_anonymous', 'identified' => 'total_hits_identified'];
        $userStatusBrowse = $defaultSorts[$userStatus] ?? 'total_hits';
        $this->setBrowseDefaults($userStatusBrowse);

        $query = $this->params()->fromQuery();
        $query['type'] = Stat::TYPE_RESOURCE;
        $query['user_status'] = $userStatus;

        $response = $this->api()->search('stats', $query);
        $this->paginator($response->getTotalResults());
        $stats = $response->getContent();

        $view = new ViewModel([
            'resources' => $stats,
            'stats' => $stats,
            'userStatus' => $userStatus,
            'type' => Stat::TYPE_RESOURCE,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-stat' : 'statistics/site/browse/by-stat');
    }

    /**
     * Browse rows by download action.
     */
    public function byDownloadAction()
    {
        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        $defaultSorts = ['anonymous' => 'total_hits_anonymous', 'identified' => 'total_hits_identified'];
        $userStatusBrowse = $defaultSorts[$userStatus] ?? 'total_hits';
        $this->setBrowseDefaults($userStatusBrowse);

        $query = $this->params()->fromQuery();
        $query['type'] = Stat::TYPE_DOWNLOAD;
        $query['user_status'] = $userStatus;

        $response = $this->api()->search('stats', $query);
        $this->paginator($response->getTotalResults());
        $stats = $response->getContent();

        $view = new ViewModel([
            'resources' => $stats,
            'stats' => $stats,
            'userStatus' => $userStatus,
            'type' => Stat::TYPE_DOWNLOAD,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-stat' : 'statistics/site/browse/by-stat');
    }

    /**
     * Browse rows by field action.
     */
    public function byFieldAction()
    {
        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        $query = $this->params()->fromQuery();

        $field = $query['field'] ?? null;
        if (empty($field) || !in_array($field, ['referrer', 'query', 'user_agent', 'accept_language'])) {
            $field = 'referrer';
            $query['field'] = $field;
        }

        $query = $this->defaultSort($query, [$field, 'hits'], 'hits');

        $currentPage = isset($query['page']) ? (int) $query['page'] : null;
        $resourcesPerPage = $isAdminRequest
            ? (int) $settings->get('pagination_per_page', 25)
            : (int) $this->siteSettings()->get('pagination_per_page', 25);

        // Don't use api, because this is a synthesis, not a list of resources.
        /** @var \Statistics\View\Helper\Statistic $statistic */
        $statistic = $this->viewHelpers()->get('statistic');
        $results = $statistic->frequents($query, $currentPage, $resourcesPerPage);
        $totalResults = $statistic->countFrequents($query);
        $totalHits = $this->api()->search('hits', ['user_status' => $userStatus])->getTotalResults();
        $totalNotEmpty = $this->api()->search('hits', ['field' => $field, 'user_status' => $userStatus, 'not_empty' => $field])->getTotalResults();
        $this->paginator($totalResults);

        switch ($field) {
            default:
            case 'referrer':
                $labelField = $this->translate('External Referrers'); // @translate
                break;
            case 'query':
                $labelField = $this->translate('Queries'); // @translate
                break;
            case 'user_agent':
                $labelField = $this->translate('Browsers'); // @translate
                break;
            case 'accept_language':
                $labelField = $this->translate('Accepted Languages'); // @translate
                break;
        }

        $view = new ViewModel([
            'type' => 'field',
            'field' => $field,
            'labelField' => $labelField,
            'results' => $results,
            'totalHits' => $totalHits,
            'totalNotEmpty' => $totalNotEmpty,
            'userStatus' => $userStatus,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-field' : 'statistics/site/browse/by-field');
    }

    public function byItemSetAction()
    {
        // FIXME Stats by item set has not been fully checked.
        // TODO Move the process into view helper Statistic.
        // TODO Enlarge byItemSet to byResource (since anything is resource).

        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        if ($userStatus === 'anonymous') {
            $whereStatus = "\nAND hit.user_id = 0";
        } elseif ($userStatus === 'identified') {
            $whereStatus = "\nAND hit.user_id <> 0";
        } else {
            $whereStatus = '';
        }

        $query = $this->params()->fromQuery();
        $year = empty($query['year']) || !is_numeric($query['year']) ? null : (int) $query['year'];
        $month = empty($query['month']) || !is_numeric($query['month']) ? null : (int) $query['month'];

        $appendDates = $this->whereDate($year, $month, [], []);
        $bind = $appendDates['bind'];
        $types = $appendDates['types'];
        $force = $appendDates['force'];
        $whereYear = $appendDates['whereYear'];
        $whereMonth = $appendDates['whereMonth'];

        $sql = <<<SQL
SELECT item_item_set.item_set_id, COUNT(hit.id) AS total_hits
FROM hit hit $force
JOIN item_item_set ON hit.entity_id = item_item_set.item_id
WHERE hit.entity_name = "items"$whereStatus$whereYear$whereMonth
GROUP BY item_item_set.item_set_id
ORDER BY total_hits
;
SQL;
        $hitsPerItemSet = $this->connection->executeQuery($sql, $bind, $types)->fetchAllKeyValue();

        $removedItemSet = $this->translate('[Removed item set #%d]'); // @translate

        $api = $this->api();
        $results = [];
        // TODO Check and integrate statistics for item set tree (with performance).
        if (false && $this->plugins()->has('itemSetsTree')) {
            $itemSetIds = $api->search('item_sets', [], ['returnScalar', 'id'])->getContent();
            foreach ($itemSetIds as $itemSetId) {
                $hitsInclusive = $this->getHitsPerItemSet($hitsPerItemSet, $itemSetId);
                if ($hitsInclusive > 0) {
                    try {
                        $itemSetTitle = $api->read('item_sets', ['id' => $itemSetId])->getContent()->displayTitle();
                    } catch (\Exception $e) {
                        $itemSetTitle = sprintf($removedItemSet, $itemSetId);
                    }
                    $results[] = [
                        'item-set' => $itemSetTitle,
                        'hits' => $hitsPerItemSet[$itemSetId] ?? 0,
                        'hitsInclusive' => $hitsInclusive,
                    ];
                }
            }
        } else {
            foreach ($hitsPerItemSet as $itemSetId => $hits) {
                try {
                    $itemSetTitle = $api->read('item_sets', ['id' => $itemSetId])->getContent()->displayTitle();
                } catch (\Exception $e) {
                    $itemSetTitle = sprintf($removedItemSet, $itemSetId);
                }
                $results[] = [
                    'item-set' => $itemSetTitle,
                    'hits' => $hits,
                    'hitsInclusive' => '',
                ];
            }
        }

        $this->paginator(count($results));

        // TODO Manage special sort fields.
        $sortBy = $query['sort_by'] ?? null;
        if (empty($sortBy) || !in_array($sortBy, ['itemSet', 'hits', 'hitsInclusive'])) {
            $sortBy = 'hitsInclusive';
        }
        $sortOrder = $query['sort_order'] ?? null;
        if (empty($sortOrder) || $sortOrder !== 'asc') {
            $sortOrder = 'desc';
        }

        usort($results, function ($a, $b) use ($sortBy, $sortOrder) {
            $cmp = strnatcasecmp($a[$sortBy], $b[$sortBy]);
            return $sortOrder === 'desc' ? -$cmp : $cmp;
        });

        $years = $this->listYears(null, null, false);

        $view = new ViewModel([
            'type' => 'item-set',
            'results' => $results,
            'years' => $years,
            'yearFilter' => $year,
            'monthFilter' => $month,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-item-set' : 'statistics/site/browse/by-item-set');
    }

    public function byValueAction()
    {
        // FIXME Stats by value has not been fully checked.
        // TODO Move the process into view helper Statistic.
        // TODO Enlarge byItemSet to byResource (since anything is resource).

        $isAdminRequest = $this->status()->isAdminRequest();
        $settings = $this->settings();

        $userStatus = $isAdminRequest
            ? $settings->get('statistics_default_user_status_admin')
            : $settings->get('statistics_default_user_status_public');

        if ($userStatus === 'anonymous') {
            $whereStatus = "\nAND hit.user_id = 0";
        } elseif ($userStatus === 'identified') {
            $whereStatus = "\nAND hit.user_id <> 0";
        } else {
            $whereStatus = '';
        }

        $query = $this->params()->fromQuery();
        $year = empty($query['year']) || !is_numeric($query['year']) ? null : (int) $query['year'];
        $month = empty($query['month']) || !is_numeric($query['month']) ? null : (int) $query['month'];
        $property = $query['property'] ?? null;
        $typeFilter = $query['value_type'] ?? null;
        $byPeriodFilter = isset($query['by_period']) && in_array($query['by_period'], ['year', 'month']) ? $query['by_period'] : 'all';

        // A property is required to get stats, so get empty without a good one.
        if ($property && $propertyId = $this->getPropertyId($property)) {
            if (is_numeric($property)) {
                $property = $this->getPropertyId([$propertyId]);
                $property = key($property);
            }
            $joinProperty = ' AND property_id = :property_id';
            $bind['property_id'] = $propertyId;
            $types['property_id'] = \Doctrine\DBAL\ParameterType::INTEGER;
        } else {
            $joinProperty = ' AND property_id = 0';
            $bind = [];
            $types = [];
        }

        // TODO Add a type filter for all, or no type filter.
        switch ($typeFilter) {
            case 'resource':
                $joinResource = "\nLEFT JOIN resource ON resource.id = value.value_resource_id";
                $selectValue = 'value.value_resource_id AS "value", resource.title AS "label"';
                $typeFilterValue = 'value.value_resource_id';
                $whereFilterValue = "\nAND value.value_resource_id IS NOT NULL\nAND value.value_resource_id <> 0";
                break;
            case 'uri':
                $joinResource = '';
                $selectValue = 'value.uri AS "value", value.value AS "label"';
                $typeFilterValue = 'value.uri';
                $whereFilterValue = "\nAND value.uri IS NOT NULL\nAND value.uri <> ''";
                break;
            case 'value':
            default:
                $joinResource = '';
                $selectValue = 'value.value AS "value", "" AS "label"';
                $typeFilterValue = 'value.value';
                $whereFilterValue = "\nAND value.value IS NOT NULL\nAND value.value <> ''";
                break;
        }

        if ($typeFilter === 'resource') {
            $joinResource = "\nLEFT JOIN resource ON resource.id = value.value_resource_id";
        } else {
            $joinResource = '';
        }

        switch ($byPeriodFilter) {
            case 'year':
                $periods = $this->listYears($year, $year, true);
                break;
            case 'month':
                if ($year && $month) {
                    $periods = $this->listYearMonths((int) sprintf('%04d%02d', $year, $month), (int) sprintf('%04d%02d', $year, $month), true);
                } elseif ($year) {
                    $periods = $this->listYearMonths((int) sprintf('%04d01', $year), (int) sprintf('%04d12', $year), true);
                } elseif ($month) {
                    $this->messenger()->addWarning(new Message('A year and a month is required to get details by month.')); // @translate
                    $view = new ViewModel([
                        'type' => 'value',
                        'results' => [],
                        'years' => $this->listYears(null, null, true),
                        'yearFilter' => $year,
                        'monthFilter' => $month,
                        'propertyFilter' => $property,
                        'valueTypeFilter' => $typeFilter,
                        'byPeriodFilter' => $byPeriodFilter,
                    ]);
                    return $view
                        ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-value' : 'statistics/site/browse/by-value');
                } else {
                    $periods = $this->listYearMonths(null, null, true);
                }
                break;
            case 'all':
            default:
                $periods = [];
                break;
        }

        // TODO There is no pagination currently in stats by value.

        // TODO Manage special sort fields.
        $sortBy = $query['sort_by'] ?? null;
        if (empty($sortBy) || !in_array($sortBy, ['value', 'hits', 'hitsInclusive'])) {
            $sortBy = 'hitsInclusive';
        }
        $sortOrder = $query['sort_order'] ?? null;
        if (empty($sortOrder) || $sortOrder !== 'asc') {
            $sortOrder = 'desc';
        }

        // FIXME The results are doubled when the property has duplicate values for a resource, so fix it or warn about deduplicating values regularly (module BulkEdit).

        $baseBind = $bind;
        $baseTypes = $types;
        if ($periods) {
            // TODO Use a single query instead of a loop, so use periods as columns.
            $results = [];
            foreach ($periods as $period => $isEmpty) {
                if (empty($isEmpty)) {
                    $results[$period] = [];
                    continue;
                }

                // TODO Manage "force index" via query builder.
                // $qb = $this->connection->createQueryBuilder();
                if ($byPeriodFilter === 'year') {
                    $y = $period;
                    $m = null;
                } else {
                    $y = substr((string) $period, 0, 4);
                    $m = substr((string) $period, 4, 2);
                }
                $appendDates = $this->whereDate($y, $m, $baseBind, $baseTypes);
                $bind = $appendDates['bind'];
                $types = $appendDates['types'];
                $force = $appendDates['force'];
                $whereYear = $appendDates['whereYear'];
                $whereMonth = $appendDates['whereMonth'];

                $sql = <<<SQL
SELECT $selectValue, COUNT(hit.id) AS hits, "" AS hitsInclusive
FROM hit hit $force
JOIN value ON hit.entity_id = value.resource_id$joinProperty$joinResource
WHERE hit.entity_name = "items"$whereStatus$whereYear$whereMonth$whereFilterValue
GROUP BY $typeFilterValue
ORDER BY hits DESC
;
SQL;
                $result = $this->connection->executeQuery($sql, $bind, $types)->fetchAllAssociative();

                usort($result, function ($a, $b) use ($sortBy, $sortOrder) {
                    $cmp = strnatcasecmp($a[$sortBy], $b[$sortBy]);
                    return $sortOrder === 'desc' ? -$cmp : $cmp;
                });

                $results[$period] = $result;
            }

            $hasValueLabel = in_array($typeFilter, ['resource', 'uri']);
            $results = $this->mergeResultsByValue($results, $hasValueLabel);

            // TODO There is no pagination currently in stats by value.
            $this->paginator(count($results));

        } else {
            // TODO Manage "force index" via query builder.
            // $qb = $this->connection->createQueryBuilder();
            $appendDates = $this->whereDate($year, $month, $baseBind, $baseTypes);
            $bind = $appendDates['bind'];
            $types = $appendDates['types'];
            $force = $appendDates['force'];
            $whereYear = $appendDates['whereYear'];
            $whereMonth = $appendDates['whereMonth'];

        $sql = <<<SQL
SELECT $selectValue, COUNT(hit.id) AS hits, "" AS hitsInclusive
FROM hit hit $force
JOIN value ON hit.entity_id = value.resource_id$joinProperty$joinResource
WHERE hit.entity_name = "items"$whereStatus$whereYear$whereMonth$whereFilterValue
GROUP BY $typeFilterValue
ORDER BY hits DESC
;
SQL;
            $results = $this->connection->executeQuery($sql, $bind, $types)->fetchAllAssociative();

            // TODO There is no pagination currently in stats by value.
            $this->paginator(count($results));

            // TODO Reinclude sort order inside sql.
            usort($results, function ($a, $b) use ($sortBy, $sortOrder) {
                $cmp = strnatcasecmp($a[$sortBy], $b[$sortBy]);
                return $sortOrder === 'desc' ? -$cmp : $cmp;
            });

            // TODO Use the same format for all queries to simplify view.
            // $results['all'] = $results;
        }

        $years = $this->listYears(null, null, true);

        $view = new ViewModel([
            'type' => 'value',
            'results' => $results,
            'years' => $years,
            'periods' => $periods,
            'yearFilter' => $year,
            'monthFilter' => $month,
            'propertyFilter' => $property,
            'valueTypeFilter' => $typeFilter,
            'byPeriodFilter' => $byPeriodFilter,
        ]);

        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/browse/by-value' : 'statistics/site/browse/by-value');
    }

    /**
     * @fixme Finalize integration of item set tree.
     */
    protected function getHitsByItemSet($hitsPerItemSet, $itemSetId): int
    {
        $childrenHits = 0;
        $childItemSetIds = $this->api()->search('item_sets_tree_edge', [], ['returnScalar' => 'id'])->getChildCollections($itemSetId);
        foreach ($childItemSetIds as $childItemSetId) {
            $childrenHits += $this->getHitsPerItemSet($hitsPerItemSet, $childItemSetId);
        }
        return ($hitsPerItemSet[$itemSetId] ?? 0) + $childrenHits;
    }

    protected function defaultSort(array $query, array $allowedSorts = [], string $defaultSort = 'hits'): array
    {
        $sortBy = $query['sort_by'] ?? null;
        if (empty($sortBy) || !in_array($sortBy, $allowedSorts)) {
            $query['sort_by'] = $defaultSort;
        }
        $sortOrder = $query['sort_order'] ?? null;
        if (empty($sortOrder) || !in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $query['sort_order'] = 'desc';
        }
        return $query;
    }

    /**
     * Get the results for all values for all periods, so fill empty hits.
     */
    protected function mergeResultsByValue(array $results, bool $hasValueLabel = false): array
    {
        // Each result by period contains value, label, hits, inclusive hits.
        $valuesMaxCounts = [];
        $valuesHitsByPeriod = [];
        foreach ($results as $period => $periodResults) {
            foreach ($periodResults as $result) {
                $v = $result['value'];
                if ($hasValueLabel) {
                    $valuesHitsByPeriod[$v]['label'] = $result['label'];
                }
                $valuesHitsByPeriod[$v]['hits'][$period] = $result['hits'];
                $valuesMaxCounts[$v] = isset($valuesMaxCounts[$v]) ? max($valuesMaxCounts[$v], $result['hits']) : $result['hits'];
            }
        }
        asort($valuesMaxCounts);
        $valuesMaxCounts = array_reverse($valuesMaxCounts, true);
        return array_replace($valuesMaxCounts, $valuesHitsByPeriod);
    }

    protected function whereDate($year = null, $month = null, array $bind = [], array $types = []): array
    {
        if ($year || $month) {
            // This is the doctrine hashed name index for the column "created".
            $force = 'FORCE INDEX FOR JOIN (`IDX_5AD22641B23DB7B8`)';
            if ($year && $month) {
                $whereYear = "\nAND EXTRACT(YEAR_MONTH FROM hit.created) = :year_month";
                $bind['year_month'] = sprintf('%04d%02d', $year, $month);
                $types['year_month'] = \Doctrine\DBAL\ParameterType::INTEGER;
            } elseif ($year) {
                $whereYear = "\nAND EXTRACT(YEAR FROM hit.created) = :year";
                $bind['year'] = $year;
                $types['year'] = \Doctrine\DBAL\ParameterType::INTEGER;
            } elseif ($month) {
                $whereMonth = "\nAND EXTRACT(MONTH FROM hit.created) = :month";
                $bind['month'] = $month;
                $types['month'] = \Doctrine\DBAL\ParameterType::INTEGER;
            }
        }
        return [
            'bind' => $bind,
            'types' => $types,
            'force' => $force ?? '',
            'whereYear' => $whereYear ?? '',
            'whereMonth' => $whereMonth ?? '',
        ];
    }

    /**
     * List years as key and value.
     *
     * When the option to include dates without value is set, value may be null.
     */
    protected function listYears(?int $fromYear = null, ?int $toYear = null, bool $includeEmpty = false): array
    {
        $qb = $this->connection->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('DISTINCT EXTRACT(YEAR FROM hit.created) AS "period"')
            ->from('hit', 'hit')
            ->orderBy('period', 'asc');
        // Don't use function YEAR() in where for speed. Extract() is useless here.
        // TODO Add a generated index (doctrine 2.11, so Omeka 4).
        if ($fromYear) {
            $qb
                ->andWhere($expr->gte('hit.created', ':from_date'))
                ->setParameter('from_date', $fromYear . '-01-01 00:00:00', \Doctrine\DBAL\ParameterType::STRING);
        }
        if ($toYear) {
            $qb
                ->andWhere($expr->lte('hit.created', ':to_date'))
                ->setParameter('to_date', $toYear . '-12-31 23:59:59', \Doctrine\DBAL\ParameterType::STRING);
        }
        $result = $this->connection->executeQuery($qb, $qb->getParameters(), $qb->getParameterTypes())->fetchFirstColumn();

        $result = array_combine($result, $result);
        if (!$includeEmpty || count($result) <= 1) {
            return $result;
        }

        $range = array_fill_keys(range(min($result), max($result)), null);
        return array_replace($range, $result);
    }

    /**
     * List year-months as key and value.
     *
     * When the option to include dates without value is set, value may be null.
     */
    protected function listYearMonths(?int $fromYearMonth = null, ?int $toYearMonth = null, bool $includeEmpty = false): array
    {
        $qb = $this->connection->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('DISTINCT EXTRACT(YEAR_MONTH FROM hit.created) AS "period"')
            ->from('hit', 'hit')
            ->orderBy('period', 'asc');
        // Don't use function YEAR() in where for speed. Extract() is useless here.
        // TODO Add a generated index (doctrine 2.11, so Omeka 4).
        if ($fromYearMonth) {
            $qb
                ->andWhere($expr->gte('hit.created', ':from_date'))
                ->setParameter('from_date', sprintf('%04d-%02d', substr((string) $fromYearMonth, 0, 4), substr((string) $fromYearMonth, 4, 2)) . '-01 00:00:00', \Doctrine\DBAL\ParameterType::STRING);
        }
        if ($toYearMonth) {
            $year = (int) substr((string) $toYearMonth, 0, 4);
            $month = (int) substr((string) $toYearMonth, 4, 2) ?: 12;
            $day = $month === 2 ? date('L', mktime(0, 0, 0, 1, 1, $year) ? 29 : 28) : (in_array($month, [4, 6, 9, 11]) ? 30 : 31);
            $qb
                ->andWhere($expr->lte('hit.created', ':to_date'))
                ->setParameter('to_date', sprintf('%04d-%02d-%02d', $year, $month, $day) . ' 23:59:59', \Doctrine\DBAL\ParameterType::STRING);
        }
        $result = $this->connection->executeQuery($qb, $qb->getParameters(), $qb->getParameterTypes())->fetchFirstColumn();
        $result = array_combine($result, $result);
        if (!$includeEmpty || count($result) <= 1) {
            return $result;
        }

        // Fill all the missing months.
        $periods = $result;

        $first = reset($periods);
        $firstDate = $fromYearMonth ?: substr((string) $first, 0, 4) . '01';
        $firstYear = (int) substr((string) $firstDate, 0, 4);
        $firstMonth = (int) substr((string) $firstDate, 4, 2);

        $reversedPeriods = array_reverse($periods);
        $last = reset($reversedPeriods);
        $lastDate = $toYearMonth ?: substr((string) $last, 0, 4) . '12';
        $lastYear = (int) substr((string) $lastDate, 0, 4);
        $lastMonth = (int) substr((string) $lastDate, 4, 2);

        $range = [];

        // Fill months for first year.
        $isSingleYear = $firstYear === $lastYear;
        foreach (range($firstMonth, $isSingleYear ? $lastMonth : 12) as $currentMonth) {
            $range[sprintf('%04d%02d', $firstYear, $currentMonth)] = null;
        }

        // Fill months for intermediate years.
        $hasIntermediateYears = $firstYear + 1 < $lastYear;
        if ($hasIntermediateYears) {
            for ($currentYear = $firstYear + 1; $currentYear < $lastYear - 1; $currentYear++) {
                for ($currentMonth = 1; $currentMonth < 13; $currentMonth++) {
                    $range[sprintf('%04d%02d', $currentYear, $currentMonth)] = null;
                }
            }
        }

        // Fill months for last year.
        if (!$isSingleYear) {
            foreach (range($firstMonth, $lastMonth ?: 12) as $currentMonth) {
                $range[sprintf('%04d%02d', $firstYear, $currentMonth)] = null;
            }
        }

        return array_replace($range, $periods);
    }

    /**
     * Get one or more property ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[]|int|null The property ids matching terms or ids, or all
     * properties by terms.
     */
    protected function getPropertyId($termsOrIds = null)
    {
        static $propertiesByTerms;
        static $propertiesByTermsAndIds;

        if (is_null($propertiesByTermsAndIds)) {
            $qb = $this->connection->createQueryBuilder();
            $qb
                ->select(
                    'DISTINCT CONCAT(vocabulary.prefix, ":", property.local_name) AS term',
                    'property.id AS id',
                    // Required with only_full_group_by.
                    'vocabulary.id'
                )
                ->from('property', 'property')
                ->innerJoin('property', 'vocabulary', 'vocabulary', 'property.vocabulary_id = vocabulary.id')
                ->orderBy('vocabulary.id', 'asc')
                ->addOrderBy('property.id', 'asc')
            ;
            $propertiesByTerms = array_map('intval', $this->connection->executeQuery($qb)->fetchAllKeyValue());
            $propertiesByTermsAndIds = array_replace($propertiesByTerms, array_combine($propertiesByTerms, $propertiesByTerms));
        }

        if (is_null($termsOrIds)) {
            return $propertiesByTerms;
        }

        if (is_scalar($termsOrIds)) {
            return isset($propertiesByTermsAndIds[$termsOrIds])
                ? $propertiesByTermsAndIds[$termsOrIds]
                : [];
        }

        return array_intersect_key($propertiesByTermsAndIds, array_flip($termsOrIds));
    }
}
