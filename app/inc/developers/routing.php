<?php
/**
 * Routing and SEO integration for Developers Engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Disable legacy developers CPT routing if present.
add_action('init', function() {
    global $wp_post_types;
    if (isset($wp_post_types['developers'])) {
        $wp_post_types['developers']->publicly_queryable = false;
        $wp_post_types['developers']->rewrite = false;
        $wp_post_types['developers']->query_var = false;
    }
}, 20);

add_filter('query_vars', function($vars) {
    $vars[] = 'jawda_dev_slug';
    $vars[] = 'jawda_is_developer_page';
    return $vars;
});

add_action('parse_request', function($wp) {
    if (is_admin()) {
        return;
    }

    $slug = $wp->query_vars['jawda_dev_slug'] ?? null;
    $service = jawda_developers_service();
    $developer = null;

    if ($slug) {
        $developer = $service->get_developer_by_slug_ar($slug);
        if (!$developer) {
            $developer = $service->get_developer_by_slug_en($slug);
        }
    }

    if ($developer) {
        $wp->query_vars['jawda_is_developer_page'] = true;
        $GLOBALS['jawda_current_developer'] = $developer;
    } else {
        unset($wp->query_vars['jawda_is_developer_page']);
        return;
    }
});

add_filter('template_include', function($template) {
    if (!get_query_var('jawda_is_developer_page')) {
        return $template;
    }

    $custom_template = get_theme_file_path('app/templates/developers/single-developer.php');
    if (file_exists($custom_template)) {
        $GLOBALS['jawda_is_rendering_developer_template'] = true;
        return $custom_template;
    }

    return $template;
});

// SEO integrations (Yoast/RankMath fallbacks)
add_filter('wpseo_title', function($title) {
    return jawda_developer_seo_value($title, 'title');
});
add_filter('wpseo_metadesc', function($desc) {
    return jawda_developer_seo_value($desc, 'description');
});
add_filter('rank_math/frontend/title', function($title) {
    return jawda_developer_seo_value($title, 'title');
});
add_filter('rank_math/frontend/description', function($desc) {
    return jawda_developer_seo_value($desc, 'description');
});

function jawda_developer_seo_value($default, $type = 'title') {
    if (!get_query_var('jawda_is_developer_page')) {
        return $default;
    }

    $developer = $GLOBALS['jawda_current_developer'] ?? null;
    if (!$developer) {
        return $default;
    }

    $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

    if ('title' === $type) {
        $seo_title = $is_ar ? ($developer['seo_title_ar'] ?? '') : ($developer['seo_title_en'] ?? '');
        if ($seo_title) {
            return $seo_title;
        }
        $name = $is_ar ? $developer[ 'slug_ar' ] : $developer['name_en'];
        $suffix = $is_ar ? __('المطورين', 'jawda') : __('Developers', 'jawda');
        return sprintf('%s | %s | %s', $name, $suffix, get_bloginfo('name'));
    }

    $seo_desc = $is_ar ? ($developer['seo_desc_ar'] ?? '') : ($developer['seo_desc_en'] ?? '');
    if ($seo_desc) {
        return $seo_desc;
    }

    $desc = $is_ar ? ($developer['description_ar'] ?? '') : ($developer['description_en'] ?? '');
    if ($desc) {
        return wp_trim_words(wp_strip_all_tags($desc), 30);
    }

    return $default;
}
