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

defined('MOO_MINISHORTCODE') or die;

use \WP_Query as WP_Query;

/**
 * A shortcode to display posts
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Posts extends Listing
{
    const PARAM_FILTER_TIME = 'time';
    const PARAM_FILTER_PERMALINK = 'permalink';
    const PARAM_FILTER_TITLE = 'title';
    const PARAM_FILTER_EXCERPT = 'excerpt';
    const PARAM_FILTER_COMMENTSLINK = 'commentsLink';
    const PARAM_FILTER_TAGLIST = 'tagList';

    /**
     * An instance of WP_Query
     *
     * @var WP_Query
     */
    protected $posts;

    public function __construct()
    {
        $this->defaultOptions['format'] = "<div class='msc-post'>"
                . "<div class='date'><span>{\$1}</span><span>{\$2}</span></div>"
                . "<h4><a href='{\$3}'>{\$4}</a></h4>"
                . "{\$5}<a class='more' href='{\$3}'>read full entry &raquo;</a>"
                . "<ul class='meta'>"
                . "<li>{\$6}</li>"
                . "<li>{\$7}</li>"
                . "</ul>"
                . "</div>";

        $this->filters = array(
            self::PARAM_FILTER_TIME . ':j',
            self::PARAM_FILTER_TIME . ':M',
            self::PARAM_FILTER_PERMALINK,
            self::PARAM_FILTER_TITLE,
            self::PARAM_FILTER_EXCERPT,
            self::PARAM_FILTER_COMMENTSLINK . ':No comments:1 comment:% comments',
            self::PARAM_FILTER_TAGLIST,
        );
    }

    /**
     * Render an item in the list
     *
     * @param int $key
     * @param array $values
     * @return string
     */
    protected function renderItem($key, $values)
    {
        $item = $this->getFormat();

        // Get the next post
        $this->posts->the_post();

        // Replace item place holders using the current defined filters
        foreach ($this->filters as $index => $filter) {
            $item = str_replace('{$' . ($index + 1) . '}', $this->filterValue($key, $index), $item);
        }

        // Add class attribute 'last' to the last item
        if (($key + 1) >= $this->count) {
            $item = preg_replace('/(class(\s*)=(\s*)["|\'])/', '$1last ', $item);
        }

        // Reset the global $the_post
        wp_reset_postdata();

        return $item;
    }

    /**
     * Retrieve post url
     *
     * @return string
     */
    protected function filterPermalink()
    {
        return esc_url(apply_filters('the_permalink', get_permalink()));
    }

    /**
     * Retrieve post excerpt
     *
     * @return string
     */
    protected function filterExcerpt()
    {
        return apply_filters('the_excerpt', get_the_excerpt());
    }

    /**
     * Retrieve post title
     *
     * @return string
     */
    protected function filterTitle()
    {
        return get_the_title();
    }

    /**
     * Retrieve post time
     *
     * @param int $index
     * @param string $format
     * @return string
     */
    protected function filterTime($index, $format)
    {
        return apply_filters('the_time', get_the_time($format), $format);
    }

    /**
     * Retrieve post tag list
     *
     * @param int $index
     * @param string $seperator
     * @return string
     */
    protected function filterTagList($index, $seperator = ', ')
    {
        return get_the_tag_list('', $seperator, '');
    }

    /**
     * Retrieve post comments link
     *
     * @param int $index
     * @param string $zero
     * @param string $one
     * @param string $more
     * @param string $none
     * @return string
     */
    protected function filterCommentsLink($index, $zero = false, $one = false, $more = false, $none = false)
    {
        ob_start();
        comments_popup_link($zero, $one, $more, '', $none);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Fetch, sort, & count the ordered items
     *
     * @param array $atts
     */
    protected function fetchData($atts)
    {
        // Clear the default filters if a new format is set
        if (isset($atts['format'])) {
            $this->filters = array();
        }

        // Fetch options
        parent::fetchData($atts);

        // Fetch data from instagram
        $this->count = 0;
        $this->items = array();

        // WordPress query parameters
        $query = 'post_status=publish&showposts=' . $this->options['max'];

        if ($this->options['sort'] == 'first') {
            $query .= '&orderby=id&order=ASC';
        } elseif ($this->options['sort'] == 'last') {
            $query .= '&orderby=id&order=DESC';
        } else {
            $query .= '&orderby=' . $this->options['sort'];
        }

        // Retrieve & count posts.
        // The property 'items' contains an array of indexes matching the number of posts found.
        // This is so that the 'foreach' loop in the self::shortcode can iterate while fetching posts data with WP_Query::the_post
        $this->posts = new WP_Query($query);
        if ($this->posts->have_posts()) {
            $this->count = $this->posts->post_count;
            $this->items = range(0, $this->count);
        }

        return $this;
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
        $elements['sort']['options'][] = 'first';
        $elements['sort']['options'][] = 'last';
        unset($elements['delimiter']);
        unset($elements['item']);
        $elements['header2']['title'] = 'Filters';

        // New element
        $elements['filter'] = array(
            'type'    => self::ELEMENT_FILTER,
            'filters' => array(
                self::PARAM_FILTER_TITLE        => array(
                    'label' => 'Title',
                ),
                self::PARAM_FILTER_TIME         => array(
                    'label'  => 'Time',
                    'params' => array('Date format'),
                ),
                self::PARAM_FILTER_EXCERPT      => array(
                    'label' => 'Excerpt',
                ),
                self::PARAM_FILTER_PERMALINK    => array(
                    'label' => 'Permalink',
                ),
                self::PARAM_FILTER_COMMENTSLINK => array(
                    'label'    => 'Comment link',
                    'params'   => array('No comments', 'One comment', 'Comments', 'Comment disabled'),
                    'defaults' => array('No comments', '1 comment', '% comments', 'Comments Off'),
                ),
                self::PARAM_FILTER_TAGLIST      => array(
                    'label'    => 'Tag list',
                    'params'   => array('Seperator'),
                    'defaults' => array(', '),
                ),
            ),
        );

        return $elements;
    }

}
