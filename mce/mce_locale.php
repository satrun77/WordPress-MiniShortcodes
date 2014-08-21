<?php

/*
 * This file is part of the Moo_MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!class_exists('_WP_Editors')) {
    require ABSPATH . WPINC . '/class-wp-editor.php';
}

$stringList = array(
    'desc'          => __('Mini Shortcodes', 'minishotcodes'),
    'popupTitle'    => __('Mini Shortcodes', 'minishotcodes'),
    'delete'        => __('Delete', 'minishotcodes'),
    'item'          => __('Item', 'minishotcodes'),
    'value'         => __('Value', 'minishotcodes'),
    'filter'        => __('Filter', 'minishotcodes'),
    'filter_params' => __('Filter parameters', 'minishotcodes'),
);
$strings = 'tinyMCE.addI18n("' . _WP_Editors::$mce_locale . '.minishotcodes", ' . json_encode($stringList) . ");\n";
