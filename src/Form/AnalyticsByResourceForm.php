<?php declare(strict_types=1);

namespace Statistics\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class AnalyticsByResourceForm extends Form
{
    public function init(): void
    {
        $this
            ->setAttribute('id', 'analytics')
            ->setAttribute('method', 'GET')
            ->add([
                'name' => 'resource_type',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Filter pages with resources', // @ŧranslate
                    'value_options' => [
                        '' => 'All', // @translate
                        'items' => 'By item', // @translate
                        'item_sets' => 'By item set', // @translate
                        'media' => 'By media', // @translate
                        'site_pages' => 'By page', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'resource_type',
                    'value' => '',
                ],
            ])
            ->add([
                'name' => 'submit',
                'type' => Element\Submit::class,
                'options' => [
                    'label' => 'Submit', // @ŧranslate
                ],
                'attributes' => [
                    'id' => 'submit',
                    'form' => 'analytics',
                    'value' => 'Submit',
                ],
            ])
        ;
    }
}
