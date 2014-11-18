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
 * MiniShortcodes_Test_ShortcodeAge to test the class for the shortcode moo_age.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MiniShortcodes_Test_ShortcodeAge extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Load plugin autoload
        include_once __DIR__.'/../src/ShortcodePlugin.php';

        new \Moo\MiniShortcode\ShortcodePlugin();
    }

    /**
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Date parameter is missing
     */
    public function testWithoutDateShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Age();
        $shortcode->shortcode();
    }

    /**
     *
     * @expectedException \Exception
     * @expectedExceptionMessage unborn
     */
    public function testInvalidDateShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Age();
        $shortcode->shortcode(array('date' => '2982-1-13'));
    }

    public function testValidDateShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Age();
        $out = $shortcode->shortcode(array('date' => '1982-1-13'));
        $this->assertRegExp('/^\d+ years old$/', $out);
    }

    public function testFormElementsShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Age();
        $elements = $shortcode->getFormElements();
        $this->assertCount(2, $elements);
    }
}
