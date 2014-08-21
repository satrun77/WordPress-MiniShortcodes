<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moo\MiniShortcode;

use \Exception as Exception;

/**
 * ShortcodePlugin is the main class of the plugin. It setup the shortcodes and any other parts of the plugin (ie. mce button).
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class ShortcodePlugin
{
    /**
     * List of shortcodes class instances
     *
     * @var array
     */
    protected $shortcodes = array();

    /**
     * Constructor class. It setup the classes autoloader
     *
     */
    public function __construct()
    {
        // Setup class autoload
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Callback function to be triggered by wordpress to process a shortcode
     *
     * @param  array     $atts
     * @param  string    $content
     * @param  string    $tag
     * @return type
     * @throws Exception
     */
    public function shortcode($atts = array(), $content = null, $tag = '')
    {
        try {
            $shortcode = $this->getShortcode(substr($tag, 4));
            if (!$shortcode) {
                throw new Exception(sprintf("Ivalid shortcode '%s'.", $tag));
            }

            return $shortcode->shortcode($atts, $content, $tag);
        } catch (Exception $e) {
            if (isset($atts['debug']) && $atts['debug'] == true) {
                return $e->getMessage();
            }

            return '';
        }
    }

    /**
     * Set a shortcode to be activated
     *
     * @param  string                             $name
     * @return \Moo\MiniShortcode\ShortcodePlugin
     */
    public function addShortcode($name)
    {
        // Instantiate the shortcode if new
        if (!$this->hasShortcode($name)) {

            // Shortcode full name
            $tagName = $this->getTagName($name);

            // Instantiate a valid shortcode
            $className = '\\' . __NAMESPACE__ . '\\Shortcode\\' . $name;
            $this->shortcodes[$name] = new $className();

            if (!$this->shortcodes[$name] instanceof ShortcodeInterface) {
                throw new Exception(sprintf("The shortcode '%s' class must be an instance of %sShortcodeInterface.", __NAMESPACE__, $tagName));
            }
        }

        // Register the shortcode with wordpress
        add_shortcode($tagName, array($this, 'shortcode'));

        return $this;
    }

    /**
     * Remove a minishortcode from Wordpress
     *
     * @param  string                             $name
     * @return \Moo\MiniShortcode\ShortcodePlugin
     */
    public function removeShortcode($name)
    {
        if ($this->hasShortcode($name)) {
            unset($this->shortcodes[$name]);
            remove_shortcode($this->getTagName($name));
        }

        return $this;
    }

    /**
     * Whether or not a shortcode exists
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasShortcode($name)
    {
        return isset($this->shortcodes[$name]);
    }

    /**
     * Returns shortcode object
     *
     * @param  string                                        $name
     * @return boolean|\Moo\MiniShortcode\ShortcodeInterface
     */
    public function getShortcode($name)
    {
        if (!$this->hasShortcode($name)) {
            return false;
        }

        return $this->shortcodes[$name];
    }

    /**
     * Returns all of the shortcodes
     *
     * @return array
     */
    public function getShortcodes()
    {
        return $this->shortcodes;
    }

    /**
     * Add or remove Mce plugin from WordPress
     *
     * @param  boolean                            $status
     * @return \Moo\MiniShortcode\ShortcodePlugin
     */
    public function addMcePlugin($status = true)
    {
        $function = !$status ? 'remove_action' : 'add_action';
        $mce = new McePlugin();
        $mce->setPlugin($this);

        $function('init', array($mce, 'init'));

        return $this;
    }

    /**
     * Construct shortcode tag name
     *
     * @param  string $name
     * @return string
     */
    protected function getTagName($name)
    {
        return 'moo_' . strtolower($name);
    }

    /**
     * Autoload minishortcode classes
     *
     * @param  string  $class
     * @return boolean
     */
    public function autoload($class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        // Only autoload \Moo\MiniShortcode classes, & clear prefix from class name
        if (strpos($class, __NAMESPACE__) === false) {
            return false;
        }
        $class = str_replace(__NAMESPACE__, '', $class);

        // Load class file
        include __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        return true;
    }

}
