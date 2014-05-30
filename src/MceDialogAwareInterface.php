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

defined('MOO_MINISHORTCODE') or die;

/**
 * MceDialogAwareInterface is an interface for shortcode class to implement if it can be used within the TinyMCE plugin.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
interface MceDialogAwareInterface
{
    const ELEMENT_TEXT = 'text';
    const ELEMENT_TEXTAREA = 'textarea';
    const ELEMENT_SELECT = 'select';
    const ELEMENT_ITEM = 'item';
    const ELEMENT_FILTER = 'filter';
    const ELEMENT_HEADER = 'header';

    public function getFormElements();
}
