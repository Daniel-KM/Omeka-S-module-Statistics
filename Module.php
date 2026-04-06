<?php declare(strict_types=1);

namespace Statistics;

if (!class_exists('Common\TraitModule', false)) {
    require_once file_exists(dirname(__DIR__) . '/Common/src/TraitModule.php')
        ? dirname(__DIR__) . '/Common/src/TraitModule.php'
        : dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Omeka\Module\AbstractModule;

/**
 * Statistics
 *
 * Provides statistical calculations on Omeka resources (counts by date, site,
 * value, etc.).
 *
 * @copyright Daniel Berthereau, 2014-2026
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    use TraitModule;

    public function init(ModuleManager $moduleManager): void
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    protected function preInstall(): void
    {
        // Omeka calls install() before init(), so the module composer
        // autoloader (loaded in init()) is not yet available. Load it here so
        // any class from src/ resolves during install/postInstall.
        require_once __DIR__ . '/vendor/autoload.php';

        $services = $this->getServiceLocator();
        $translator = $services->get('MvcTranslator');

        $errors = [];

        if (PHP_VERSION_ID < 80100) {
            $message = new \Omeka\Stdlib\Message(
                $translator->translate('The module %1$s requires PHP %2$s or later.'), // @translate
                'Statistics', '8.1'
            );
            $errors[] = (string) $message;
        }

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.85')) {
            $message = new \Omeka\Stdlib\Message(
                $translator->translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.85'
            );
            $errors[] = (string) $message;
        }

        if ($errors) {
            throw new \Omeka\Module\Exception\ModuleCannotInstallException(implode("\n", $errors));
        }
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        /** @var \Omeka\Permissions\Acl $acl */
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');

        // The public rights are checked in controller according to the config.
        $settings = $services->get('Omeka\Settings');
        if ($settings->get('statistics_public_allow_statistics')) {
            $acl
                ->allow(
                    null,
                    ['Statistics\Controller\Statistics']
                );
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
    }

    public function handleMainSettings(Event $event): void
    {
        $this->handleAnySettings($event, 'settings');
    }
}
