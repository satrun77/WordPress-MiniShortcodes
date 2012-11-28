<?php

/*
 * Plugin Name: Mini Shortcodes
 * Plugin URI: http://
 * Description: This plugin contains different mini "shortcodes". Currently there are 3. (1) Display age based on a date. (2) List of items and dates ordered by the dates ascending or descending. (3) Display item(s) based on defined rule(s).
 * Author: Mohamed Alsharaf
 * Version: 1.0.0
 * Author URI: http://jamandcheese-on-phptoast.com
 * License: http://opensource.org/licenses/MIT
 */
define('MOO_MINSHORTCODE', 1);

require_once dirname(__FILE__) . '/src/ShortcodeInterface.php';

class Moo_MiniShortcodes
{

    /**
     * List of shortcodes class instances
     *
     * @var array
     */
    protected $shortcodes = array();

    /**
     * Instantiate shortcodes instances
     *
     * @return void
     */
    public function start()
    {
        try {
            $this->initShortcode('Age');
            $this->initShortcode('OrderedList');
            $this->initShortcode('DisplayMe');

            // make shortcodes available inside widgets
            add_filter('widget_text', 'do_shortcode');
        } catch (Exception $e) {
            $message = $e->getMessage() . "\n"
                    . $e->getFile() . " - " . $e->getLine() . "\n"
                    . $e->getTraceAsString() . "\n";
            error_log($message);
        }
    }

    /**
     * Instantiate a shortcode
     *
     * @param string $name
     * @return Moo_ShortcodeInterface
     * @throws Exception
     */
    protected function initShortcode($name)
    {
        // create new instance of a shortcode if it does not exists
        $tagName = $this->getTagName($name);
        if (!isset($this->shortcodes[$tagName])) {

            $file = dirname(__FILE__) . '/src/' . $name . '.php';
            if (!realpath($file)) {
                throw new Exception(sprintf("Shortcode '%s' does not exists.", $name));
            }
            require_once $file;

            $className = 'Moo_MiniShortcodes_' . $name;
            $this->shortcodes[$tagName] = new $className;

            if (!$this->shortcodes[$tagName] instanceof Moo_ShortcodeInterface) {
                throw new Exception(sprintf("The shortcode '%s' class must be an instance of Moo_ShortcodeInterface.", $tagName));
            }
        }

        // register shortcode with wordpress
        add_shortcode($tagName, array($this, 'shortcode'));

        return $this->shortcodes[$name];
    }

    /**
     * Callback function to be triggered by wordpress to process a shortcode
     *
     * @param array $atts
     * @param string $content
     * @param string $tag
     * @return type
     * @throws Exception
     */
    public function shortcode($atts = array(), $content = null, $tag = '')
    {
        try {
            if (!isset($this->shortcodes[$tag])) {
                throw new Exception(sprintf("Ivalid shortcode '%s'.", $tag));
            }

            return $this->shortcodes[$tag]->shortcode($atts, $content, $tag);
        } catch (Exception $e) {
            if (isset($atts['debug']) && $atts['debug'] == true) {
                return $e->getMessage();
            }
            return '';
        }
    }

    /**
     * Construct shortcode tag name
     *
     * @param string $className
     * @return string
     */
    protected function getTagName($className)
    {
        return 'moo_' . strtolower($className);
    }

}

$mooShortcode = new Moo_MiniShortcodes;
$mooShortcode->start();
