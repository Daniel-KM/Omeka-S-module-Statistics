<?php declare(strict_types=1);

namespace Statistics\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Omeka\Form\Element as OmekaElement;

class StatisticsBySiteForm extends Form
{
    /**
     * @var bool
     */
    protected $hasAccess = false;

    /**
     * @var array
     */
    protected $years = [];

    public function __construct($name = null, array $options = [])
    {
        if (array_key_exists('has_access', $options)) {
            $this->hasAccess = (bool) $options['has_access'];
        }
        if (array_key_exists('years', $options) && is_array($options['years'])) {
            $this->years = array_map('intval', $options['years']);
        }
        parent::__construct($name, $options);
    }

    public function init(): void
    {
        $this
            ->setAttribute('id', 'statistics')
            ->setAttribute('method', 'GET')
            // A search form doesn't need a csrf.
            ->remove('csrf')
            ->add([
                'name' => 'resource_type',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'label' => 'Resource types', // @translate
                    'value_options' => [
                        'resources' => 'Resources',
                        'item_sets' => 'Item sets',
                        'items' => 'Items',
                        'media' => 'Medias'
                    ],
                ],
                'attributes' => [
                    'id' => 'resource_type_resources',
                    'value' => [
                        'items',
                    ],
                ],
            ])
            ->add([
                'name' => 'year',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'label' => 'Year', // @ŧranslate
                    'value_options' => [
                        '' => 'All years', // @translate
                    ] + $this->years,
                    'empty_value' => '',
                ],
                'attributes' => [
                    'id' => 'year',
                ],
            ])
            ->add([
                'name' => 'month',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'label' => 'Month', // @ŧranslate
                    'value_options' => [
                        '' => 'All monthes', // @translate
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December',
                    ],
                    'empty_value' => '',
                ],
                'attributes' => [
                    'id' => 'month',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a month…',
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
                    'form' => 'statistics',
                ],
            ])
        ;
    }

    public function setHasAccess(bool $hasAccess): self
    {
        $this->hasAccess = $hasAccess;
        return $this;
    }

    public function setYears(array $years): self
    {
        $this->years = array_map('intval', $years);
        return $this;
    }
}
