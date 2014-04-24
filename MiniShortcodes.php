<?php

/*
 * Plugin Name: Mini ShortCodes
 * Plugin URI: http://
 * Description: This plugin contains mini "shortcodes". (1) Display age based on a date. (2) Display list of items. (3) Display item(s) based on defined rule(s). (4) List of photos from Instagram. (5) Display list of posts.
 * Author: Mohamed Alsharaf
 * Version: 1.1.0
 * Author URI: http://my.geek.nz
 * License: The MIT License (MIT)
 */
define('MOO_MINSHORTCODE', 1);

require_once dirname(__FILE__) . '/src/ShortCodeInterface.php';

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
            $this->initShortCode('Age');
            $this->initShortCode('DisplayMe');
            $this->initShortCode('Posts');
            $this->initShortCode('List');
            $this->initShortCode('Instagram');
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
     * @return Moo_ShortCodeInterface
     * @throws Exception
     */
    protected function initShortCode($name)
    {
        // instantiate short code if new
        $tagName = $this->getTagName($name);
        if (!isset($this->shortcodes[$tagName])) {

            // load shortcode class
            $file = dirname(__FILE__) . '/src/' . $name . '.php';
            if (!realpath($file)) {
                throw new Exception(sprintf("Shortcode '%s' does not exists.", $name));
            }
            require_once $file;

            $className = 'Moo_MiniShortcodes_' . $name;
            $this->shortcodes[$tagName] = new $className;

            if (!$this->shortcodes[$tagName] instanceof Moo_ShortCodeInterface) {
                throw new Exception(sprintf("The shortcode '%s' class must be an instance of Moo_ShortCodeInterface.", $tagName));
            }
        }

        // register shortcode with wordpress
        add_shortcode($tagName, array($this, 'shortcode'));

        return $this->shortcodes[$tagName];
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
