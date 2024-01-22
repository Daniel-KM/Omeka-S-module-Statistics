<?php declare(strict_types=1);

namespace Statistics\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class AnalyticsBySiteForm extends Form
{
    /**
     * @var array
     */
    protected $years = [];

    /**
     * Overridden by AnalyticsByItemSetForm.
     */
    public function __construct($name = null, array $options = [])
    {
        if (array_key_exists('years', $options) && is_array($options['years'])) {
            $this->years = array_map('intval', $options['years']);
        }
        parent::__construct($name, $options);
    }

    public function init(): void
    {
        $this
            ->setAttribute('id', 'analytics')
            ->setAttribute('method', 'GET')
            ->remove('csrf')
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
                'name' => 'since',
                'type' => CommonElement\OptionalDate::class,
                'options' => [
                    'label' => 'From', // @ŧranslate
                    'format' => 'Y-m-d',
                ],
                'attributes' => [
                    'id' => 'since',
                    'min' => reset($this->years) . '-01-01',
                    'max' => end($this->years) . '-12-31',
                    'step' => 1,
                ],
            ])
            ->add([
                'name' => 'until',
                'type' => CommonElement\OptionalDate::class,
                'options' => [
                    'label' => 'Until', // @ŧranslate
                    'format' => 'Y-m-d',
                ],
                'attributes' => [
                    'id' => 'until',
                    'min' => reset($this->years) . '-01-01',
                    'max' => end($this->years) . '-12-31',
                    'step' => 1,
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

    public function setYears(array $years): self
    {
        $this->years = array_map('intval', $years);
        return $this;
    }
}
