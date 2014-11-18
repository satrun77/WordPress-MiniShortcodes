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
 * McePlugin is a class for TinyMCE plugin.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class McePlugin
{
    /**
     * An instance of the minishortcode main class
     *
     * @var \Moo\MiniShortcode\ShortcodePlugin
     */
    protected $plugin;

    /**
     * Current version
     *
     * @var string
     */
    const VERSION = '1.4';

    /**
     * Set minishortcode main class
     *
     * @param  \Moo\MiniShortcode\ShortcodePlugin $plugin
     * @return \Moo\MiniShortcode\ShortcodePlugin
     */
    public function setPlugin(ShortcodePlugin $plugin)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Initiate Mce plugin used by add/remove_action callback
     *
     * @return boolean
     */
    public function init()
    {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return false;
        }

        if (get_user_option('rich_editing') == 'true') {
            add_filter('mce_external_plugins', array($this, 'registerPlugin'));
            add_filter('mce_buttons', array($this, 'registerButton'));
            add_action('admin_footer', array($this, 'registerPluginDialog'));
            add_filter('mce_external_languages', array($this, 'registerLocale'));
            add_action('admin_head', array($this, 'registerHead'));
            add_filter('tiny_mce_before_init', array($this, 'registerMceStyles'));
        }

        return true;
    }

    /**
     * Register CSS used by the plugin dialog
     *
     * @return void
     */
    public function registerHead()
    {
        echo '<link rel="stylesheet" href="'.$this->getUrl('dialog.css').'" type="text/css" />';
    }

    /**
     * Register locale file
     *
     * @param  array  $locale
     * @return string
     */
    public function registerLocale($locale)
    {
        $locale['minishotcodes'] = $this->gePath('mce_locale.php');

        return $locale;
    }

    /**
     * Register the TinyMCE plugin JS file
     *
     * @param  array  $plugin
     * @return string
     */
    public function registerPlugin($plugin)
    {
        $plugin['minishotcodes'] = $this->getUrl('minishotcodes_tinymce.js');

        return $plugin;
    }

    /**
     * Register the TinyMCE button
     *
     * @param  array  $buttons
     * @return string
     */
    public function registerButton($buttons)
    {
        $buttons[] = 'minishotcodes_button';

        return $buttons;
    }

    /**
     * Register CSS used in the content of TinyMCE
     *
     * @param  array $plugin
     * @return array
     */
    public function registerMceStyles($plugin)
    {
        if (!empty($plugin['content_css'])) {
            $plugin['content_css'] .= ",";
        } else {
            $plugin['content_css'] = '';
        }
        $plugin['content_css'] .= $this->getUrl('editor.css');

        return $plugin;
    }

    /**
     * Register plugin dialog
     *
     * @return void
     */
    public function registerPluginDialog()
    {
        $output = '';

        $output .= '<div style="display:block;" id="msc-dialog-wrapper">';
        foreach ($this->plugin->getShortcodes() as $tag => $shortcode) {
            // Shortcode class must implements MceDialogAwareInterface
            if (!$shortcode instanceof MceDialogAwareInterface) {
                continue;
            }

            // Render the shortcode form
            $tagName = strtolower($tag);
            $output .= '<h2>'.__('Shortcode').': '.$tag.'</h2>';
            $output .= '<form id="msc-form-'.$tagName.'" class="msc-form close">';
            $output .= $this->renderDialogForm($shortcode->getFormElements());
            $output .= '<div class="submitbox"><div class="msc-update"><input type="submit" value="'.__('Add').'" class="button-primary msc-submit" data-tag="'.$tagName.'" name="msc-'.$tagName.'-submit"></div></div>';
            $output .= '</form>';
        }
        $output .= '</div>';

        echo $output;
    }

    /**
     * Render form elements for a shortcode
     *
     * @param  array  $elements
     * @return string
     */
    protected function renderDialogForm(array $elements)
    {
        $output = '';

        foreach ($elements as $name => $element) {
            // Find element method in current class
            $method = sprintf('render%sElement', ucfirst($element['type']));
            if (!method_exists($this, $method)) {
                continue;
            }

            // Render the element and the label
            $output .= '<div class="msc-element">';
            if (!empty($element['label'])) {
                $output .= $this->renderLabel($name, $element);
            }
            $output .= $this->$method($name, $element);
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Render a label element
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    protected function renderLabel($name, array $element)
    {
        return sprintf('<label for="msc-%s-field"><span>%s</span></label>', $name, __($element['label']));
    }

    /**
     * Render text field element
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    public function renderTextElement($name, array $element)
    {
        $dataType = '';
        if (!empty($element['datatype'])) {
            $dataType = 'data-datatype="'.$element['datatype'].'"';
        }

        return sprintf('<input id="msc-%s-field" type="text" name="%s" value="%s" %s/>', $name, $name, $element['value'], $dataType);
    }

    /**
     * Render textarea field element
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    public function renderTextareaElement($name, array $element)
    {
        return sprintf('<textarea id="msc-%s-field" name="%s">%s</textarea>', $name, $name, $element['value']);
    }

    /**
     * Render select element
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    public function renderSelectElement($name, array $element)
    {
        $html = sprintf('<select id="msc-%s-field" name="%s">', $name, $name);

        if (!empty($element['options']) && is_array($element['options'])) {
            foreach ($element['options'] as $option) {
                $selected = '';
                if ($option == $element['value']) {
                    $selected = 'selected="selected"';
                }
                $html .= sprintf('<option value="%s" %s>%s</option>', $option, $selected, $option);
            }
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Render item element. Toolbar with 2 optins to add item or value, and container ul tag for list of items
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    protected function renderItemElement($name, array $element)
    {
        $html = '<ul class="msc-toolbar">'
                .'<li class="msc-add-item"><a href="#">'.__('Add Item').'</a></li>'
                .'<li class="msc-add-value"><a href="#">'.__('Add Value').'</a></li>'
                .'</ul>';
        $html .= sprintf("<ul class='msc-item-list' id='msc-item-%s' data-filters='".json_encode($element['filters'])."'></ul>", $name);

        return $html;
    }

    /**
     * Render filter element. Toolbar with 1 optin to add a filter, and container ul tag for list of filters
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    protected function renderFilterElement($name, array $element)
    {
        $html = '<ul class="msc-toolbar">'
                .'<li class="msc-add-filter"><a href="#">'.__('Add Filter').'</a></li>'
                .'</ul>';
        $html .= sprintf("<ul class='msc-item-list' id='msc-item-%s' data-filters='%s'></ul>", $name, json_encode($element['filters']));

        return $html;
    }

    /**
     * Render header tag
     *
     * @param  string $name
     * @param  array  $element
     * @return string
     */
    public function renderHeaderElement($name, array $element)
    {
        return sprintf('<h3 class="msc-header msc-header-%s">%s</h3>', $name, __($element['title']));
    }

    /**
     * Returns a url to a file in the plugin
     *
     * @param  string $file
     * @return string
     */
    protected function getUrl($file)
    {
        return plugin_dir_url(dirname(__FILE__))."mce/".$file."?v=".self::VERSION;
    }

    /**
     * Returns a pth to a file in the plugin
     *
     * @param  string $file
     * @return string
     */
    protected function gePath($file)
    {
        return plugin_dir_path(dirname(__FILE__))."mce/".$file;
    }
}
