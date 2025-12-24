<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', 'crb_add_smtp_options_page' );
function crb_add_smtp_options_page() {
    Container::make( 'theme_options', __( 'SMTP Settings', 'crb' ) )
        ->set_page_parent( 'options-general.php' ) // Add as a submenu to "Settings"
        ->add_fields( array(
            Field::make( 'html', 'crb_smtp_heading' )
                ->set_html( '<h2>' . __( 'Configure SMTP for reliable email sending', 'crb' ) . '</h2>' ),

            Field::make( 'text', 'crb_smtp_from_email', __( 'From Email', 'crb' ) )
                ->set_attribute( 'placeholder', 'e.g., info@yourdomain.com' )
                ->set_help_text( __( 'The email address that emails will be sent from.', 'crb' ) ),

            Field::make( 'text', 'crb_smtp_from_name', __( 'From Name', 'crb' ) )
                ->set_attribute( 'placeholder', 'e.g., Your Site Name' )
                ->set_help_text( __( 'The name that emails will appear to be from.', 'crb' ) ),

            Field::make( 'separator', 'crb_smtp_separator', __( 'SMTP Server Configuration', 'crb' ) ),

            Field::make( 'text', 'crb_smtp_host', __( 'SMTP Host', 'crb' ) )
                ->set_attribute( 'placeholder', 'e.g., smtp.gmail.com' )
                ->set_required( true ),

            Field::make( 'select', 'crb_smtp_encryption', __( 'Encryption', 'crb' ) )
                ->add_options( array(
                    'none' => 'None',
                    'ssl' => 'SSL',
                    'tls' => 'TLS',
                ) )
                ->set_default_value( 'tls' ),

            Field::make( 'text', 'crb_smtp_port', __( 'SMTP Port', 'crb' ) )
                ->set_attribute( 'type', 'number' )
                ->set_attribute( 'placeholder', 'e.g., 587' )
                ->set_required( true ),

            Field::make( 'text', 'crb_smtp_username', __( 'SMTP Username', 'crb' ) )
                ->set_attribute( 'placeholder', __( 'Your Gmail address', 'crb' ) )
                ->set_required( true ),

            Field::make( 'text', 'crb_smtp_password', __( 'SMTP Password', 'crb' ) )
                ->set_attribute( 'type', 'password' )
                ->set_required( true )
                ->set_help_text( __( 'IMPORTANT: For Gmail, do not use your main password. Generate and use a 16-digit "App Password" from your Google Account security settings.', 'crb' ) ),
        ) );
}