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
 * MiniShortcodes_Test_ShortcodePosts to test the class for the shortcode moo_posts.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MiniShortcodes_Tests_ShortcodePosts extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Load plugin autoload
        include_once __DIR__.'/../src/ShortcodePlugin.php';

        new \Moo\MiniShortcode\ShortcodePlugin();
    }

    public function testCreatingDefaultShortcode()
    {
        $count = 5;
        $xml = $this->createShortcode($count);

        $this->assertEquals($count, $xml->count());

        foreach ($xml->children() as $post) {
            $this->assertTrue(isset($post->h4->a));
            $this->assertNotEmpty((string)$post->h4->a);
        }
    }

    public function testCreatingSortedFirstShortcode()
    {
        $xml = $this->createShortcode(5, array('sort' => 'first'));

        $left = null;
        foreach ($xml->children() as $key => $post) {
            $this->assertTrue(isset($post->h4->a));
            if ($left != null) {
                $this->assertGreaterThan($left, (string)$post->h4->a);
            }
            $left = (string)$post->h4->a;
        }
    }

    /**
     * @param integer $number
     */
    protected function createShortcode($number, $args = array())
    {
        $posts = $this->factory->post->create_many($number, array('post_status' => 'publish'));
        $count = count($posts);

        $shortcode = new \Moo\MiniShortcode\Shortcode\Posts();
        $out = $shortcode->shortcode($args);
        $xml = new SimpleXMLElement(html_entity_decode($out));

        $this->assertEquals($count, $xml->count());
        $this->assertTrue(isset($xml->div));

        return $xml;
    }

    public function testsFormElementsShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Posts();
        $elements = $shortcode->getFormElements();
        $this->assertCount(10, $elements);
    }
}
