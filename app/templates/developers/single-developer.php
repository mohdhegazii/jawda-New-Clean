<?php
if (!defined('ABSPATH')) {
    exit;
}

if (empty($GLOBALS['jawda_is_rendering_developer_template'])) {
    return;
}

$developer = $GLOBALS['jawda_current_developer'] ?? null;
if (!$developer) {
    get_template_part('404');
    return;
}

$is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
$logo_id = $developer['logo_id'] ?? 0;
$description = $is_ar ? ($developer['description_ar'] ?? '') : ($developer['description_en'] ?? '');
$name = $is_ar ? $developer[ 'slug_ar' ] : $developer['name_en'];

get_header();
?>
<main class="developer-page">
    <div class="container">
        <div class="developer-header">
            <?php if ($logo_id) : ?>
                <div class="developer-logo"><?php echo wp_get_attachment_image($logo_id, 'medium'); ?></div>
            <?php endif; ?>
            <h1 class="developer-name"><?php echo esc_html($name); ?></h1>
        </div>
        <div class="developer-description">
            <?php echo wpautop(wp_kses_post($description)); ?>
        </div>
    </div>
</main>
<?php
get_footer();
