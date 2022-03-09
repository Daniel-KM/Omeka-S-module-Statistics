<?php declare(strict_types=1);

namespace Statistics\Shortcode;

use Shortcode\Shortcode\AbstractShortcode;

class Stat extends AbstractShortcode
{
    public function render(array $args = []): string
    {
        if ($this->shortcodeName === 'stat'
            || $this->shortcodeName === 'stat_total'
        ) {
            return $this->renderStatsTotal($args);
        } elseif ($this->shortcodeName === 'stat_position') {
            return $this->renderStatsPosition($args);
        } elseif ($this->shortcodeName === 'stat_vieweds') {
            return $this->renderStatsVieweds($args);
        } else {
            return '';
        }
    }

    /**
     * Shortcode to display total hits of one or multiple pages or resources.
     *
     * If resource(s) is set, don't look for url(s).
     */
    protected function renderStatsTotal(array $args): string
    {
        $type = $args['type'] ?? null;

        // TODO There may be multiple resource names.
        $resourceName = $args['resource'] ?? $args['resource_type'] ?? $args['record_type'] ?? $args['entity_name'] ?? null;
        if ($resourceName) {
            $resourceName = strtolower($resourceName);
        }

        $resourceId = $resourceName
            ? $args['id'] ?? $args['resource_id'] ?? $args['record_id'] ?? $args['entity_id'] ?? null
            : null;

        /** @var \Statistics\View\Helper\Statistic $statistic */
        $statistic = $this->view->statistic();

        // Search by resource.
        if ($resourceId) {
            $result = $type === 'download'
                ? $statistic->totalDownload($resourceId)
                : $statistic->totalResource((string) $resourceName, (int) $resourceId);
        }
        // Search by resource type.
        elseif ($resourceName) {
            $result = $statistic->totalResourceType($resourceName);
        }
        // Search by url.
        else {
            $url = $args['url'] ?? null;
            $result = $statistic->totalPage($url);
        }

        // Don't return null.
        return '<span class="statistics-data statistics-hits">'
            . (int) $result
            . '</span>';
    }

    /**
     * Shortcode to display the position of the page or resource (most viewed).
     */
    protected function renderStatsPosition(array $args): string
    {
        $type = $args['type'] ?? null;

        // Unlike StatsTotal, position of multiple resource type is meaningless.
        $resourceName = $args['resource'] ?? $args['resource_type'] ?? $args['record_type'] ?? $args['entity_name'] ?? null;
        if ($resourceName) {
            $resourceName = strtolower($resourceName);
        }

        $resourceId = $resourceName
            ? $args['id'] ?? $args['resource_id'] ?? $args['record_id'] ?? null
            : null;

        /** @var \Statistics\View\Helper\Statistic $statistic */
        $statistic = $this->view->statistic();

        // Search by resource.
        if ($resourceId) {
            $result = $type === 'download'
                ? $statistic->positionDownload($resourceId)
                : $statistic->positionResource((string) $resourceName, (int) $resourceId);
        }
        // Search by url.
        else {
            $url = $args['url'] ?? null;
            $result = $statistic->positionPage($url);
        }

        // Don't return null.
        return '<span class="statistics-data statistics-position">'
            . (int) $result
            . '</span>';
    }

    /**
     * Shortcode to get the viewed pages or resources.
     */
    protected function renderStatsVieweds(array $args): string
    {
        $type = $args['type'] ?? null;
        $sort = isset($args['sort']) && $args['sort'] === 'last' ? 'last' : 'most';
        $page = isset($args['page']) ? (int) $args['page'] : null;
        $limit = isset($args['number']) ? (int) $args['number'] : 10;

        /** @var \Statistics\View\Helper\Statistic $statistic */
        $statistic = $this->view->statistic();

        if ($type === 'download') {
            // Search in all downloads.
            return $statistic->viewedDownloads($sort, null, $page, $limit, true);
        } elseif (!$type || $type === 'page') {
            // Search in all pages.
            return $statistic->viewedPages(null, $sort, null, $page, $limit, true);
        } else {
            // Search by resource type.
            return $statistic->viewedResources($type, $sort, null, $page, $limit, true);
        }
    }
}
