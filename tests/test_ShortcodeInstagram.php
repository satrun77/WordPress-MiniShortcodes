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
 * MiniShortcodes_Test_ShortcodeInstagram to test the class for the shortcode moo_instagram.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MiniShortcodes_Test_ShortcodeInstagram extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Load plugin autoload
        include_once __DIR__.'/../src/ShortcodePlugin.php';

        new \Moo\MiniShortcode\ShortcodePlugin();
    }

    public function tearDown()
    {
        parent::tearDown();

        $dir = new \GlobIterator(__DIR__.'/../src/Shortcode/*.txt');
        foreach ($dir as $file) {
            unlink($file->getPathname());
        }
    }

    public function testCreatingShortcode()
    {
        $http = $this->getMock('WP_Http_Curl');
        $http->expects($this->any())
                ->method('request')
                ->will($this->returnValue(array(
                            'body' => '{"data":[{"created_time":"1415081452","link":"http:\/\/instagram.com\/p\/u956VuxNvd\/",'
                            .'"images":{"thumbnail":{"url":"http:\/\/scontent-b.cdninstagram.com\/hphotos-xfp1\/'
                            .'t51.2885-15\/10748407_745324072215924_361709996_s.jpg","width":150,"height":150}},'
                            .'"caption":{"text":"A fine craft I made from an old bed! I think it\'s valued @ $1000 :)'
                            .'"}},{"link":"http:\/\/instagram.com\/p\/ujvDZvRNus\/","images":{"thumbnail":{"url":'
                            .'"http:\/\/scontent-b.cdninstagram.com\/hphotos-xfp1\/t51.2885-15\/1515369_295537817312067'
                            .'_1582461774_s.jpg","width":150,"height":150}},"caption":null}]}',
        )));

        $shortcode = new \Moo\MiniShortcode\Shortcode\Instagram();
        $shortcode->setHttpCurl($http);
        $out = $shortcode->shortcode(array(
            'client_id' => '123',
            'user_id'   => 'abcd',
            'debug'     => true,
            'format'    => '<li><img src="{$2}"/></li>',
            'tag'       => 'ul',
        ));

        $xml = new SimpleXMLElement($out);

        $this->assertEquals(2, $xml->count());
        $this->assertTrue(isset($xml->li));
        $this->assertContains('hphotos-xfp1/t51.2885-15/10748407_745324072215924_361709996_s.jpg', $out);
        $this->assertContains('hphotos-xfp1/t51.2885-15/1515369_295537817312067_1582461774_s.jpg', $out);
    }

    public function testFormElementsShortcode()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Instagram();
        $elements = $shortcode->getFormElements();
        $this->assertCount(12, $elements);
    }

    public function testLazyloadHttpCurl()
    {
        $shortcode = new \Moo\MiniShortcode\Shortcode\Instagram();
        $this->assertInstanceOf('WP_Http_Curl', $shortcode->getHttpCurl());
    }
}
