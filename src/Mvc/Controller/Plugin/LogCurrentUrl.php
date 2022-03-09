<?php declare(strict_types=1);

namespace Statistics\Mvc\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Api\Request;
use Statistics\Entity\Hit;

class LogCurrentUrl extends AbstractPlugin
{
    /**
     * @var \Interop\Container\ContainerInterface
     */
    protected $services;

    /**
     * Constructor.
     *
     * Use services to avoid some load for admin side, because the event
     * "view.layout" is triggered for any url.
     */
    public function __construct(ContainerInterface $services)
    {
        $this->services = $services;
    }

    /**
     * Log the current public url.
     */
    public function __invoke(): ?Hit
    {
        // Don't store server ping or internal redirect on root or some proxies.
        if (empty($_SERVER['HTTP_HOST'])
            // || ($_SERVER['REQUEST_URI'] === '/' && $_SERVER['QUERY'] === '' && $_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['CONTENT_LENGTH'] === '0')
        ) {
            return null;
        }

        // Don't log admin pages.

        /** @var \Omeka\Mvc\Status $status */
        $status = $this->services->get('Omeka\Status');
        if ($status->isAdminRequest()) {
            return null;
        }

        // If the request is a download, don't log it for admin.
        // It's not simple to determine from server if the request comes from a
        // visitor on the site or something else. So use referrer and identity.
        // TODO But log for guests users.
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referrer
            && strpos($referrer, '/admin/')
            && $status->getRouteMatch()->getMatchedRouteName() === 'download'
            // Only check if there is a user: no useless check for users who
            // can't go admin (guest).
            && $this->services->get('Omeka\AuthenticationService')->getIdentity()
        ) {
            $urlAdminTop = $this->services->get('ControllerPluginManager')->get('url')->fromRoute('admin', [], ['force_canonical' => true]) . '/';
            if (strpos($referrer, $urlAdminTop) === 0) {
                return null;
            }
        }

        // For performance, use the adapter directly, not the api.
        // TODO Use direct sql query to store hits?

        /** @var \Statistics\Api\Adapter\HitAdapter $adapter */
        $hitAdapter = $this->services->get('Omeka\ApiAdapterManager')->get('hits');

        $includeBots = (bool) $this->services->get('Omeka\Settings')->get('statistics_include_bots');
        if (empty($includeBots)) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            if ($hitAdapter->isBot($userAgent)) {
                return null;
            }
        }

        $request = new Request(Request::CREATE, 'hits');
        $request
            ->setOption('initialize', false)
            ->setOption('finalize', false)
            ->setOption('returnScalar', 'id')
        ;
        // The entity manager is automatically flushed by default.
        return $hitAdapter->create($request)->getContent();
    }
}
