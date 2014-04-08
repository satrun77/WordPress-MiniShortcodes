<?php

defined('MOO_MINSHORTCODE') or die;

/**
 * A shortcode to display age as string based on a date
 * Usage:
 *  [moo_age date="1982-1-13" append=" years old"]
 *
 * @copyright  2012 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    1.0.0
 * @license    The MIT License (MIT)
 */
class Moo_MiniShortcodes_Age implements Moo_ShortcodeInterface
{

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
        if (!isset($atts['date'])) {
            throw new Exception('Date parameter is missing.');
        }

        $birthdate = new DateTime($atts['date']);
        $now = new DateTime();
        $diff = $now->format("Y") - $birthdate->format("Y");
        if ($diff <= 0) {
            throw new Exception(sprintf("You are an unborn child! [Your age is %s]", $diff));
        }

        $append = isset($atts['append']) ? $atts['append'] : ' years old';
        return $diff . $append;
    }

}
