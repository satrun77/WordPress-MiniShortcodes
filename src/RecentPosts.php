<?php

defined('MOO_MINSHORTCODE') or die;

/**
 * A shortcode to display recent posts
 * Usage:
 *  [moo_recentposts number=5]
 * 
 * @copyright  2014 Mohamed Alsharaf
 * @author     Mohamed Alsharaf (mohamed.alsharaf@gmail.com)
 * @version    1.0.0
 * @license    The MIT License (MIT)
 */
class Moo_MiniShortCodes_RecentPosts implements Moo_ShortcodeInterface
{

    /**
     * Shortcode callback method
     * 
     * @param array $atts
     * @param string $content
     * @param string $tag
     * @return string
     * @throws Exception
     */
    public function shortcode($atts = array(), $content = null, $tag = '')
    {
        $query = 'offset=1&showposts=' . $atts['number'];
        if (!empty($atts['random'])) {
            $query .= '&orderby=rand';
        }

        query_posts($query);

        $output = '<div id="moo-recentposts">';
        if (have_posts()) : while (have_posts()) : the_post();
//                $output .= '<div class="date">' . $this->getTime('j') . ' <span>' . $this->getTime('M') . '</span></div>';
                $output .= '<div class="content">';
                $output .= '<h4><a href="' . $this->getPermalink() . '">' . get_the_title() . '</a></h4>';
                $output .= $this->getExcerpt();
                $output .= '</div>';
                $output .= '<div class="metadata">';
                $output .= '<ul>';
//                $output .= '<li class="date">' . $this->getTime('j') . ' <span>' . $this->getTime('M') . '</li>';
                $output .= '<li class="comments">' . $this->getCommentsLink() . '</li>';
                $output .= '<li class="tags">' . get_the_tag_list('', ', ', '') . '</li>';
                $output .= '</ul>';
                $output .= '</div>';
            endwhile;
        endif;

        $output .= '</div>';

        return $output;
    }

    protected function getPermalink()
    {
        return esc_url(apply_filters('the_permalink', get_permalink()));
    }

    protected function getTime($format)
    {
        return apply_filters('the_time', get_the_time($format), $format);
    }

    protected function getExcerpt()
    {
        $more = '<a class="more" href="' . $this->getPermalink() . '">read full entry &raquo;</a>';

        return apply_filters('the_excerpt', get_the_excerpt() . $more);
    }

    protected function getCommentsLink()
    {
        ob_start();
        comments_popup_link('No comments', '1 comment', '% comments');
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

}
