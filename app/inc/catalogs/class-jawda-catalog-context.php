<?php
/**
 * Catalog Context builder for dynamic catalogs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Catalog_Context {
    protected $type = 'projects';
    protected $location_level = '';
    protected $location_object = null;
    protected $governorate = null;
    protected $city = null;
    protected $district = null;
    protected $is_custom = false;

    public static function from_request() {
        global $wp, $jawda_current_location_context;

        if (!isset($wp)) {
            return null;
        }

        // Ensure we are on the dynamic projects catalog.
        $is_projects = isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'projects';
        $has_location = isset($wp->query_vars['jawda_new_projects_root']) || isset($wp->query_vars['jawda_loc_gov']);

        if (!$is_projects || (!$has_location && empty($jawda_current_location_context))) {
            return null;
        }

        $context_data = is_array($jawda_current_location_context ?? null) ? $jawda_current_location_context : [];

        $instance = new self();
        $instance->type = 'projects';

        $level = $context_data['level'] ?? ($wp->query_vars['jawda_loc_level'] ?? '');
        if (!$level && isset($wp->query_vars['jawda_new_projects_root'])) {
            $level = 'country';
        }

        $instance->location_level = $level;

        if ($level === 'country') {
            $instance->location_object = (object) [
                'id'      => 0,
                'slug'    => 'egypt',
                'slug_ar' => 'مصر',
                'name_en' => 'Egypt',
                 'slug_ar'  => 'مصر',
            ];
        }

        if (isset($context_data['governorate'])) {
            $instance->governorate = $context_data['governorate'];
        }
        if (isset($context_data['city'])) {
            $instance->city = $context_data['city'];
        }
        if (isset($context_data['district'])) {
            $instance->district = $context_data['district'];
        }

        if ($level === 'governorate') {
            $instance->location_object = $instance->governorate;
        } elseif ($level === 'city') {
            $instance->location_object = $instance->city;
        } elseif ($level === 'district') {
            $instance->location_object = $instance->district;
        }

        if (!$instance->location_object) {
            return null;
        }

        return $instance;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_location_level() {
        return $this->location_level;
    }

    public function get_location_object() {
        return $this->location_object;
    }

    public function get_governorate() {
        return $this->governorate;
    }

    public function get_city() {
        return $this->city;
    }

    public function get_district() {
        return $this->district;
    }

    public function get_catalog_key() {
        $parts = [];
        $parts[] = $this->type;

        if ($this->location_level === 'country') {
            $parts[] = 'country=egypt';
        }

        if ($this->governorate) {
            $parts[] = 'gov=' . $this->get_stable_slug($this->governorate);
        }

        if ($this->city) {
            $parts[] = 'city=' . $this->get_stable_slug($this->city);
        }

        if ($this->district) {
            $parts[] = 'district=' . $this->get_stable_slug($this->district);
        }

        return implode('|', $parts);
    }

    protected function get_stable_slug($obj) {
        if (is_array($obj)) {
            $obj = (object) $obj;
        }
        if (!is_object($obj)) {
            return '';
        }
        if (!empty($obj->slug)) {
            return sanitize_title($obj->slug);
        }
        if (!empty($obj->slug_ar)) {
            return sanitize_title($obj->slug_ar);
        }
        if (!empty($obj->name_en)) {
            return sanitize_title($obj->name_en);
        }
        if (!empty($obj->name_ar)) {
            return sanitize_title($obj->name_ar);
        }
        return '';
    }
}
