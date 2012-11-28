<?php

/**
 * A shortcode to display something(s) after a defined rule(s)
 * Usage:
 *  [moo_displayme type="[random|rules]" ondate="2012-13-1" beforedate="" afterdate="" countries="nz" cities="" regions="" show="2" debug="true" item1="one" item2="one" item3="one"]
 *
 * @copyright  2012 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    1.0.0
 * @license    http://framework.zend.com/license/new-bsd New BSD License
 */
class Moo_MiniShortCodes_DisplayMe implements Moo_ShortCodeInterface
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
     * - type: The type of the display me (random or rules)
     * - show: Number of items to display.
     * - ondate: On that date display me
     * - beforedate: If the current date before that date, then display me
     * - afterdate: If the current date after that date, then display me
     * - countries: List of countries codes seperated by comma. If the user IP address from a country defined in the list, then display me
     * - cities: Same as countries but for cities names
     * - regions: Same as countries but for regions nams
     *
     * @var array
     */
    protected $options = array();

    /**
     * List of allowed display me types
     *
     * @var array
     */
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

    /**
     * Display random item(s)
     *
     * @return string
     */
    protected function itemRandomly()
    {
        if ($this->options['show'] > $this->count) {
            $this->options['show'] = $this->count;
        }

        $itemKeys = array_rand($this->items, $this->options['show']);
        if ($this->options['show'] > 1) {
            $ouput = '';
            foreach ($itemKeys as $itemKey) {
                $ouput .= html_entity_decode($this->items[$itemKey], 0, 'UTF-8');
            }
            return $ouput;
        }

        return html_entity_decode($this->items[$itemKeys], 0, 'UTF-8');
    }

    /**
     * Display item(s) based on rules
     *
     * @return string
     */
    protected function itemByRule()
    {
        if (!$this->isValidDate() || !$this->isValidLocation()) {
            return '';
        }

        $ouput = '';
        for ($i = 0; $i < $this->options['show']; $i++) {
            $ouput .= $this->items[$i];
        }
        return $ouput;
    }

    /**
     * Validate shortcode attributes
     *
     * @param array $atts
     * @return boolean
     * @throws Exception
     */
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

    /**
     * Validate shortcode based on date parameters
     *
     * @return boolean
     * @throws Exception
     */
    protected function isValidDate()
    {
        $now = new DateTime;

        // ondate validation
        if (isset($this->options['ondate'])) {
            $dateParts = date_parse($this->options['ondate']);
            if ($dateParts === false) {
                throw new Exception("Invalid date value for 'ondate'.");
            }

            $dateFormat = array(
                'year' => 'Y',
                'month' => 'n',
                'day' => 'j',
                'hour' => 'H',
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

        // beforedate validation
        if (isset($this->options['beforedate'])) {
            $timestamp = strtotime($this->options['beforedate']);
            if ($timestamp < 0) {
                throw new Exception("Invalid date value for 'beforedate'.");
            }

            if ($now->format("U") > $timestamp) {
                return false;
            }
        }

        // afterdate validation
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

    /**
     * Validate shortcode based on geolocation using the IP address
     * This method uses a free geolocator service http://freegeoip.net
     *
     * @return boolean
     */
    protected function isValidLocation()
    {
        if (!isset($this->options['countries']) && !isset($this->options['cities']) && !isset($this->options['regions'])) {
            return true;
        }

        $url = 'http://freegeoip.net/json/' . $this->getClientIp();
        $jsonString = file_get_contents($url, 0, null, null);
        $json = json_decode($jsonString);

        // filter by country
        if (isset($this->options['countries'])
                && strpos(strtolower($this->options['countries']), strtolower($json->country_code)) === false) {
            return false;
        }

        if (isset($this->options['cities'])
                && strpos(strtolower($this->options['cities']), strtolower($json->city)) === false) {
            return false;
        }

        if (isset($this->options['regions'])
                && strpos(strtolower($this->options['regions']), strtolower($json->region_name)) === false) {
            return false;
        }

        return true;
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
     * Fetch items and options from the shortcode attributes.
     *
     * @param array $atts
     */
    protected function fetchData($atts)
    {
        $this->options = array();
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
    }

}