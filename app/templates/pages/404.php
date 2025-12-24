<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_404(){

  ob_start();

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
  .hero-search form .search-submit{font-weight:700;background-color:#000;color:#FFF;padding:8px 5px;white-space:nowrap;border:none;display:inline-block;cursor:pointer;transition:all .5s linear;margin-top:10px;font-family:Cairo,sans-serif;width:100%;border-radius:5px;text-align:center;font-size:1rem}
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
  <main class='container404'>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>4</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <span class='particle'>0</span>
    <article class='content404'>
      <h1>404</h1>
      <p><?php get_text('الصفحة المطلوبة غير موجودة','The page you requested was not found'); ?></p>
      <p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php get_text('العودة للصفحة الرئيسية','Go back to Home'); ?></a>
      </p>
    </article>
  </main>

  <style>
    .container404,.content404{position:relative;background:#fff}.container404{display:flex;align-items:center;justify-content:center;height:400px;color:#000;overflow:hidden}.content404{width:600px;max-width:100%;margin:20px;padding:60px 40px;text-align:center;box-shadow:-10px 10px 67px -12px rgba(0,0,0,.2);opacity:0;animation:apparition .8s 1.2s cubic-bezier(.39,.575,.28,.995) forwards}.content404 p{font-size:1.3rem;margin-top:0;margin-bottom:.6rem;color:#595959}.content404 p:last-child{margin-bottom:0}.content404 a{display:inline-block;margin-top:2rem;padding:.5rem 1rem;border:3px solid #595959;background:0 0;font-size:1rem;color:#595959;text-decoration:none;cursor:pointer;font-weight:700}.particle{position:absolute;display:block;pointer-events:none}.particle:nth-child(1){top:69.4813027744%;left:69.970845481%;font-size:29px;filter:blur(.02px);animation:23s floatReverse infinite}.particle:nth-child(2){top:92.5700365408%;left:17.6297747307%;font-size:21px;filter:blur(.04px);animation:23s float infinite}.particle:nth-child(3){top:3.8554216867%;left:36.8932038835%;font-size:30px;filter:blur(.06px);animation:37s floatReverse infinite}.particle:nth-child(4){top:84.7746650426%;left:65.6219392752%;font-size:21px;filter:blur(.08px);animation:38s floatReverse infinite}.particle:nth-child(5){top:33.8164251208%;left:87.5486381323%;font-size:28px;filter:blur(.1px);animation:36s float infinite}.particle:nth-child(6){top:14.598540146%;left:46.9667318982%;font-size:22px;filter:blur(.12px);animation:22s float2 infinite}.particle:nth-child(7){top:16.7281672817%;left:85.8835143139%;font-size:13px;filter:blur(.14px);animation:40s float infinite}.particle:nth-child(8){top:9.6852300242%;left:28.2651072125%;font-size:26px;filter:blur(.16px);animation:23s float2 infinite}.particle:nth-child(9){top:70.9599027947%;left:43.0107526882%;font-size:23px;filter:blur(.18px);animation:21s float2 infinite}.particle:nth-child(10){top:88.8888888889%;left:40.8560311284%;font-size:28px;filter:blur(.2px);animation:22s float infinite}.particle:nth-child(11){top:98.2800982801%;left:.9861932939%;font-size:14px;filter:blur(.22px);animation:31s floatReverse2 infinite}.particle:nth-child(12){top:52.0245398773%;left:46.3054187192%;font-size:15px;filter:blur(.24px);animation:24s floatReverse2 infinite}.particle:nth-child(13){top:3.9263803681%;left:95.5665024631%;font-size:15px;filter:blur(.26px);animation:22s floatReverse2 infinite}.particle:nth-child(14){top:49.0196078431%;left:70.8661417323%;font-size:16px;filter:blur(.28px);animation:23s floatReverse2 infinite}.particle:nth-child(15){top:12.7764127764%;left:45.3648915187%;font-size:14px;filter:blur(.3px);animation:21s float2 infinite}.particle:nth-child(16){top:36.9829683698%;left:3.9138943249%;font-size:22px;filter:blur(.32px);animation:32s float2 infinite}.particle:nth-child(17){top:50.0613496933%;left:13.7931034483%;font-size:15px;filter:blur(.34px);animation:35s floatReverse infinite}.particle:nth-child(18){top:33.0900243309%;left:17.6125244618%;font-size:22px;filter:blur(.36px);animation:21s float2 infinite}.particle:nth-child(19){top:47.5151515152%;left:1.9512195122%;font-size:25px;filter:blur(.38px);animation:29s float2 infinite}.particle:nth-child(20){top:95.0303030303%;left:85.8536585366%;font-size:25px;filter:blur(.4px);animation:27s float2 infinite}.particle:nth-child(21){top:.97799511%;left:81.5324165029%;font-size:18px;filter:blur(.42px);animation:27s floatReverse2 infinite}.particle:nth-child(22){top:16.7694204686%;left:59.3471810089%;font-size:11px;filter:blur(.44px);animation:32s float2 infinite}.particle:nth-child(23){top:36.7149758454%;left:24.3190661479%;font-size:28px;filter:blur(.46px);animation:26s float infinite}.particle:nth-child(24){top:67.4816625917%;left:6.8762278978%;font-size:18px;filter:blur(.48px);animation:21s float2 infinite}.particle:nth-child(25){top:46.8864468864%;left:81.452404318%;font-size:19px;filter:blur(.5px);animation:29s float2 infinite}.particle:nth-child(26){top:53.9759036145%;left:26.213592233%;font-size:30px;filter:blur(.52px);animation:34s float2 infinite}.particle:nth-child(27){top:31.067961165%;left:54.6875%;font-size:24px;filter:blur(.54px);animation:28s float infinite}.particle:nth-child(28){top:80.195599022%;left:74.6561886051%;font-size:18px;filter:blur(.56px);animation:27s float2 infinite}.particle:nth-child(29){top:57.3511543135%;left:96.7741935484%;font-size:23px;filter:blur(.58px);animation:23s floatReverse infinite}.particle:nth-child(30){top:92.2330097087%;left:78.125%;font-size:24px;filter:blur(.6px);animation:30s float infinite}.particle:nth-child(31){top:80.7881773399%;left:47.4308300395%;font-size:12px;filter:blur(.62px);animation:23s float2 infinite}@keyframes apparition{from{opacity:0;transform:translateY(100px)}to{opacity:1;transform:translateY(0)}}@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(180px)}}@keyframes floatReverse{0%,100%{transform:translateY(0)}50%{transform:translateY(-180px)}}@keyframes float2{0%,100%{transform:translateY(0)}50%{transform:translateY(28px)}}@keyframes floatReverse2{0%,100%{transform:translateY(0)}50%{transform:translateY(-28px)}}
  </style>

  <?php

  $content = ob_get_clean();
  echo minify_html($content);


}