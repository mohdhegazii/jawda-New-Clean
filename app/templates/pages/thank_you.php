<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_header
----------------------------------------------------------------------------- */

function get_my_page_thank_you(){

  ob_start();

  if( carbon_get_theme_option( 'jawda_thankyou_script' ) ){
    echo carbon_get_theme_option( 'jawda_thankyou_script' );
  }

  ?>

    <style media="screen">
      .thkyimg img {width:auto;max-width:100%;height:auto;}
      .backbtn {display:inline-block;padding:5px 25px;background:#C59B3B;color:#fff;font-size:1.1rem;margin:1rem 0;border-radius:5px;cursor:pointer;}
      .thanksocial {text-align:center;padding:40px 0;font-size:2rem;}
      .thanksocial ul {list-style:none;padding:0;}
      .thanksocial ul li {display:inline-block;margin:0 2px;}
      .thanksocial ul li a {display:block;width:50px;height:50px;line-height:50px;text-align:center;padding:0;background:#C59B3B;color:#fff;border-radius:50%;}
    </style>

    <div class="thankyouimg">
      <div class="container">
        <div class="row">
          <div class="col-md-12 center thkyimg">
            <img src="<?php echo wimgurl.'thankyou.png'; ?>" alt="Thank You">
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="thanksocial">
            <?php get_my_social(); ?>
          </div>
        </div>
      </div>
    </div>

    <!--Project Main-->
    <div class="project-main">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="content-box center">
              <h2>
                <?php get_text(
              'شكرا لتواصلك معنا ، سوف يقوم أحد اعضاء فريق خدمة العملاء بالتواصل معك في اقرب وقت',
              'Thank you for contacting us, a member of the customer service team will contact you as soon as possible'
            ); ?>
              </h2>
              <input action="action"onclick="window.history.go(-1); return false;"type="submit"value="<?php get_text('العودة للصفحة السابقة','Return to previous page'); ?>" class="backbtn">
            </div>
          </div>
        </div>
      </div>
    </div>
  <!--End main-->



  <?php

  $content = ob_get_clean();
  echo minify_html($content);


}
