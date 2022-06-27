<?php declare(strict_types=1);

namespace Statistics\Controller;

use Doctrine\DBAL\Connection;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Controller to browse Stats.
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

        $results['all'] = $this->statisticsPeriod();

        if (!$this->hasAdvancedSearch) {
            $view = new ViewModel([
                'results' => $results,
                'hasAdvancedSearch' => $this->hasAdvancedSearch,
            ]);
            return $view
                ->setTemplate($isAdminRequest ? 'statistics/admin/statistics/index' : 'statistics/site/statistics/index');
        }

        $translate = $this->plugins->get('translate');

        $results['today'] = $this->statisticsPeriod(strtotime('today'));

        $results['history'][$translate('Last year')] = $this->statisticsPeriod( // @translate
            strtotime('-1 year', strtotime(date('Y-1-1', $time))),
            strtotime(date('Y-1-1', $time) . ' - 1 second')
        );
        $results['history'][$translate('Last month')] = $this->statisticsPeriod( // @translate
            strtotime('-1 month', strtotime(date('Y-m-1', $time))),
            strtotime(date('Y-m-1', $time) . ' - 1 second')
        );
        $results['history'][$translate('Last week')] = $this->statisticsPeriod( // @translate
            strtotime("previous week"),
            strtotime("previous week + 6 days")
        );
        $results['history'][$translate('Yesterday')] = $this->statisticsPeriod( // @translate
            strtotime('-1 day', strtotime(date('Y-m-d', $time))),
            strtotime('-1 day', strtotime(date('Y-m-d', $time)))
        );

        $results['current'][$translate('This year')] = // @translate
        $this->statisticsPeriod(strtotime(date('Y-1-1', $time)));
        $results['current'][$translate('This month')] =  // @translate
        $this->statisticsPeriod(strtotime(date('Y-m-1', $time)));
        $results['current'][$translate('This week')] = // @translate
        $this->statisticsPeriod(strtotime('this week'));
        $results['current'][$translate('This day')] = // @translate
        $this->statisticsPeriod(strtotime('today'));

        foreach ([365 => null, 30 => null, 7 => null, 1 => null] as $start => $endPeriod) {
            $startPeriod = strtotime("- {$start} days");
            $label = ($start == 1)
                ? $translate('Last 24 hours') // @translate
                : sprintf($translate('Last %s days'), $start); // @translate
            $results['rolling'][$label] = $this->statisticsPeriod($startPeriod, $endPeriod);
        }

        $view = new ViewModel([
            'results' => $results,
            'hasAdvancedSearch' => $this->hasAdvancedSearch,
        ]);

        return $view
            ->setTemplate($isAdminRequest ? 'statistics/admin/statistics/index' : 'statistics/site/statistics/index');
    }

    /**
     * Helper to get all stats of a period.
     *
     * @todo Move the view helper Statistics.
     *
     * @param int $startPeriod Number of days before today (default is all).
     * @param int $endPeriod Number of days before today (default is now).
     * @param string $field "created" or "modified".
     * @return array
     */
    protected function statisticsPeriod(?int $startPeriod = null, ?int $endPeriod = null, string $field = 'created'): array
    {
        $query = [];
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

        $results = [
            'item_sets' => 0,
            'items' => 0,
            'media' => 0,
        ];

        // TODO A search by resources will allow only one query, but it is not yet merged by Omeka.
        foreach (array_keys($results) as $resourceType) {
            $results[$resourceType] = $api->search($resourceType, $query, ['initialize' => false, 'finalize' => false])->getTotalResults();
        }

        return ['total' => array_sum($results)] + $results;
    }
}
