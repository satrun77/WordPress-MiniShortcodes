<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moo\MiniShortcode\Shortcode;

defined('MOO_MINISHORTCODE') or die;

use Moo\MiniShortcode\ShortcodeInterface;
use Moo\MiniShortcode\MceDialogAwareInterface;
use \DateTime as DateTime;
use \Exception as Exception;

/**
 * A shortcode to display age as string based on a date
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Age implements ShortcodeInterface, MceDialogAwareInterface
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

        $append = isset($atts['append']) ? $atts['append'] : __(' years old');
        return $diff . $append;
    }

    /**
     * Form elements for TinyMCE plugin
     *
     * @return array
     */
    public function getFormElements()
    {
        return array(
            'date'   => array(
                'type'  => self::ELEMENT_TEXT,
                'label' => 'Date',
                'name'  => 'date',
                'value' => '',
            ),
            'append' => array(
                'type'  => self::ELEMENT_TEXT,
                'label' => 'append',
                'name'  => 'append',
                'value' => ' years old',
            )
        );
    }

}
