<?php

function get_my_styles(){

  $cssfile = '';
  $cssContents = styles_list();

  foreach($cssContents as $file) {


    $cssfc = array(
      "~url~",
      "~imgurl~",
      "~fonts~",
      "~color1~",
      "~color2~",
      "~color3~",
    );

    $cssrv = array(
      get_template_directory_uri(),
      get_template_directory_uri().'/assets/images',
      get_template_directory_uri().'/assets/font',
      jawda_get_color(1),
      jawda_get_color(2),
      jawda_get_color(3),
    );


    $cssfile .=str_replace($cssfc, $cssrv,file_get_contents($file));
  }

  $style = '<style>';
  $style .= minifyCss($cssfile);
  $style .= '.menu-bar { background-color: ' . jawda_get_color(2) . ' !important; }';
  $style .= '.navi .menutoggel i { color: #fff !important; }';
  $style .= '.menu-bar .language a { color: ' . jawda_get_color(1) . ' !important; }';
  $style .= '
    .related-box { display: flex; flex-direction: column; height: 100%; }
    .related-data { flex-grow: 1; display: flex; flex-direction: column; }
    .related-price-container { margin-top: auto; }
    .project-services { display: flex; flex-direction: column; gap: 24px; }
    .project-services__list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; }
    .project-services__list--columns-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
    .project-services__list--columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    .project-services__list--columns-3 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    .project-services__list--columns-4 { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }
    .project-services__list--columns-5 { grid-template-columns: repeat(5, minmax(0, 1fr)) !important; }
    .project-services__item--toggle { order: initial !important; }
    .project-services__item--toggle--bottom { order: initial !important; grid-column: auto !important; }
    .project-services__item--extra[hidden] { display: none !important; }

    @media (max-width: 991px) {
      .project-services__list { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 12px; }
      .project-services__list--columns-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
      .project-services__list--columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }

    @media (max-width: 767px) {
      .project-services__item { min-height: 120px; padding: 12px; }
      .project-services__item--toggle { min-height: 120px; }
    }
    .related-projects-section {
        background-color: #FFFFFF;
        padding: 40px 0;
    }

    /* Sticky Breadcrumb Logic */
    .breadcrumbs {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        position: relative;
        scrollbar-width: none; /* Firefox */
        white-space: nowrap; /* Prevent text wrapping */
    }
    .breadcrumbs::-webkit-scrollbar {
        display: none; /* Safari/Chrome */
    }

    /* Prevent items from shrinking */
    .breadcrumbs .breadcrumb-item,
    .breadcrumbs .breadcrumbs__separator {
        flex-shrink: 0;
    }

    /* Target the first item (home icon) */
    .breadcrumbs > span:first-child,
    .breadcrumbs > span.breadcrumb-item:first-child,
    .breadcrumbs > .sticky-home-wrapper {
        position: sticky;
        inset-inline-start: 0;
        z-index: 99;
        background-color: #EEE; /* Matches .project-hero and .unit-hero background */
        padding-inline-end: 10px; /* Space after icon */
        display: inline-flex;
        align-items: center;
        height: 100%;
    }

    /* Apply website colors */
    .breadcrumbs a, .breadcrumbs .breadcrumb-item {
        color: ~color3~; /* Default text color */
    }
    .breadcrumbs a:hover {
        color: ~color1~; /* Active/Hover color */
    }
    .breadcrumbs .breadcrumb-item-current {
        color: ~color1~; /* Last item color */
        font-weight: bold;
    }
    .breadcrumbs i {
        color: ~color1~; /* Icon color */
    }

    /* Add gradient/shadow if needed to show separation */
    /*
    .breadcrumbs > span:first-child::after {
        content: "";
        position: absolute;
        top: 0;
        right: -10px;
        bottom: 0;
        width: 10px;
        background: linear-gradient(to right, rgba(255,255,255,1), rgba(255,255,255,0));
    }
    */
  ';
  $style .= '</style>'."\n";
  echo $style;

}

