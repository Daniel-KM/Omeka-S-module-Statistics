<?php declare(strict_types=1);

namespace Statistics\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Omeka\Form\Element as OmekaElement;

class AnalyticsByDownloadForm extends Form
{
    public function init(): void
    {
        $this
            ->setAttribute('id', 'analytics')
            ->setAttribute('method', 'GET')
            // A search form doesn't need a csrf.
            ->remove('csrf')
            ->add([
                'type' => OmekaElement\Query::class,
                'name' => 'query',
                'options' => [
                    'label' => 'Resource query', // @translate
                    'info' => 'Filter the resources', // @translate
                    'documentation' => 'https://omeka.org/s/docs/user-manual/sites/site_pages/#browse-preview',
                    'query_resource_type' => 'media',
                    'query_partial_excludelist' => [
                    ],
                ],
                'attributes' => [
                    'id' => 'query',
                ],
            ])
            ->add([
                'name' => 'columns',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'label' => 'Columns', // @ŧranslate
                    'value_options' => [
                        'url' => 'Page', // @translate
                        'hits' => 'Hits', // @translate
                        'hits_anonymous' => 'Anonymous', // @translate
                        'hits_identified' => 'Identified', // @translate
                        'resource' => 'Resource', // @translate
                        'resource_type' => 'Resource type', // @translate
                        'resource_class' => 'Resource class', // @translate
                        'resource_template' => 'Resource template', // @translate
                        'item_sets' => 'Item sets', // @translate
                        'media_type' => 'Media type', // @translate
                        'date' => 'Last date', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'resource_type',
                    'value' => [
                        'url',
                        'hits',
                        'resource',
                        'media_type',
                    ],
                ],
            ])
            ->add([
                'name' => 'submit',
                'type' => Element\Button::class,
                'options' => [
                    'label' => 'Submit', // @ŧranslate
                ],
                'attributes' => [
                    'id' => 'submit',
                    'type' => 'submit',
                    'form' => 'analytics',
                ],
            ])
        ;
    }
}
