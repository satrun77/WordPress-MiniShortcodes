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
 * MiniShortcodes_Test_ShortcodeListing to test the class for the shortcode moo_listing.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MiniShortcodes_Test_ShortcodeListing extends WP_UnitTestCase
{

    public function setUp()
    {
        parent::setUp();

        // Load plugin autoload
        include_once __DIR__ . '/../src/ShortcodePlugin.php';

        new \Moo\MiniShortcode\ShortcodePlugin();
    }

    public function testWithoutParamsShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Listing();
        $out = $shortcode->shortcode();
        $this->assertEmpty($out);
    }

    public function testCreatingShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Listing();
        $out = $shortcode->shortcode(array(
            'class'  => 'my-list-test',
            'tag'    => 'ul',
            'item1'  => 'value1|value2',
            'item2'  => 'value1|value2',
            'format' => '<li>{$1} - {$2}</li>',
            'before' => '',
            'after'  => '',
        ));

        $xml = new SimpleXMLElement($out);

        $this->assertEquals(2, $xml->count());
        $this->assertTrue(isset($xml->li));
        $this->assertEquals('value1 - value2', $xml->li[0]);
        $this->assertContains('my-list-test', (string) $xml->attributes()->class);
    }

    public function testFormElementsShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Listing();
        $elements = $shortcode->getFormElements();
        $this->assertCount(11, $elements);
    }

}
