<?php declare(strict_types=1);

namespace Statistics\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class AnalyticsByFieldForm extends Form
{
    public function init(): void
    {
        $this
            ->setAttribute('id', 'analytics')
            ->setAttribute('method', 'GET')
            // A search form doesn't need a csrf.
            ->remove('csrf')
            ->add([
                'name' => 'field',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Field', // @ŧranslate
                    'value_options' => [
                        'referrer' => 'Referrer', // @translate
                        'query' => 'query', // @translate
                        'user_agent' => 'User agent', // @translate
                        'accept_language' => 'Accept language', // @translate
                        'language' => 'Language', // @translate
                    ],
                    'empty_value' => '',
                ],
                'attributes' => [
                    'id' => 'field',
                    'value' => 'referrer',
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
