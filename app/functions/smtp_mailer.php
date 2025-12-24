<?php

add_action( 'phpmailer_init', 'crb_configure_smtp' );
function crb_configure_smtp( $phpmailer ) {
    global $jawda_force_secondary_smtp;

    if ( empty( $jawda_force_secondary_smtp ) ) {
        return;
    }

    // Get the saved SMTP settings
    $host       = carbon_get_theme_option( 'crb_smtp_host' );
    $port       = carbon_get_theme_option( 'crb_smtp_port' );
    $username   = carbon_get_theme_option( 'crb_smtp_username' );
    $password   = carbon_get_theme_option( 'crb_smtp_password' );
    $from_email = carbon_get_theme_option( 'crb_smtp_from_email' );
    $from_name  = carbon_get_theme_option( 'crb_smtp_from_name' );
    $encryption = carbon_get_theme_option( 'crb_smtp_encryption' );

    // Only configure if the host and port are set
    if ( empty( $host ) || empty( $port ) ) {
        return;
    }

    // Set the mailer to use SMTP
    $phpmailer->isSMTP();

    // Set the SMTP server details
    $phpmailer->Host       = $host;
    $phpmailer->Port       = $port;
    $phpmailer->Username   = $username;
    $phpmailer->Password   = $password;

    // Set the encryption type
    if ( $encryption !== 'none' ) {
        $phpmailer->SMTPSecure = $encryption;
    }

    // Enable SMTP authentication
    $phpmailer->SMTPAuth = true;

    // Set the From address, but only if one is provided
    if ( ! empty( $from_email ) ) {
        $phpmailer->From = $from_email;
    }

    // Set the From name, but only if one is provided
    if ( ! empty( $from_name ) ) {
        $phpmailer->FromName = $from_name;
    }
}