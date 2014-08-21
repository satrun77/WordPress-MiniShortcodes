<?php

/*
 * Plugin Name: Mini Shortcodes
 * Plugin URI: http://
 * Description: This plugin contains mini "shortcodes". - Display age based on a date. Display list of items. Display list of photos from Instagram. Display blog posts.
 * Author: Mohamed Alsharaf
 * Version: 2.0.0
 * Author URI: http://my.geek.nz
 * License: The MIT License (MIT)
 */
define('MOO_MINISHORTCODE', 1);

// PHP 5.3 is minimum requirement for this plugin.
if (version_compare(PHP_VERSION, '5.3', '<')) {
    wp_die('<p>The <strong>Mini Shortcodes</strong> plugin requires PHP version 5.3 or greater.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => TRUE));
}

// Plugin main class
include 'src/ShortcodePlugin.php';

// Activate the plugin
try {
    $mooShortcode = new \Moo\MiniShortcode\ShortcodePlugin();
    $mooShortcode
            ->addShortcode('age')
            ->addShortcode('posts')
            ->addShortcode('listing')
            ->addShortcode('instagram')
            ->addMcePlugin();
} catch (Exception $e) {

    $message = $e->getMessage() . "\n"
            . $e->getFile() . " - " . $e->getLine() . "\n"
            . $e->getTraceAsString() . "\n";
    error_log($message);
}
