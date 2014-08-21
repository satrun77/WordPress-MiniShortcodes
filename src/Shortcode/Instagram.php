<?php

/*
 * This file is part of the \Moo\MiniShortcode package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moo\MiniShortcode\Shortcode;

/**
 * A shortcode to display a list of photos from an instagram account
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Instagram extends Listing
{
    /**
     * Cache file life time in seconds
     *
     * @var int
     */
    private $cacheTime = '86400';

    public function __construct()
    {
        // Defines new options
        $this->defaultOptions['client_id'] = '';
        $this->defaultOptions['user_id'] = '';
    }

    /**
     * Returns API url to retrieve recent photos
     *
     * @param  int    $userId
     * @param  string $clientId
     * @return string
     */
    protected function getUrl($userId, $clientId)
    {
        return sprintf("https://api.instagram.com/v1/users/%s/media/recent/?client_id=%s", $userId, $clientId);
    }

    /**
     * Returns cache file path
     *
     * @return string
     */
    protected function getCacheFile()
    {
        return __DIR__ . '/' . $this->options['client_id'] . 'instagram.txt';
    }

    /**
     * Whether or not the API response is cached
     *
     * @return boolean
     */
    protected function isCached()
    {
        // Create new cache if file update timestamp is older than one day, or does not exists
        $timeDiff = time() - filemtime($this->getCacheFile());
        if (!file_exists($this->getCacheFile()) || $timeDiff > $this->cacheTime) {
            return false;
        }

        return true;
    }

    /**
     * Save API response to cache file
     *
     * @param  string  $content
     * @return boolean
     */
    protected function writeToCache($content)
    {
        if (file_put_contents($this->getCacheFile(), $content) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Fetch data from cache file
     *
     * @return \Moo\MiniShortcode\Shortcode\Instagram
     */
    protected function fetchItems()
    {
        $content = file_get_contents($this->getCacheFile());
        $response = json_decode($content);

        if (!empty($response->data)) {
            foreach ($response->data as $row) {
                $caption = !empty($row->caption->text) ? $row->caption->text : '';
                $this->items[$this->count] = array(
                    $row->link,
                    $row->images->thumbnail->url,
                    $caption
                );
                $this->count++;
            }
        }

        return $this;
    }

    /**
     * Fetch data from instagram or cache file.
     *
     * @param array $atts
     */
    protected function fetchData($atts)
    {
        // Fetch options
        parent::fetchData($atts);

        // Fetch data from instagram
        $this->count = 0;
        $this->items = array();

        if (!$this->isCached()) {
            $request = new WP_Http_Curl();
            $response = $request->request($this->getUrl($this->options['user_id'], $this->options['client_id']), array(
                'timeout' => 300,
                'headers' => array(
                    'User-Agent' => filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'),
                )
            ));

            if (isset($response['body'])) {
                $this->writeToCache($response['body']);
            }
        }

        return $this->fetchItems();
    }

    /**
     * Form elements for TinyMCE plugin
     *
     * @return array
     */
    public function getFormElements()
    {
        $elements = parent::getFormElements();

        // Modify parent elments.
        $elements['header2']['title'] = 'Account details';
        unset($elements['item']);

        // New elements
        $elements['client_id'] = array(
            'type'     => self::ELEMENT_TEXT,
            'label'    => 'client_id',
            'value'    => $this->defaultOptions['client_id'],
            'datatype' => self::PARAM_FILTER_STRING,
        );
        $elements['user_id'] = array(
            'type'     => self::ELEMENT_TEXT,
            'label'    => 'user_id',
            'value'    => $this->defaultOptions['user_id'],
            'datatype' => self::PARAM_FILTER_STRING,
        );

        return $elements;
    }

}
