<?php
/**
 * Catalog SEO resolution service.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Catalog_SEO_Service {
    protected $table;
    protected $cache = [];

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'jawda_catalog_seo_overrides';
    }

    public function get_override_by_key($catalog_key) {
        global $wpdb;
        if (!$catalog_key) {
            return null;
        }
        if (isset($this->cache[$catalog_key])) {
            return $this->cache[$catalog_key];
        }
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE catalog_key = %s AND is_active = 1", $catalog_key));
        $this->cache[$catalog_key] = $row;
        return $row;
    }

    public function get_override(Jawda_Catalog_Context $context) {
        return $this->get_override_by_key($context->get_catalog_key());
    }

    public function has_override(Jawda_Catalog_Context $context) {
        return (bool) $this->get_override($context);
    }

    public function get_meta_title(Jawda_Catalog_Context $context, $use_fallback = false) {
        $override = $this->get_override($context);
        $is_ar = $this->is_ar();
        if ($override) {
            $value = $is_ar ? $override->meta_title_ar : $override->meta_title_en;
            if (!empty($value)) {
                return $value;
            }
        }
        return $use_fallback ? $this->build_fallback_title($context, $is_ar) : '';
    }

    public function get_meta_description(Jawda_Catalog_Context $context, $use_fallback = false) {
        $override = $this->get_override($context);
        $is_ar = $this->is_ar();
        if ($override) {
            $value = $is_ar ? $override->meta_desc_ar : $override->meta_desc_en;
            if (!empty($value)) {
                return $value;
            }
        }
        return $use_fallback ? $this->build_fallback_description($context, $is_ar) : '';
    }

    public function get_content(Jawda_Catalog_Context $context, $use_fallback = false) {
        $override = $this->get_override($context);
        $is_ar = $this->is_ar();
        if ($override) {
            $field = $is_ar ? $override->content_ar : $override->content_en;
            if (!empty($field)) {
                return $field;
            }
            $legacy = $is_ar ? $override->intro_html_ar : $override->intro_html_en;
            if (!empty($legacy)) {
                return $legacy;
            }
        }

        if ($use_fallback) {
            return wpautop($this->build_fallback_description($context, $is_ar));
        }

        return '';
    }

    public function get_intro_html(Jawda_Catalog_Context $context) {
        return $this->get_content($context, false);
    }

    public function get_featured_image_id(Jawda_Catalog_Context $context) {
        $override = $this->get_override($context);
        if ($override && $override->featured_image_id) {
            return (int) $override->featured_image_id;
        }
        return 0;
    }

    public function get_featured_image_url(Jawda_Catalog_Context $context) {
        $id = $this->get_featured_image_id($context);
        if ($id) {
            $url = wp_get_attachment_url($id);
            if ($url) {
                return $url;
            }
        }
        return '';
    }

    public function get_meta_robots(Jawda_Catalog_Context $context) {
        $override = $this->get_override_by_key($context->get_catalog_key());
        if ($override && !empty($override->meta_robots)) {
            return $override->meta_robots;
        }
        return '';
    }

    protected function is_ar() {
        return function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
    }

    public function build_fallback_title(Jawda_Catalog_Context $context, $is_ar) {
        $location = $context->get_location_object();
        $site_name = get_bloginfo('name');
        $name = $location ? ($is_ar ? ($location->name_ar ?? '') : ($location->name_en ?? '')) : '';

        if ($context->get_location_level() === 'country') {
            return $is_ar
                ? sprintf('مشروعات جديدة في %s | %s', $name ?: 'مصر', $site_name)
                : sprintf('New Projects in %s | %s', $name ?: 'Egypt', $site_name);
        }

        if ($name) {
            return $is_ar
                ? sprintf('مشروعات جديدة في %s | %s', $name, $site_name)
                : sprintf('New Projects in %s | %s', $name, $site_name);
        }

        return $is_ar ? sprintf('مشروعات جديدة | %s', $site_name) : sprintf('New Projects | %s', $site_name);
    }

    public function build_fallback_description(Jawda_Catalog_Context $context, $is_ar) {
        $location = $context->get_location_object();
        $name = $location ? ($is_ar ? ($location->name_ar ?? '') : ($location->name_en ?? '')) : '';
        $site_name = get_bloginfo('name');

        if ($is_ar) {
            if ($name) {
                return sprintf('اكتشف أفضل المشروعات العقارية الجديدة في %s مع %s. تصفح أحدث الأسعار والمخططات.', $name, $site_name);
            }
            return sprintf('استكشف المشروعات الجديدة مع %s. تصفح أحدث الأسعار والمخططات.', $site_name);
        }

        if ($name) {
            return sprintf('Discover the best new real estate projects in %s with %s. Browse latest prices and plans.', $name, $site_name);
        }
        return sprintf('Explore the latest new projects with %s. Browse updated prices and plans.', $site_name);
    }
}

function jawda_catalog_seo_service() {
    static $instance = null;
    if (!$instance) {
        $instance = new Jawda_Catalog_SEO_Service();
    }
    return $instance;
}

/**
 * Helper to expose current catalog SEO payload to templates.
 *
 * @param bool $with_fallback Whether to return template-based defaults when no override exists.
 *
 * @return array|null
 */
function jawda_get_current_catalog_seo($with_fallback = true) {
    if (!function_exists('jawda_catalogs_current_context')) {
        return null;
    }

    $ctx = jawda_catalogs_current_context();
    if (!$ctx) {
        return null;
    }

    $service = jawda_catalog_seo_service();

    return [
        'context'            => $ctx,
        'is_override'        => $service->has_override($ctx),
        'title'              => $service->get_meta_title($ctx, $with_fallback),
        'description'        => $service->get_meta_description($ctx, $with_fallback),
        'content'            => $service->get_content($ctx, $with_fallback),
        'featured_image_id'  => $service->get_featured_image_id($ctx),
        'featured_image_url' => $service->get_featured_image_url($ctx),
        'meta_robots'        => $service->get_meta_robots($ctx),
    ];
}
