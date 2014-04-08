<?php

defined('MOO_MINSHORTCODE') or die;

require_once __DIR__ . '/List.php';

/**
 * A shortcode to display a list of photos from an instagram account
 * Usage:
 *  [moo_instagram client_id="" client_token="" user_id=""]
 *
 * @copyright  2014 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    1.0.0
 * @license    The MIT License (MIT)
 */
class Moo_MiniShortcodes_Instagram extends Moo_MiniShortcodes_List
{
    /**
     * Cache file life time in seconds
     *
     * @var int
     */
    private $cacheTime = '86400';

    protected function init()
    {
        // Defines new options
        $this->defaultOptions['client_id'] = '';
        $this->defaultOptions['user_id'] = '';
    }

    /**
     * Returns API url to retrieve recent photos
     *
     * @param int $userId
     * @param string $clientId
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
     * @param string $content
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
     * @return \Moo_MiniShortcodes_Instagram
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
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                )
            ));

            if (isset($response['body'])) {
                $this->writeToCache($response['body']);
            }
        }

        return $this->fetchItems();
    }

}
