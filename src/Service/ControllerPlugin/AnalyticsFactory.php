<?php declare(strict_types=1);

namespace Statistics\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Statistics\Mvc\Controller\Plugin\Analytics;

class AnalyticsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Analytics(
            $services->get('ViewHelperManager')->get('analytics')
        );
    }
}
