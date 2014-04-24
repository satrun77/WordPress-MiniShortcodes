<?php

defined('MOO_MINSHORTCODE') or die;

/**
 * A shortcode to display a list of items.
 *
 * Usage:
 *  [moo_list item1="value1|value2..." item2="value1|value2..." ...]
 *
 * @copyright  2014 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    2.0.0
 * @license    The MIT License (MIT)
 */
class Moo_MiniShortcodes_List implements Moo_ShortcodeInterface
{
    /** parameters filters */
    const PARAM_FILTER_STRING = 'string';
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
     * Current options:
     * - class: Specific class name (CSS)
     * - delimiter: Character used to specify the separation between item values - Detault is |
     * - format: Item format - Default is empty
     * - sort: Sort the items based on the first item value ASC or DESC - Default is DESC
     * - max: Maximum number of items to show - Default is 5
     * - tag: The name of the tag to be used as a wrapper tag of the list - Default is div
     * - before: any text or html to be displayed before the list.
     * - after: any test or html to be displayed after the list.
     *
     * @var array
     */
    protected $options = array();
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
     * To override by a subclass
     *
     * @return void
     */
    protected function init()
    {
        
    }

    /**
     * Shortcode callback method
     *
     * @param array $atts
     * @param string $content
     * @param string $tag
     * @return string
     * @throws Exception
     */
    public function shortcode($atts = array(), $content = null, $tag = '')
    {
        // Initiate and then fetch options and items
        $this->init();
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
        $output .= '<' . $this->options['tag'] . ' class="msc-list ' . $this->options['class'] . '">';
        foreach ($this->items as $key => $values) {
            if ($key < $this->options['max'] || $this->options['max'] == -1) {
                $output .= $this->renderItem($key, $values);
            }
        }
        $output .= '</' . $this->options['tag'] . '>';

        // Output after the list
        if (!empty($this->options['after'])) {
            $output .= $this->options['after'];
        }

        return $output;
    }

    /**
     * Render an item in the list
     *
     * @param int $key
     * @param array $values
     * @return string
     */
    protected function renderItem($key, $values)
    {
        // Replace {baseurl} with site base URL
        $item = str_replace('{baseurl}', get_site_url(), $this->options['format']);

        // Replace item values with the place holders. {$1} to be replaced with the first value
        foreach ($values as $index => $value) {
            $item = str_replace('{$' . ($index + 1) . '}', $this->filterValue($value, $index), $item);
        }

        // Add class attribute 'last' to the last item
        // TODO: if class attribute does not exists, then add it.
        if (($key + 1) >= $this->count) {
            $item = preg_replace('/(class(\s*)=(\s*)["|\'])/', '$1last ', $item);
        }

        return $item;
    }

    /**
     * Filter an item value
     *
     * @param string $value
     * @param int $index
     * @return mix
     */
    protected function filterValue($value, $index)
    {
        // No filter found
        if (empty($this->filters[$index])) {
            return $value;
        }

        $params = explode(':', $this->filters[$index]);
        $filter = array_shift($params);
        $method = 'filter' . ucfirst($filter);

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
     * @param string $value
     * @param string $format
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
     * @param mix $value
     * @return int
     */
    protected function filterInt($value)
    {
        return (int) $value;
    }

    /**
     * Filter an option value
     *
     * @param string $value
     * @param string $filter
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
     * @param array $a
     * @param array $b
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

}
