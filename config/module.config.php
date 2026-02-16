<?php declare(strict_types=1);

namespace Statistics;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'apiResourcesTotalResults' => View\Helper\ApiResourcesTotalResults::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
        ],
        'factories' => [
            Form\StatisticsBySiteForm::class => Service\Form\FormFactory::class,
            Form\StatisticsByValueForm::class => Service\Form\FormFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'Statistics\Controller\Statistics' => Service\Controller\StatisticsControllerFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            'statistics' => [
                'label' => 'Statistics', // @translate
                'route' => 'admin/statistics',
                'controller' => 'Statistics',
                'action' => 'index',
                'resource' => 'Statistics\Controller\Statistics',
                'class' => 'o-icon- fa-chart-line',
                'pages' => [
                    [
                        'route' => 'admin/statistics/default',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'statistics' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/statistics',
                            'defaults' => [
                                '__NAMESPACE__' => 'Statistics\Controller',
                                '__SITE__' => true,
                                'controller' => 'Statistics',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'output' => [
                                        'type' => \Laminas\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '.:output',
                                            'constraints' => [
                                                'output' => 'csv|ods|tsv',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'child_routes' => [
                    'statistics' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/statistics',
                            'defaults' => [
                                '__NAMESPACE__' => 'Statistics\Controller',
                                '__ADMIN__' => true,
                                'controller' => 'Statistics',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'output' => [
                                        'type' => \Laminas\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '.:output',
                                            'constraints' => [
                                                'output' => 'csv|ods|tsv',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'statistics' => [
        'settings' => [
            'statistics_public_allow_statistics' => false,
        ],
    ],
];
