<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_why_us(){

  ob_start();

  ?>

  <!--Main Contact Form-->
	<div class="contact-us">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="contact-box">
						<div class="contact-form">
							<p><?php txt('Connect with us'); ?></p>
							<span class="form-title"><?php get_text('ارسل بياناتك و سنتواصل معك في اقرب وقت ممكن','Submit your information and we will contact you as soon as possible'); ?></span>
							<?php my_home_contact_form(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--End Main Contact Form-->

  <?php

  $content = ob_get_clean();
  echo minify_html($content);


}
