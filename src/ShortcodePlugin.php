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
     * An instance of McePlugin class
     *
     * @var McePlugin
     */
    protected $mcePlugin;

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

            return $shortcode->shortcode($atts, $content, $tag);
        } catch (Exception $e) {
            if (isset($atts['debug']) && (boolean) $atts['debug'] === true) {
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
            $className = '\\'.__NAMESPACE__.'\\Shortcode\\'.$name;
            $this->shortcodes[$name] = new $className();

            if (!$this->shortcodes[$name] instanceof ShortcodeInterface) {
                throw new Exception(sprintf("The shortcode '%s' class must be an instance of %s\\ShortcodeInterface.", $tagName, __NAMESPACE__));
            }

            // Register the shortcode with wordpress
            if (!shortcode_exists($tagName)) {
                add_shortcode($tagName, array($this, 'shortcode'));
            }
        }

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
        }
        remove_shortcode($this->getTagName($name));

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
        $this->mcePlugin = new McePlugin();
        $this->mcePlugin->setPlugin($this);

        $function('init', array($this->mcePlugin, 'init'));

        return $this;
    }

    /**
     * Get an instance of McePlugin class
     *
     * @return McePlugin
     */
    public function getMcePlugin()
    {
        return $this->mcePlugin;
    }

    /**
     * Construct shortcode tag name
     *
     * @param  string $name
     * @return string
     */
    protected function getTagName($name)
    {
        return 'moo_'.strtolower($name);
    }

    /**
     * Autoload minishortcode classes
     *
     * @param  string  $classFullname
     * @return boolean
     */
    public function autoload($classFullname)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        $class = $classFullname;
        if ('\\' == $classFullname[0]) {
            $class = substr($classFullname, 1);
        }

        // Only autoload \Moo\MiniShortcode classes, & clear prefix from class name
        if (strpos($class, __NAMESPACE__) === false) {
            return false;
        }
        $class = str_replace(__NAMESPACE__, '', $class);

        // Load class file
        $file = __DIR__.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        if (file_exists($file)) {
            return include $file;
        }

        throw new \Exception(sprintf("Unable to load the class '%s'.", $classFullname));
    }
}
