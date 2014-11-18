<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {
    include_once __DIR__.'/../src/ShortcodePlugin.php';

    /**
     * MiniShortcodes_Test_InitPlugin to test the plugin main class for adding and removing shortcodes.
     *
     * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
     */
    class MiniShortcodes_Test_InitPlugin extends WP_UnitTestCase
    {
        /**
         *
         * @expectedException \Exception
         */
        public function testAddInvalidShortcode()
        {
            $mooShortcode = new \Moo\MiniShortcode\ShortcodePlugin();
            $this->assertFalse($mooShortcode->getShortcode('noValid1'));

            try {
                $mooShortcode->addShortcode('FakeShortcode');
                $this->fail('An expected exception has not been raised.');
            } catch (\Exception $e) {
            }

            $mooShortcode->addShortcode('noValid');
        }

        public function testRemovingShortcode()
        {
            $mooShortcode = new \Moo\MiniShortcode\ShortcodePlugin();
            $mooShortcode->addShortcode('age');
            $out = do_shortcode('[moo_age date="1982-01-13"]');
            $this->assertNotContains('[moo_age ', $out);
            $mooShortcode->removeShortcode('age');
            $out2 = do_shortcode('[moo_age date="1982-01-13"]');
            $this->assertContains('[moo_age ', $out2);
            $mooShortcode->addShortcode('age');
        }

        public function testCountingShortcode()
        {
            $mooShortcode = new \Moo\MiniShortcode\ShortcodePlugin();
            $mooShortcode->addShortcode('age');
            $mooShortcode->addShortcode('posts');
            $this->assertEquals(2, count($mooShortcode->getShortcodes()));
            $mooShortcode->addShortcode('listing');
            $this->assertEquals(3, count($mooShortcode->getShortcodes()));
        }

        public function testDebugParam()
        {
            $mooShortcode = new \Moo\MiniShortcode\ShortcodePlugin();
            $mooShortcode->addShortcode('age');
            $out = do_shortcode('[moo_age date="3000-01-13" debug="1"]');
            $this->assertContains('You are an unborn child!', $out);
            $out2 = do_shortcode('[moo_age date="3000-01-13"]');
            $this->assertEmpty($out2);
        }
    }

}

namespace Moo\MiniShortcode\Shortcode {

    /**
     * Fake shortcode used in unit testing
     *
     * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
     */
    class FakeShortcode
    {
    }

}