function jawda_enqueue_frontend_scripts() {
    $ldir = is_rtl() ? 'rtl' : 'ltr';

    // Enqueue FontAwesome for breadcrumb icons
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        [],
        '6.4.0'
    );

    wp_enqueue_script(
        'jawda-script',
        get_template_directory_uri() . '/assets/js/' . $ldir . '/script.js',
        ['jquery'],
        '01',
        true
    );

    $hero_search_i18n = [
        'projects'   => __('Projects', 'jawda'),
        'locations'  => __('Locations', 'jawda'),
        'developers' => __('Developers', 'jawda'),
        'rate_limit' => __('Too many requests. Please try again in a few seconds.', 'jawda'),
        'forbidden'  => __('Search is temporarily unavailable. Please try again.', 'jawda'),
    ];

    wp_localize_script('jawda-script', 'hero_search_i18n', $hero_search_i18n);

    // Legacy catalog logic removed
    $catalog_url = home_url( '/' ); // Fallback to home if no catalog
    if ( function_exists( 'jawda_get_projects_catalog_url' ) ) {
         $catalog_url = jawda_get_projects_catalog_url();
    } elseif ( defined('JAWDA_PROJECTS_CATALOG_URL') ) {
         $catalog_url = JAWDA_PROJECTS_CATALOG_URL;
    }

    $hero_search_vars = [
        'catalog_url' => $catalog_url,
    ];

    wp_localize_script('jawda-script', 'hero_search_vars', $hero_search_vars);

    // Breadcrumbs Scrolling Interaction
    wp_enqueue_script(
        'jawda-breadcrumbs',
        get_template_directory_uri() . '/assets/js/breadcrumbs.js',
        [], // No dependencies
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'jawda_enqueue_frontend_scripts');


function get_my_scripts(){
  $ldir = is_rtl() ? "rtl" : "ltr" ;
  $search_nonce = wp_create_nonce('search_nonce_action');
  $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
  wp_localize_script('jawda-script', 'global', [
      'ajax' => admin_url('admin-ajax.php'),
  ]);

  wp_localize_script('jawda-script', 'search_nonce', [
      'nonce' => $search_nonce,
  ]);

  wp_enqueue_script(
      'jawda-frontend-locations',
      get_template_directory_uri() . '/assets/js/frontend-locations.js',
      ['jquery'],
      defined('THEME_VERSION') ? THEME_VERSION : '1.0.0',
      true
  );

  wp_enqueue_script(
      'jawda-wjs-main',
      trailingslashit(wjsurl) . 'main.js',
      [],
      '1.0',
      true
  );
  // The line for script.js is removed from here

  ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const moreLessButton = document.querySelector('.more-less-button');
      if (moreLessButton) {
        moreLessButton.addEventListener('click', function () {
          const moreLinks = document.querySelector('.more-links');
          if (moreLinks.style.display === 'none') {
            moreLinks.style.display = 'block';
            <?php ob_start(); get_text("أقل", "Show Less"); $less_text = ob_get_clean(); ?>
            moreLessButton.textContent = '<?php echo esc_js($less_text); ?>';
          } else {
            moreLinks.style.display = 'none';
            <?php ob_start(); get_text("المزيد", "Show More"); $more_text = ob_get_clean(); ?>
            moreLessButton.textContent = '<?php echo esc_js($more_text); ?>';
          }
        });
      }
    });

    document.addEventListener('DOMContentLoaded', function () {
      const serviceSections = document.querySelectorAll('.project-services.project-services--collapsible');
      const columnClasses = [
        'project-services__list--columns-1',
        'project-services__list--columns-2',
        'project-services__list--columns-3',
        'project-services__list--columns-4',
        'project-services__list--columns-5'
      ];

      serviceSections.forEach(function (section) {
        const list = section.querySelector('.project-services__list');
        const toggle = section.querySelector('.project-services__item--toggle');
        if (!list || !toggle) {
          return;
        }

        const extras = Array.from(list.querySelectorAll('.project-services__item--extra'));
        const primaryItems = Array.from(list.querySelectorAll('.project-services__item--primary:not(.project-services__item--extra)'));
        const moreLabel = toggle.getAttribute('data-more-label') || toggle.textContent || '';
        const lessLabel = toggle.getAttribute('data-less-label') || moreLabel;

        const collapsedColumns = list.dataset.columnsCollapsed ? parseInt(list.dataset.columnsCollapsed, 10) : 0;
        const expandedColumns = list.dataset.columnsExpanded ? parseInt(list.dataset.columnsExpanded, 10) : collapsedColumns;
        const collapsedLimit = list.dataset.collapsedLimit ? parseInt(list.dataset.collapsedLimit, 10) : primaryItems.length;

        const setColumns = function (columns) {
          if (!columns) {
            return;
          }
          columnClasses.forEach(function (className) {
            list.classList.remove(className);
          });
          list.classList.add('project-services__list--columns-' + columns);
        };

        const collapse = function (shouldScroll) {
          section.classList.remove('project-services--expanded');
          toggle.setAttribute('aria-expanded', 'false');
          if (moreLabel) {
            toggle.textContent = moreLabel;
          }
          extras.forEach(function (item) {
            item.setAttribute('hidden', '');
          });
          if (extras.length) {
            list.insertBefore(toggle, extras[0]);
          } else {
            list.appendChild(toggle);
          }
          toggle.classList.remove('project-services__item--toggle--bottom');
          const fallback = Math.max(1, Math.min(5, collapsedLimit + (extras.length ? 1 : 0)));
          setColumns(collapsedColumns || fallback);
          if (shouldScroll) {
            const scrollOffset = 120;
            const listTop = list.getBoundingClientRect().top + window.pageYOffset;
            window.scrollTo({
              top: Math.max(listTop - scrollOffset, 0),
              behavior: 'smooth'
            });
          }
        };

        const expand = function () {
          section.classList.add('project-services--expanded');
          toggle.setAttribute('aria-expanded', 'true');
          toggle.textContent = lessLabel || moreLabel;
          extras.forEach(function (item) {
            item.removeAttribute('hidden');
          });
          list.appendChild(toggle);
          toggle.classList.add('project-services__item--toggle--bottom');
          const fallback = Math.max(1, Math.min(5, primaryItems.length + extras.length));
          setColumns(expandedColumns || collapsedColumns || fallback);
        };

        collapse(false);

        toggle.addEventListener('click', function () {
          if (section.classList.contains('project-services--expanded')) {
            collapse(true);
          } else {
            expand();
          }
        });
      });
    });



    function equalizeCardHeights() {
      document.querySelectorAll('.row').forEach(function(row) {
        const cards = row.querySelectorAll('.projectbxspace .related-box');
        if (cards.length > 1) {
          let maxHeight = 0;
          // Reset heights first to get the natural height
          cards.forEach(function(card) {
            card.style.height = 'auto';
          });
          // Find the max height
          cards.forEach(function(card) {
            if (card.offsetHeight > maxHeight) {
              maxHeight = card.offsetHeight;
            }
          });
          // Apply the max height to all cards in the row
          cards.forEach(function(card) {
            card.style.height = maxHeight + 'px';
          });
        }
      });
    }

    document.addEventListener('DOMContentLoaded', equalizeCardHeights);
    window.addEventListener('resize', equalizeCardHeights);

    // Auto-scroll breadcrumbs to the end
    document.addEventListener('DOMContentLoaded', function() {
        const breadcrumbs = document.querySelector('.breadcrumbs');
        if (breadcrumbs) {
            const lastItem = breadcrumbs.lastElementChild;
            if (lastItem) {
                lastItem.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'end' });
            }
        }
    });

  </script>
  <?php
}

