<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!defined('CF_DEP_TAX_GOV'))  define('CF_DEP_TAX_GOV',  'property_state'); // سلَج تاكسونومي المحافظات
if (!defined('CF_DEP_TAX_CITY')) define('CF_DEP_TAX_CITY', 'property_city');        // سلَج تاكسونومي المدن

// مفاتيح الميتا الفعلية عندك:
if (!defined('CF_DEP_CITY_META_GOV_KEY')) define('CF_DEP_CITY_META_GOV_KEY', 'jawda_city_state'); // meta في المدينة بتخزن gov term_id
if (!defined('CF_DEP_PROJECT_TAX_CITY')) define('CF_DEP_PROJECT_TAX_CITY', 'property_city');     // taxonomy لربط المشروع بالمدينة

if (!defined('CF_DEP_DEBUG')) define('CF_DEP_DEBUG', true);
