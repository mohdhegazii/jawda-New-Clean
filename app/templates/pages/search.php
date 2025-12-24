<?php

/* -----------------------------------------------------------------------------
# Blog Page Body
----------------------------------------------------------------------------- */

function page_advanced_search_body($parameters){

$st = isset($_GET['st']) ? sanitize_text_field( wp_unslash( $_GET['st'] ) ) : '';
$parameters = search_parameters_filter($parameters);

if( !in_array($st, [1,2]) )
{
  die('<script>window.location.href = "'.siteurl.'";</script>');
}

if( isset($parameters['type']) )
{
  if (!is_numeric($parameters['type'])) {
    die('<script>window.location.href = "'.siteurl.'";</script>');
  }
}

if( isset($parameters['city']) )
{
  if (!is_numeric($parameters['city'])) {
    die('<script>window.location.href = "'.siteurl.'";</script>');
  }
}

?>
<style>
.hero-search{width:96%;margin-right:2%;margin-left:2%}
ul.tabs{margin:0;list-style:none;color:#a5a5a5;display:flex;justify-content:center;text-align:center;overflow:hidden}
ul.tabs li{background:#f2f2f2;padding:5px 15px;cursor:pointer;font-weight:700;min-width:35%;border-radius:5px 5px 0 0;margin:0 2px;font-size:1rem}
  ul.tabs li.current{background:<?php echo jawda_get_color(1); ?>;color:#FFF}
.tab-content{display:none;background:#FFF;padding:10px 25px;border-radius:5px;box-shadow:0 0 10px rgba(0,0,0,.3)}
.tab-content.current{display:inherit}
.hero-search form{width:100%;min-height:80px;display:flex;justify-content:center;align-items:center;padding:10px;flex-wrap:wrap}
.hero-search form .search-input,.hero-search form .search-select{color:#424242;height:35px;box-shadow:none;outline:0;width:100%;padding:0 15px;cursor:pointer;display:block;margin:10px 0;font-family:Cairo;font-weight:400;font-size:1rem;border:1px solid #a5a5a5;background-color:#FFF;border-radius:5px}
.hero-search form .search-select{appearance:none;-moz-appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml;utf8,<svg fill='gray' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");background-position:15px;background-repeat:no-repeat}
.hero-search form .search-input:focus,.hero-search form .search-select:focus{background-color:#eee}
.hero-search form input::placeholder{opacity:.8}
.hero-search form .search-select option{font-family:Cairo;background-color:#eee;padding:5px 0;display:block;font-size:1rem;color:#212121}
.hero-search form .search-submit{font-weight:700;background-color:#235B4E;color:#FFF;padding:8px 5px;white-space:nowrap;border:none;display:inline-block;cursor:pointer;transition:all .5s linear;margin-top:10px;font-family:Cairo,sans-serif;width:100%;border-radius:5px;text-align:center;font-size:1rem}
  .hero-search form .search-submit:hover{background-color:<?php echo jawda_get_color(1); ?>}
.wpas-field,.wpas-field.wpas-submit-field{width:100%}
  .advanced{width:96%;margin:10px 2%;font-weight:700;line-height:30px;color:<?php echo jawda_get_color(1); ?>;font-size:1rem;white-space:nowrap;text-align:left}
  .advanced i{border:1px solid <?php echo jawda_get_color(1); ?>;border-radius:20px;height:22px;width:22px;line-height:20px;font-size:14px;display:inline-block;text-align:center;margin-left:5px}
.advanced:hover{color:#000}
@media only screen and (min-width:1200px){.advanced,.hero-search{width:86%;margin-right:7%;margin-left:7%}
.hero-search form{justify-content:space-around;flex-wrap:nowrap;font-weight:700;width:100%;padding:0}
.hero-search form .search-input,.hero-search form .search-select{width:97%;margin:0;padding-left:40px;flex-grow:1}
.hero-search form .search-submit{margin:0}
.wpas-field{width:28%}
.wpas-field.wpas-submit-field{width:12%}
#wpas-submit{flex-grow:2;text-align:left}
}
  .alert {margin:25px 0;padding:20px 0;background:<?php echo jawda_get_color(1); ?>;color:#000;text-align:center;}
</style>
<div class="featured-projects">
  <?php get_search_box(); ?>
</div>

<?php

$is_text = ( isset($parameters['s']) && trim($parameters['s']) !== '' ) ? true : false;
$is_city = ( isset($parameters['city']) && trim($parameters['city']) !== '' ) ? true : false;
$is_type = ( isset($parameters['type']) && trim($parameters['type']) !== '' ) ? true : false;

$title = ( is_rtl() ) ? 'نتائج البحث ' : 'Search Results ';
if( $is_text ){
  $title .= ( is_rtl() ) ? 'عن' : 'for';
  $title .= " '".esc_html($parameters['s'])."' ";
}
if( $is_city ){
  if ( function_exists('jawda_get_city') ) {
      $city_arr = jawda_get_city( $parameters['city'] );
      if ( $city_arr ) {
          $title .= ( is_rtl() ) ? 'في' : 'In';
          $c_name = is_rtl() ? $city_arr[ 'slug_ar' ] : $city_arr['name_en'];
          $title .= " '" . esc_html($c_name) . "' ";
      }
  } else {
      $city_term = get_term( $parameters['city'] );
      if ( $city_term && ! is_wp_error( $city_term ) ) {
        $title .= ( is_rtl() ) ? 'في' : 'In';
        $title .= " '".$city_term->name."' ";
      }
  }
}
if( $is_type ){
  $type_term = get_term( $parameters['type'] );
  if ( $type_term && ! is_wp_error( $type_term ) ) {
    $title .= ( is_rtl() ) ? 'نوع المشروع' : 'Project Type';
    $title .= " '".$type_term->name."' ";
  }
}

?>

<div class="unit-hero">
  <div class="container">
    <div class="row">
      <div class="col-md-12 center">
        <h1 class="project-headline"><?php echo esc_html($title); ?></h1>
      </div>
    </div>
  </div>
</div>

<?php
$args = search_helper($parameters);
$the_query = new WP_Query( $args );
?>

<div class="units-page">
  <div class="container">
    <div class="row">
      <?php
      //
      if ( $the_query->have_posts() ) :
        while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
        <div class="col-md-4 projectbxspace">
          <?php if ( $st == 1 ): ?>
            <?php get_my_project_box(get_the_ID()); ?>
          <?php elseif( $st == 2 ): ?>
            <?php get_my_property_box(get_the_ID()); ?>
          <?php endif; ?>
        </div>
        <?php endwhile;

      else:

      ?>
      <div style="text-align:center;">
        <div class="container">
          <div class="row">
            <div class="col-md-12">
              <div class="alert">
                <?php if(is_rtl()): ?>
                  <h3>لا يوجد نتائج لهذا البحث جرب البحث بكلمات أخرى</h3>
                <?php else: ?>
                  <h3>There are no results for this search. Try searching in other words</h3>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php

      //
      endif;

      wp_reset_postdata();

      ?>
    </div>
        <div class="row">
      <div class="col-md-12">
        <div class="blognavigation">
            <?php
            $big = 999999999; // need an unlikely integer
            echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'current' => max(1, get_query_var('paged')),
                'total' => $the_query->max_num_pages,
                'prev_text' => (is_rtl()) ? 'السابق' : '« Previous',
                'next_text' => (is_rtl()) ? 'التالي' : 'Next »',
            ));
            ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php


}