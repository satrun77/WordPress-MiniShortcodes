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

/**
 * ShortcodeInterface is an interface that must be implemented by a shortcode class
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
interface ShortcodeInterface
{
    /**
     * @param string $content
     *
     * @return string
     */
    public function shortcode($atts = array(), $content = null, $tag = '');
}
