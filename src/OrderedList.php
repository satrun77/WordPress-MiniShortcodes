<?php

defined('MOO_MINSHORTCODE') or die;

/**
 * A shortcode to display a list of ordered items based on thier dates
 * Usage:
 *  [moo_orderedlist sort="asc" max="5" delimiter="|" class="mylist" format="{date:Y-m-d H:i:s} - {text:}" debug="true" item1="2012-10-11|Description..." item2="..."]
 *
 * @copyright  2012 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    1.0.0
 * @license    http://opensource.org/licenses/MIT
 */
class Moo_MiniShortcodes_OrderedList implements Moo_ShortcodeInterface
{

    /**
     * Total number of items
     *
     * @var int
     */
    protected $count = 0;

    /**
     * List of ordered items
     *
     * @var array
     */
    protected $items = array();

    /**
     * List of options to be used in the shortcode.
     *
     * Current options:
     * - class: Specific class name (CSS)
     * - delimiter: Character used to specify the separation between date & text - Detault is |
     * - format: Item format. - Default is {date:Y-m-d H:i:s} - {text:}
     * - sort: Sort the items based on the date ASC or DESC - Default is DESC
     * - max: Maximum number of items to show - Default is 5
     *
     * @var array
     */
    protected $options = array();

    /**
     * DateTime object
     *
     * @var DateTime
     */
    protected $dateTime;

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
        $this->fetchOptions($atts);
        $this->fetchData($atts);

        // no items found
        if ($this->count == 0) {
            return '';
        }

        $counter = 0;
        $output = '<ul class="ordered-list ' . $this->options['class'] . '">';
        foreach ($this->items as $key => $value) {
            if ($counter == $this->options['max']) {
                break;
            }
            $lastItem = (($counter + 1) === $this->options['max']) ? ' class="last"' : '';
            $output .= '<li' . $lastItem . '>' . $this->renderItem($value) . '</li>';
            $counter++;
        }
        $output .= '<ul>';

        return $output;
    }

    /**
     * Fetch the options from the shortcode tag
     *
     * @param array $atts
     */
    protected function fetchOptions($atts)
    {
        $this->options = array();
        $this->options['class'] = '';
        if (isset($atts['class'])) {
            $this->options['class'] = $atts['class'];
        }

        $this->options['delimiter'] = '|';
        if (isset($atts['delimiter'])) {
            $this->options['delimiter'] = $atts['delimiter'];
        }

        $this->options['max'] = 5;
        if (isset($atts['max'])) {
            $this->options['max'] = (int) $atts['max'];
        }

        $this->options['sort'] = 'desc';
        if (isset($atts['sort'])) {
            $this->options['sort'] = $atts['sort'];
        }

        $this->options['format'] = '{date:Y-m-d H:i:s} - {text:}';
        if (isset($atts['format']) && $atts['format'] != '') {
            $this->options['format'] = $atts['format'];
        }

        $date = '';
        $dateFound = preg_match("/\{date:(.*?)\}/", $this->options['format'], $date);
        if ($dateFound && $date[1] != '') {
            $this->options['dateFormat'] = $date[1];
            $this->options['dateTag'] = $date[0];
        }

        $text = '';
        $textFound = preg_match("/\{text:(.*?)\}/", $this->options['format'], $text);
        if ($textFound) {
            $this->options['textFormat'] = $text[1];
            $this->options['textTag'] = $text[0];
        }
    }

    /**
     * Fetch, sort, & count the ordered items
     *
     * @param array $atts
     */
    protected function fetchData($atts)
    {
        $this->count = 0;
        $this->items = array();
        foreach ($atts as $name => $value) {
            if (strpos($name, 'item') === 0) {
                $valueParts = explode($this->options['delimiter'], $value);
                if (isset($valueParts[1])) {
                    $valueParts[0] = strtotime($valueParts[0]);
                } else {
                    array_unshift($valueParts, '0');
                }
                $this->items[$this->count] = $valueParts;
                $this->count++;
            } else {
                $this->options[$name] = $value;
            }
        }
        usort($this->items, array($this, 'sortItems'));
    }

    /**
     * Callback method used to sort the items using 'usort'. See self::fetchData()
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

    /**
     * Render an item
     *
     * @param int $key
     * @param array $value
     * @return string
     */
    protected function renderItem($value)
    {
        // date and text are required
        if (!isset($value[0]) || !isset($value[1])) {
            return '';
        }

        $date = '';
        $text = html_entity_decode($value[1], 0, 'UTF-8');

        // format date if exists
        if ($value[0] > 0) {
            $date = date($this->options['dateFormat'], $value[0]);
        }

        $output = $this->options['format'];
        $output = str_replace($this->options['dateTag'], $date, $output);
        $output = str_replace($this->options['textTag'], $text, $output);

        return $output;
    }

}