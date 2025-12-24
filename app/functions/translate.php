<?php


// Security Check
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
-- Translate
----------------------------------------------------------------------------- */

function txt($txt){

  $t= [
    'phone' => 'التليفون',
    'email' => 'البريد الالكتروني',
    "Contact Us" => "اتصل بنا",
    'Find your dream home' => 'ابحث عن منزل احلامك',
    'The most popular Cities' => 'أشهر المناطق',
    'Find your favorite property in the most distinguished areas' => 'ابحث عن عقارك المفضل في اكثر المناطق تميز',
    'Project' => 'مشروع',
    'Featured Projects' => 'المشروعات المميزة',
    'A distinguished group of the most important and famous projects' => 'مجموعة متميزة من اهم و اشهر المشروعات',
    'Starting from' => 'تبدأ من',
    'EGP' => 'ج.م',
    'Advertise your property for free' => 'اعلن عن عقارك مجانا',
    'Now you can announce the sale or rent of your property for free in a quick and easy way' => 'دلوقتي تقدر تعلن عن بيع او ايجار عقارك مجانا بطريقة سريعة وسهلة',
    'add your property' => 'اضف عقارك',
    'Connect with us' => 'تواصل معنا',
    'more details' => 'مزيد من التفاصيل',
    'Latest Articles' => 'أحدث المقالات',
    "Keep up with what's new in the real estate world" => 'تابع كل ما هو جديد في عالم العقارات',
    'Latest Projects' => 'أحدث المشروعات',
    'Most Popular Developers' => 'أشهر المطورين',
    'to contact us' => 'للتواصل معنا',
    'Call' => 'اتصل على',
    'Address' => 'العنوان',
    'Important links' => 'روابط مهمة',
    'About' => 'عن',
    'Details' => 'التفاصيل',
    'Project units' => 'وحدات المشروع',
    'Project details' => 'تفاصيل المشروع',
    'Project Features' => 'مميزات المشروع',
    'Prices starting from' => 'اسعار تبدأ من',
    'property details' => 'تفاصيل الوحدة',
    'Similar units' => 'الوحدات المشابهة',
    'Similar projects' => 'المشروعات المشابهة',
    // '' => '',
  ];

  if ( is_rtl() == 'rtl' ) {
    echo ( isset($t[$txt]) ) ? $t[$txt] : $txt;
  }

  else
  {
    echo $txt;
  }

}
