<?php

namespace Dcat\Admin\Grid\Displayers;

use Dcat\Admin\Admin;
use Illuminate\Support\Arr;

class Editable extends AbstractDisplayer
{
    /**
     * @var int
     */
    protected static $setup;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Type of editable.
     *
     * @var string
     */
    protected $type = '';

    /**
     * Options of editable function.
     *
     * @var array
     */
    protected $options = [
        'emptytext'  => '<i class="fa fa-pencil"></i>',
    ];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Add options for editable.
     *
     * @param array $options
     */
    public function addOptions($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Add attributes for editable.
     *
     * @param array $attributes
     */
    public function addAttributes($attributes = [])
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * Text type editable.
     */
    public function text()
    {
    }

    /**
     * Textarea type editable.
     */
    public function textarea()
    {
    }

    /**
     * Select type editable.
     *
     * @param array|\Closure $options
     */
    public function select($options = [])
    {
        $useClosure = false;

        if ($options instanceof \Closure) {
            $useClosure = true;
            $options = $options->call($this, $this->row);
        }

        $source = [];

        foreach ($options as $value => $text) {
            $source[] = compact('value', 'text');
        }

        if ($useClosure) {
            $this->addAttributes(['data-source' => json_encode($source)]);
        } else {
            $this->addOptions(compact('source'));
        }
    }

    /**
     * Date type editable.
     */
    public function date()
    {
        $this->combodate();
    }

    /**
     * Datetime type editable.
     */
    public function datetime()
    {
        $this->combodate('YYYY-MM-DD HH:mm:ss');
    }

    /**
     * Year type editable.
     */
    public function year()
    {
        $this->combodate('YYYY');
    }

    /**
     * Month type editable.
     */
    public function month()
    {
        $this->combodate('MM');
    }

    /**
     * Day type editable.
     */
    public function day()
    {
        $this->combodate('DD');
    }

    /**
     * Combodate type editable.
     *
     * @param string $format
     */
    public function combodate($format = 'YYYY-MM-DD')
    {
        $this->type = 'combodate';

        $this->addOptions([
            'format'     => $format,
            'viewformat' => $format,
            'template'   => $format,
            'combodate'  => [
                'maxYear' => 2035,
            ],
        ]);
    }

    protected function buildEditableOptions(array $arguments = [])
    {
        $this->type = Arr::get($arguments, 0, 'text');

        call_user_func_array([$this, $this->type], array_slice($arguments, 1));
    }

    protected function setupScript()
    {
        if (! static::$setup) {
            static::$setup = 1;
            Admin::script(
                <<<'JS'
$.fn.editable.defaults.params = function (params) {
    params._token = LA.token;
    params._editable = 1;
    params._method = 'PUT';
    return params;
};
$.fn.editable.defaults.error = function (data) {
    var msg = '';
    if (data.responseJSON.errors) {
        $.each(data.responseJSON.errors, function (k, v) {
            msg += v + "\\n";
        });
    }
    return msg
};
JS
            );
        }
    }

    public function display()
    {
        $this->options['name'] = $column = $this->column->getName();

        $class = 'grid-editable-'.str_replace(['.', '#', '[', ']'], '-', $column);

        $this->buildEditableOptions(func_get_args());

        $options = json_encode($this->options);

        $this->setupScript();

        Admin::script("$('.$class').editable($options);");

        $attributes = [
            'href'       => '#',
            'class'      => "$class",
            'data-type'  => $this->type,
            'data-pk'    => "{$this->getKey()}",
            'data-url'   => "{$this->getResource()}/{$this->getKey()}",
            'data-value' => "{$this->value}",
        ];

        if (! empty($this->attributes)) {
            $attributes = array_merge($attributes, $this->attributes);
        }

        $attributes = collect($attributes)->map(function ($attribute, $name) {
            return "$name='$attribute'";
        })->implode(' ');

        $html = $this->type === 'select' ? '' : $this->value;

        return "<a $attributes>{$html}</a>";
    }

    protected function collectAssets()
    {
        Admin::collectComponentAssets('editable');
    }
}
