<?php declare(strict_types=1);

namespace Statistics\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class AnalyticsByPageForm extends Form
{
    public function init(): void
    {
        $this
            ->setAttribute('id', 'analytics')
            ->setAttribute('method', 'GET')
            // A search form doesn't need a csrf.
            ->remove('csrf')
            ->add([
                'name' => 'has_resource',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Filter pages with resources', // @ŧranslate
                    'value_options' => [
                        '' => 'All', // @translate
                        '1' => 'Has resource', // @translate
                        '0' => 'Has no resource', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'has_resource',
                    'value' => '',
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
