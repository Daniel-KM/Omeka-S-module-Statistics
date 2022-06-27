<?php declare(strict_types=1);

namespace Statistics\Controller;

use Doctrine\DBAL\Connection;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Stdlib\Message;
use Statistics\Entity\Stat;

/**
 * Controller to browse Statistics.
 *
 * Statistics are mainly standard search requests with date interval.
 * To search a date interval requires module AdvancedSearch.
 */
class StatisticsController extends AbstractActionController
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $hasAdvancedSearch;

    public function __construct(Connection $connection, bool $hasAdvancedSearch)
    {
        $this->connection = $connection;
        $this->hasAdvancedSearch = $hasAdvancedSearch;
    }

    public function indexAction()
    {
        $isAdminRequest = $this->status()->isAdminRequest();

        $results = [];
        $time = time();

        $resourceTypes = ['resources', 'item_sets', 'items', 'media'];

        $results['all'] = $this->statisticsPeriod(null, null, [], 'created', $resourceTypes);

        if (!$this->hasAdvancedSearch) {
            $view = new ViewModel([
                'results' => $results,
                'hasAdvancedSearch' => $this->hasAdvancedSearch,
            ]);
            return $view
                ->setTemplate($isAdminRequest ? 'statistics/admin/statistics/index' : 'statistics/site/statistics/index');
        }

        $translate = $this->plugins->get('translate');

        $results['today'] = $this->statisticsPeriod(strtotime('today'), null, [], 'created', $resourceTypes);

        $results['history'][$translate('Last year')] = $this->statisticsPeriod( // @translate
            strtotime('-1 year', strtotime(date('Y-1-1', $time))),
            strtotime(date('Y-1-1', $time) . ' - 1 second'),
            [],
            'created',
            $resourceTypes
        );
        $results['history'][$translate('Last month')] = $this->statisticsPeriod( // @translate
            strtotime('-1 month', strtotime(date('Y-m-1', $time))),
            strtotime(date('Y-m-1', $time) . ' - 1 second'),
            [],
            'created',
            $resourceTypes
        );
        $results['history'][$translate('Last week')] = $this->statisticsPeriod( // @translate
            strtotime("previous week"),
            strtotime("previous week + 6 days"),
            [],
            'created',
            $resourceTypes
        );
        $results['history'][$translate('Yesterday')] = $this->statisticsPeriod( // @translate
            strtotime('-1 day', strtotime(date('Y-m-d', $time))),
            strtotime('-1 day', strtotime(date('Y-m-d', $time))),
            [],
            'created',
            $resourceTypes
        );

        $results['current'][$translate('This year')] = // @translate
        $this->statisticsPeriod(strtotime(date('Y-1-1', $time)), null, [], 'created', $resourceTypes);
        $results['current'][$translate('This month')] =  // @translate
        $this->statisticsPeriod(strtotime(date('Y-m-1', $time)), null, [], 'created', $resourceTypes);
        $results['current'][$translate('This week')] = // @translate
        $this->statisticsPeriod(strtotime('this week'), null, [], 'created', $resourceTypes);
        $results['current'][$translate('This day')] = // @translate
        $this->statisticsPeriod(strtotime('today'), null, [], 'created', $resourceTypes);

        foreach ([365 => null, 30 => null, 7 => null, 1 => null] as $start => $endPeriod) {
            $startPeriod = strtotime("- {$start} days");
            $label = ($start == 1)
                ? $translate('Last 24 hours') // @translate
                : sprintf($translate('Last %s days'), $start); // @translate
            $results['rolling'][$label] = $this->statisticsPeriod($startPeriod, $endPeriod, [], 'created', $resourceTypes);
        }

        $view = new ViewModel([
            'results' => $results,
            'hasAdvancedSearch' => $this->hasAdvancedSearch,
        ]);

        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/statistics/index' : 'statistics/site/statistics/index');
    }

    /**
     * Redirect to the "index" action.
     */
    protected function redirectToIndex()
    {
        $query = $this->params()->fromRoute();
        $query['action'] = 'index';
        $isSiteRequest = $this->status()->isSiteRequest();
        return $this->redirect()->toRoute($isSiteRequest ? 'site/statistics/default' : 'admin/statistics/default', $query);
    }

    public function browseAction()
    {
        return $this->redirectToIndex();
    }

    public function bySiteAction()
    {
        if (!$this->hasAdvancedSearch) {
            return $this->redirectToIndex();
        }

        $isAdminRequest = $this->status()->isAdminRequest();

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        $sites = $api->search('sites', [], ['initialize' => false, 'finalize' => false, 'returnScalar' => 'title'])->getContent();

        $query = $this->params()->fromQuery();

        $resourceTypes = empty($query['resource_type']) ? ['items'] : (is_array($query['resource_type']) ? $query['resource_type'] : [$query['resource_type']]);
        $resourceTypes = array_intersect(['resources', 'item_sets', 'items', 'media'], $resourceTypes) ?: ['items'];
        $year = empty($query['year']) || !is_numeric($query['year']) ? null : (int) $query['year'];
        $month = empty($query['month']) || !is_numeric($query['month']) ? null : (int) $query['month'];

        $baseQuery = $query;

        $results = [];
        foreach ($sites as $siteId => $title) {
            $query = $baseQuery;
            $query['site_id'] = $siteId;
            $results[$siteId]['label'] = $title;
            $results[$siteId]['count'] = $this->statisticsPeriod($year, $month, $query, 'created', $resourceTypes);
        }

        $this->paginator(count($results));

        // TODO Manage special sort fields.
        $sortBy = $query['sort_by'] ?? null;
        if (empty($sortBy) || !in_array($sortBy, ['site', 'resources', 'item_sets', 'items', 'media'])) {
            $sortBy = 'total';
        }
        $sortOrder = isset($query['sort_order']) && strtolower($query['sort_order']) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'site') {
            $sortBy = 'label';
            usort($results, function ($a, $b) use ($sortBy, $sortOrder) {
                $cmp = $a[$sortBy] <=> $b[$sortBy];
                return $sortOrder === 'desc' ? -$cmp : $cmp;
            });
        } elseif (in_array($sortBy, ['total', 'resources', 'item_sets', 'items', 'media'])) {
            if ($sortBy === 'total') {
                $sortBy = 'resources';
            }
            usort($results, function ($a, $b) use ($sortBy, $sortOrder) {
                $cmp = $a['count'][$sortBy] <=> $b['count'][$sortBy];
                return $sortOrder === 'desc' ? -$cmp : $cmp;
            });
        }

        $years = $this->listYears(null, null, false);

        $view = new ViewModel([
            'type' => 'site',
            'results' => $results,
            'resourceTypes' => $resourceTypes,
            'years' => $years,
            'yearFilter' => $year,
            'monthFilter' => $month,
            'hasAdvancedSearch' => $this->hasAdvancedSearch,
        ]);
        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/statistics/by-site' : 'statistics/site/statistics/by-site');
    }

    /**
     * Helper to get all stats of a period.
     *
     * @todo Move the view helper Statistics.
     *
     * @param int $startPeriod Number of days before today (default is all).
     * @param int $endPeriod Number of days before today (default is now).
     * @param array $query
     * @param string $field "created" or "modified".
     * @param array $resourceTypes
     * @return array
     */
    protected function statisticsPeriod(
        ?int $startPeriod = null,
        ?int $endPeriod = null,
        array $query = [],
        string $field = 'created',
        array $resourceTypes = []
    ): array {
        $isOnlyResources = array_values($resourceTypes) === ['resources'];
        $hasResources = array_search('resources', $resourceTypes);
        if ($isOnlyResources) {
            $resourceTypes = ['item_sets', 'items', 'media'];
        } elseif ($hasResources !== false) {
            unset($resourceTypes[$hasResources]);
            $hasResources = true;
        }

        if ($startPeriod) {
            $query['datetime'][] = [
                'joiner' => 'and',
                'field' => $field,
                'type' => 'gte',
                'value' => date('Y-m-d 00:00:00', $startPeriod),
            ];
        }
        if ($endPeriod) {
            $query['datetime'][] = [
                'joiner' => 'and',
                'field' => $field,
                'type' => 'lte',
                'value' => date('Y-m-d 23:59:59', $endPeriod),
            ];
        }

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        $results = [];
        // TODO A search by resources will allow only one query, but it is not yet merged by Omeka.
        foreach ($resourceTypes as $resourceType) {
            $results[$resourceType] = $api->search($resourceType, $query, ['initialize' => false, 'finalize' => false])->getTotalResults();
        }

        if ($isOnlyResources) {
            return ['resources' => array_sum($results)];
        }

        return $hasResources
            ? ['resources' => array_sum($results)] + $results
            : $results;
    }

    /**
     * List years as key and value.
     *
     * When the option to include dates without value is set, value may be null.
     */
    protected function listYears(?int $fromYear = null, ?int $toYear = null, bool $includeEmpty = false, string $field = 'created'): array
    {
        $qb = $this->connection->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select("DISTINCT EXTRACT(YEAR FROM resource.$field) AS 'period'")
            ->from('resource', 'resource')
            ->orderBy('period', 'asc');
        // Don't use function YEAR() in where for speed. Extract() is useless here.
        // TODO Add a generated index (doctrine 2.11, so Omeka 4).
        if ($fromYear) {
            $qb
                ->andWhere($expr->gte('resource.' . $field, ':from_date'))
                ->setParameter('from_date', $fromYear . '-01-01 00:00:00', \Doctrine\DBAL\ParameterType::STRING);
        }
        if ($toYear) {
            $qb
                ->andWhere($expr->lte('resource.' . $field, ':to_date'))
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
}