/* -----------------  ------------------ */



function styles_list(){

  $ldir = is_rtl() ? "rtl" : "ltr" ;

  $cssContents = [];

  $cssContents['main'] = get_template_directory().'/assets/css/'.$ldir.'/main.css' ;

  if ( is_front_page() || is_home() ) {
    $cssContents['home'] = get_template_directory().'/assets/css/'.$ldir.'/home.css' ;
  }

  elseif ( is_single() || is_page()  ) {
    $cssContents['post'] = get_template_directory().'/assets/css/'.$ldir.'/single.css' ;
  }

  elseif( is_category() || is_tag() || is_tax() || is_search() || is_404() ){
    $cssContents['category'] = get_template_directory().'/assets/css/'.$ldir.'/single.css' ;
  }

  // Add support for Virtual Catalog pages
  elseif ( !empty($GLOBALS['jawda_current_location_context']) ) {
    $cssContents['virtual-catalog'] = get_template_directory().'/assets/css/'.$ldir.'/single.css' ;
  }

  return $cssContents;
}


function jawda_get_color($id)
{
  $d = [ 1 => '#DD3333', 2 => '#235B4E', 3 => '#424242' ];
  for ($i=1; $i <= 3; $i++) {
    $code = carbon_get_theme_option( 'jawda_color_'.$i );
    if ( $code !== NULL AND $code !== "" ) {
      $d[$i] = $code;
    }
  }
  return $d[$id];
}
