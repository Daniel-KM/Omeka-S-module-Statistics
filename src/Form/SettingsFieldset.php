<?php declare(strict_types=1);

namespace Statistics\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    /**
     * @var string
     */
    protected $label = 'Statistics'; // @translate

    protected $elementGroups = [
        'statistics' => 'Statistics', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'statistics')
            ->setOption('element_groups', $this->elementGroups)
            ->add([
                'name' => 'statistics_public_allow_statistics',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'statistics',
                    'label' => 'Allow public to access statistics', // @translate
                ],
                'attributes' => [
                    'id' => 'statistics_public_allow_statistics',
                ],
            ])
        ;
    }
}
