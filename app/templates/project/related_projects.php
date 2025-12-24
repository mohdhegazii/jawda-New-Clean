<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects - MODIFIED
----------------------------------------------------------------------------- */

function get_my_related_projects() {

    ob_start();

    $current_post_id = get_the_ID();
    $related_ids = get_post_meta($current_post_id, '_related_projects_ids', true);

    if (empty($related_ids) || !is_array($related_ids)) {
        $related_ids = [];
    }

    // Ensure the array contains only integers.
    $related_ids = array_map('intval', $related_ids);
    $related_ids = array_filter($related_ids);

    if (!empty($related_ids)) {
        ?>
        <div class="related-projects-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="headline">
                            <h2><?php txt( 'Related Projects' ); ?></h2>
                            <div class="separator"></div>
                        </div>
                    </div>
                </div>
                <div class="row featured-slider">
                    <?php foreach ($related_ids as $project_id) : ?>
                        <div class="col-md-4">
                            <?php get_my_project_box($project_id); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

  $content = ob_get_clean();
  echo minify_html( $content );

}
