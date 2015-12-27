<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
include_once __DIR__.'/../src/ShortcodePlugin.php';

/**
 * MiniShortcodes_Test_Shortcodes to test the plugin shortcodes.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MiniShortcodes_Test_Shortcodes extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Make sure the following shortcodes are registered
        $mooShortcode = new \Moo\MiniShortcode\ShortcodePlugin();
        $mooShortcode->addShortcode('age');
        $mooShortcode->addShortcode('listing');
        $mooShortcode->addShortcode('posts');
    }

    public function testAgeShortcode()
    {
        $out1 = do_shortcode('[moo_age date="1982-01-13"]');
        $this->assertRegExp('/^\d+ years old$/', $out1);

        $out2 = do_shortcode('[moo_age date="1982-01-13" append=" years"]');
        $this->assertRegExp('/\d+ years$/', $out2);
    }

    public function testListingShortcode()
    {
        $out = do_shortcode('[moo_listing class="my-list-test" tag="ul" item1="value1|value2" item2="value1|value2" format="<li>{$1} - {$2}</li>"]');
        $xml = new SimpleXMLElement($out);

        $this->assertEquals(2, $xml->count());
        $this->assertTrue(isset($xml->li));
        $this->assertEquals('value1 - value2', $xml->li[0]);
        $this->assertContains('my-list-test', (string) $xml->attributes()->class);
    }

    public function testPostsShortcode()
    {
        $posts = $this->factory->post->create_many(5, array('post_status' => 'publish'));
        $count = count($posts);

        $out = do_shortcode('[moo_posts]');
        $xml = new SimpleXMLElement(html_entity_decode($out));

        $this->assertEquals($count, $xml->count());
        $this->assertTrue(isset($xml->div));

        foreach ($xml->children() as $post) {
            $this->assertTrue(isset($post->h4->a));
            $this->assertNotEmpty((string)$post->h4->a);
        }
    }
}
