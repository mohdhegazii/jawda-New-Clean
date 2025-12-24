<?php

/* -----------------------------------------------------------------------------
-- pagination Function
----------------------------------------------------------------------------- */

if (!function_exists('theme_pagination')) {
  function theme_pagination() {
    if (is_singular()) {
        return;
    }

    global $wp_query;
    if ($wp_query->max_num_pages <= 1) {
        return;
    }

    $paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
    $max   = intval($wp_query->max_num_pages);

    $links = [];
    $show_ellipsis = true;

    // Add nearby links
    for ($i = 1; $i <= $max; $i++) {
        if ($i == 1 || $i == $max || ($i >= $paged - 1 && $i <= $paged + 1)) {
            $links[] = $i;
        }
    }

    echo '<div class="navigation"><ul>';

    if (get_previous_posts_link()) {
        printf('<li class="prev">%s</li>', get_previous_posts_link());
    }

    // Loop through pages
    $current_link = 0;
    foreach ($links as $link) {
        if ($current_link + 1 < $link) {
            echo '<li>…</li>';
        }

        $class = ($paged == $link) ? ' class="active"' : '';
        printf('<li%s><a href="%s">%s</a></li>', $class, esc_url(get_pagenum_link($link)), $link);
        $current_link = $link;
    }

    if (get_next_posts_link()) {
        printf('<li class="next">%s</li>', get_next_posts_link());
    }

    echo '</ul></div>';
  }
}
