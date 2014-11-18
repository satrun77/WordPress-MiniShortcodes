<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MiniShortcodes_Test_McePlugin to test the class for managing the mce plugin.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MiniShortcodes_Test_McePlugin extends WP_UnitTestCase
{
    protected $plugin;
    protected $adminId;

    public function setUp()
    {
        parent::setUp();

        // Load plugin autoload
        include_once __DIR__.'/../src/ShortcodePlugin.php';

        $this->plugin = new \Moo\MiniShortcode\ShortcodePlugin();
        $this->plugin->addMcePlugin();
    }

    public function testWithoutDateShortcode()
    {
        $mce = $this->plugin->getMcePlugin();
        $this->assertInstanceOf('\\Moo\\MiniShortcode\\McePlugin', $mce);

        $this->assertFalse($mce->init());
        $this->loginAsAdmin();
        $this->assertTrue($mce->init());

        $this->assertArrayHasKey('minishotcodes', apply_filters('mce_external_plugins', array()));
        $this->assertContains('minishotcodes_button', apply_filters('mce_buttons', array()));
        $this->assertArrayHasKey('minishotcodes', apply_filters('mce_external_languages', array()));

        $css = apply_filters('tiny_mce_before_init', array());
        $this->assertArrayHasKey('content_css', $css);
        $this->assertContains('mce/editor.css', $css['content_css']);

        $this->assertContains('mce/dialog.css', $this->captureFilterOutput('admin_head', null));

        $this->plugin
                ->addShortcode('age')
                ->addShortcode('posts')
                ->addShortcode('listing');
        $dialog = $this->captureFilterOutput('admin_footer', null);
        $this->assertContains('msc-form-age', $dialog);
        $this->assertContains('msc-form-posts', $dialog);
        $this->assertContains('msc-form-listing', $dialog);
    }

    protected function captureFilterOutput($tag, $args)
    {
        ob_start();
        apply_filters($tag, $args);
        $output = ob_get_contents();
        ob_clean();

        return $output;
    }

    protected function loginAsAdmin()
    {
        $this->adminId = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($this->adminId);
        $this->assertTrue(is_super_admin($this->adminId));

        return $this->adminId;
    }
}
