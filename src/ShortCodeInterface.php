<?php

defined('MOO_MINSHORTCODE') or die;

interface Moo_ShortcodeInterface
{

    public function shortcode($atts = array(), $content = null, $tag = '');
}
