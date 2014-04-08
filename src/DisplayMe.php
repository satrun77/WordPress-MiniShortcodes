<?php

defined('MOO_MINSHORTCODE') or die;

/**
 * A shortcode to display something(s) after a defined rule(s)
 * Usage:
 *  [moo_displayme type="[random|rules]" ondate="2012-13-1" beforedate="" afterdate="" iplocation="" show="2" debug="true" item1="one" item2="one" item3="one"]
 *  [moo_displayme type="random" show="1" debug="true" item1="one" item2="two" item3="three"]
 *  [moo_displayme type="rules" ondate="2012-13-1" show="2" debug="true" item1="<h1>one</h1>" item2="<h1>two</h1>" item3="<h1>three</h1>"]
 * 
 * Ip check uses: http://freegeoip.net/json/130.123.96.22 (not implemented)
 * 
 * @copyright  2012 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    1.0.0
 * @license    The MIT License (MIT)
 */
class Moo_MiniShortCodes_DisplayMe implements Moo_ShortcodeInterface
{
    const TYPE_RAND = 'random';
    const TYPE_RULE = 'rules';

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
    protected $types = array(
        self::TYPE_RAND, self::TYPE_RULE
    );

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
        $this->validateAttributes($atts);

        if ($this->options['type'] == self::TYPE_RAND) {
            return $this->itemRandomly();
        }

        if ($this->options['type'] == self::TYPE_RULE) {
            return $this->itemByRule();
        }

        return '';
    }

    protected function validateAttributes($atts)
    {
        $this->fetchData($atts);

        if ($this->count == 0) {
            throw new Exception('There are no items to display.');
        }

        if (!in_array($this->options['type'], $this->types)) {
            throw new Exception(sprintf('Type attribute is incorrect. Allowed values are: [%s]', join('|', $this->types)));
        }

        return true;
    }

    protected function itemRandomly()
    {
        if ($this->options['show'] > $this->count) {
            $this->options['show'] = $this->count;
        }

        $itemKey = array_rand($this->items, $this->options['show']);

        return html_entity_decode($this->items[$itemKey], 0, 'UTF-8');
    }

    protected function itemByRule()
    {
        if (!$this->isValidDate()) {
            return '';
        }

        if (!$this->isValidLocation()) {
            return '';
        }

        $output = '';
        for ($i = 0; $i < $this->options['show']; $i++) {
            if (isset($this->items[$i])) {
                $output .= $this->items[$i];
            }
        }
        return $output;
    }

    protected function isValidDate()
    {
        $now = new DateTime;

        // ondate validations
        if (isset($this->options['ondate'])) {
            $dateParts = date_parse($this->options['ondate']);
            if ($dateParts === false) {
                throw new Exception("Invalid date value for 'ondate'.");
            }

            $dateFormat = array(
                'year'   => 'Y',
                'month'  => 'n',
                'day'    => 'j',
                'hour'   => 'H',
                'minute' => 'i',
                'second' => 's'
            );
            foreach ($dateParts as $part => $value) {
                if ($value !== false
                        && isset($dateFormat[$part])
                        && $now->format($dateFormat[$part]) != $value) {
                    return false;
                }
            }

            return true;
        }

        if (isset($this->options['beforedate'])) {
            $timestamp = strtotime($this->options['beforedate']);
            if ($timestamp < 0) {
                throw new Exception("Invalid date value for 'beforedate'.");
            }

            if ($now->format("U") > $timestamp) {
                return false;
            }
        }

        if (isset($this->options['afterdate'])) {
            $timestamp = strtotime($this->options['afterdate']);
            if ($timestamp < 0) {
                throw new Exception("Invalid date value for 'afterdate'.");
            }

            if ($now->format("U") < $timestamp) {
                return false;
            }
        }

        return true;
    }

    protected function isValidLocation()
    {
        if (!isset($this->options['iplocation'])) {
            return true;
        }

        $url = 'http://freegeoip.net/json/' . $this->getClientIp();
        $jsonString = file_get_contents($url, 0, null, null);
        $json = json_decode($jsonString);
        var_dump($json);
        die;
    }

    /**
     * Returns the client IP address.
     * Code from Symfony 2 Symfony\Component\HttpFoundation\Request
     * 
     * @return string
     */
    protected function getClientIp()
    {
        if ($this->options['proxy']) {
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $clientIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                foreach ($clientIp as $ipAddress) {
                    $cleanIpAddress = trim($ipAddress);

                    if (false !== filter_var($cleanIpAddress, FILTER_VALIDATE_IP)) {
                        return $cleanIpAddress;
                    }
                }

                return '';
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Fetch, sort, & count the ordered items
     * 
     * @param array $atts
     */
    protected function fetchData($atts)
    {
        $this->count = 0;
        foreach ($atts as $name => $value) {
            if (strpos($name, 'item') === 0) {
                $this->items[$this->count] = $value;
                $this->count++;
            } else {
                $this->options[$name] = $value;
            }
        }

        if (!isset($this->options['type'])) {
            $this->options['type'] = self::TYPE_RAND;
        }

        if (!isset($this->options['show'])) {
            $this->options['show'] = 1;
        }

        if (!isset($this->options['proxy'])) {
            $this->options['proxy'] = 0;
        }
    }

}
