<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moo\MiniShortcode\Shortcode;

use Moo\MiniShortcode\ShortcodeInterface;
use Moo\MiniShortcode\MceDialogAwareInterface;
use \DateTime as DateTime;

/**
 * A shortcode to display a list of items.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Listing implements ShortcodeInterface, MceDialogAwareInterface
{
    /** parameters filters */
    const PARAM_FILTER_STRING = 'text';
    const PARAM_FILTER_INT = 'int';
    const PARAM_FILTER_DATE = 'date';

    /**
     * Total number of items
     *
     * @var int
     */
    protected $count = 0;

    /**
     * List of items
     *
     * @var array
     */
    protected $items = array();

    /**
     * List of options to be used in the shortcode.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Default options values
     *
     * @var array
     */
    protected $defaultOptions = array(
        'class'     => '',
        'max'       => '5',
        'delimiter' => '|',
        'format'    => '',
        'tag'       => 'div',
        'before'    => '',
        'after'     => '',
        'sort'      => 'desc',
    );

    /**
     * An array defines the filters to be used for each of the shortcode option.
     *
     * @var array
     */
    protected $optionsFilters = array(
        'class'     => self::PARAM_FILTER_STRING,
        'max'       => self::PARAM_FILTER_INT,
        'delimiter' => self::PARAM_FILTER_STRING,
        'format'    => self::PARAM_FILTER_STRING,
        'tag'       => self::PARAM_FILTER_STRING,
        'js'        => self::PARAM_FILTER_STRING,
        'before'    => self::PARAM_FILTER_STRING,
        'after'     => self::PARAM_FILTER_STRING,
        'sort'      => self::PARAM_FILTER_STRING,
    );

    /**
     * Filters to be used on item values
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Shortcode callback method
     *
     * @param  array  $atts
     * @param  string $content
     * @param  string $tag
     * @return string
     */
    public function shortcode($atts = array(), $content = null, $tag = '')
    {
        // Fetch options and items
        $this->fetchData($atts);

        // No items found
        if ($this->count == 0) {
            return '';
        }

        $output = '';
        // Output before the list
        if (!empty($this->options['before'])) {
            $output .= $this->options['before'];
        }

        // The list
        $output .= '<'.$this->options['tag'].' class="msc-list '.$this->options['class'].'">';
        foreach ($this->items as $key => $values) {
            if ($key < $this->options['max'] || $this->options['max'] == -1) {
                $output .= $this->renderItem($key, $values);
            }
        }
        $output .= '</'.$this->options['tag'].'>';

        // Output after the list
        if (!empty($this->options['after'])) {
            $output .= $this->options['after'];
        }

        return $output;
    }

    /**
     * Render an item in the list
     *
     * @param  int    $key
     * @param  array  $values
     * @return string
     */
    protected function renderItem($key, $values)
    {
        // Item format
        $item = $this->replacePlaceholders($values);

        // Add class attribute 'last' to the last item
        if (($key + 1) >= $this->count) {
            $item = preg_replace('/(class(\s*)=(\s*)["|\'])/', '$1last ', $item);
        }

        return $item;
    }

    /**
     * Replace item values with the place holders. {$1} to be replaced with the first value
     * With filters the keys are used to replace the placeholders instead of the value (See Posts shortcode).
     *
     * @param  array   $values
     * @param  boolean $useKeys
     * @return string
     */
    protected function replacePlaceholders(array $values, $useKeys = false)
    {
        $item = $this->getFormat();
        foreach ($values as $index => $value) {
            if ($useKeys) {
                $value = $index;
            }
            $item = str_replace('{$'.($index + 1).'}', $this->filterValue($value, $index), $item);
        }

        return $item;
    }

    /**
     * Filter an item value
     *
     * @param  string      $value
     * @param  int         $index
     * @return string|null
     */
    protected function filterValue($value, $index)
    {
        // No filter found
        if (empty($this->filters[$index])) {
            return $value;
        }

        // Split the parameters from the filter name (first item)
        $params = explode(':', $this->filters[$index]);
        $filter = array_shift($params);
        $method = 'filter'.ucfirst($filter);

        // Execute filter method if exists and pass the parameters as the arguments with the raw value
        if (method_exists($this, $method)) {
            if (!empty($params)) {
                array_unshift($params, $value);

                return call_user_func_array(array($this, $method), $params);
            }

            return $this->$method($value);
        }

        return $value;
    }

    /**
     * Filter a value to an string date
     *
     * @param  string $value
     * @param  string $format
     * @return string
     */
    protected function filterDate($value, $format)
    {
        if (!$format) {
            $format = 'Y-m-d';
        }

        $date = new DateTime($value);

        return $date->format($format);
    }

    /**
     * Filter a value to an integer
     *
     * @param  string $value
     * @return int
     */
    protected function filterInt($value)
    {
        return (int) $value;
    }

    /**
     * Filter an option value
     *
     * @param  string     $value
     * @param  string     $filter
     * @return string|int
     */
    protected function filterOption($value, $filter = self::PARAM_FILTER_STRING)
    {
        if ($filter == self::PARAM_FILTER_INT) {
            return $this->filterInt($value);
        }

        return $value;
    }

    /**
     * Fetch items, sort items & count items.
     * Fetch shortcode options.
     * Fetch shortcode values filters.
     *
     * @param array $atts
     */
    protected function fetchData($atts)
    {
        $this->count = 0;
        $this->items = array();
        $this->options = $this->defaultOptions;

        if (!is_array($atts)) {
            return $this;
        }

        // Extract items and filters and options from the tag attributes
        foreach ($atts as $name => $value) {
            if (strpos($name, 'item') === 0) {
                $valueParts = explode($this->options['delimiter'], $value);
                $this->items[$this->count] = $valueParts;
                $this->count++;
            } elseif (isset($this->options[$name])) {
                $filter = !empty($this->optionsFilters[$name]) ? $this->optionsFilters[$name] : self::PARAM_FILTER_STRING;
                $this->options[$name] = $this->filterOption($value, $filter);
            } elseif (strpos($name, 'filter') === 0) {
                $this->filters[] = $value;
            }
        }

        // No sort
        if ($this->options['sort'] == 'none') {
            return $this;
        }

        // Random or sort items
        if ($this->options['sort'] == 'rand') {
            shuffle($this->items);
        } else {
            usort($this->items, array($this, 'sortItems'));
        }

        return $this;
    }

    /**
     * Callback method used to sort the items using 'usort'. See self::fetchData()
     * Sorting the items based on the first value
     *
     * @param  array $a
     * @param  array $b
     * @return int
     */
    protected function sortItems($a, $b)
    {
        if ($a[0] == $b[0]) {
            return 0;
        }

        if ($this->options['sort'] == 'asc') {
            return ($a[0] < $b[0]) ? -1 : 1;
        }

        return ($a[0] < $b[0]) ? 1 : -1;
    }

    /**
     * Returns item format
     *
     * @return string
     */
    protected function getFormat()
    {
        // Replace {baseurl} with site base URL
        $item = str_replace('{baseurl}', get_site_url(), $this->options['format']);

        // Replace tags placeholders
        return str_replace(array('%}', '{%'), array('>', '<'), $item);
    }

    /**
     * Form elements for TinyMCE plugin
     *
     * @return array
     */
    public function getFormElements()
    {
        return array(
            'header'    => array(
                'type'  => MceDialogAwareInterface::ELEMENT_HEADER,
                'title' => 'General',
            ),
            'format'    => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXTAREA,
                'label'    => 'Format',
                'value'    => $this->defaultOptions['format'],
                'datatype' => self::PARAM_FILTER_STRING,
            ),
            'class'     => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXT,
                'label'    => 'class',
                'value'    => $this->defaultOptions['class'],
                'datatype' => self::PARAM_FILTER_STRING,
            ),
            'tag'       => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXT,
                'label'    => 'tag',
                'value'    => $this->defaultOptions['tag'],
                'datatype' => self::PARAM_FILTER_STRING,
            ),
            'max'       => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXT,
                'label'    => 'max',
                'value'    => $this->defaultOptions['max'],
                'datatype' => self::PARAM_FILTER_INT,
            ),
            'sort'      => array(
                'type'    => MceDialogAwareInterface::ELEMENT_SELECT,
                'label'   => 'sort',
                'value'   => $this->defaultOptions['sort'],
                'options' => array('asc', 'desc', 'rand'),
            ),
            'before'    => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXTAREA,
                'label'    => 'before',
                'value'    => $this->defaultOptions['before'],
                'datatype' => self::PARAM_FILTER_STRING,
            ),
            'after'     => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXTAREA,
                'label'    => 'after',
                'name'     => 'after',
                'value'    => $this->defaultOptions['after'],
                'datatype' => self::PARAM_FILTER_STRING,
            ),
            'delimiter' => array(
                'type'     => MceDialogAwareInterface::ELEMENT_TEXT,
                'label'    => 'delimiter',
                'value'    => $this->defaultOptions['delimiter'],
                'datatype' => self::PARAM_FILTER_STRING,
            ),
            'header2'   => array(
                'type'  => MceDialogAwareInterface::ELEMENT_HEADER,
                'title' => 'Items',
            ),
            'item'      => array(
                'type'    => MceDialogAwareInterface::ELEMENT_ITEM,
                'filters' => array(
                    self::PARAM_FILTER_STRING => array(
                        'label' => 'Text',
                    ),
                    self::PARAM_FILTER_INT    => array(
                        'label' => 'Integer',
                    ),
                    self::PARAM_FILTER_DATE   => array(
                        'label'  => 'Date',
                        'params' => array('Date format'),
                    ),
                ),
            ),
        );
    }
}
