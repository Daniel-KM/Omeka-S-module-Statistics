<?php declare(strict_types=1);

namespace Statistics\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

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
