<?php
/*
Plugin Name: WooCommerce reCAPTCHA Integration
Description: Adds Google reCAPTCHA to WooCommerce login and register pages without affecting the site performance.
Version: 1.0.0
Plugin URI: https://alexdraghici.dev/
Author: Alexandru Draghici
Author URI: https://alexdraghici.dev/
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    exit;
}

add_filter('woocommerce_general_settings', 'add_recaptcha_settings');

/**
 * @param $settings
 * @return mixed
 */
function add_recaptcha_settings($settings): mixed
{
    $settings[] = array(
        'name' => __('reCAPTCHA Settings', 'woocommerce'),
        'type' => 'title',
        'desc' => 'The following options are used to configure Google reCAPTCHA.',
        'id' => 'recaptcha_options'
    );
    $settings[] = array(
        'name' => __('Site Key', 'woocommerce'),
        'desc_tip' => 'Enter your Google reCAPTCHA site key.',
        'id' => 'woocommerce_recaptcha_site_key',
        'type' => 'text'
    );
    $settings[] = array(
        'name' => __('Secret Key', 'woocommerce'),
        'desc_tip' => 'Enter your Google reCAPTCHA secret key.',
        'id' => 'woocommerce_recaptcha_secret_key',
        'type' => 'text'
    );
    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'recaptcha_options'
    );

    return $settings;
}

add_action('wp_enqueue_scripts', 'enqueue_custom_script');

function enqueue_custom_script(): void
{
    if (is_account_page()) {
        wp_enqueue_script('custom-input-listener', plugins_url('/js/custom-input-listener' . recaptcha_is_debug() . '.js', __FILE__), ['jquery'], '1.0.3', true);
    }
}

add_action('woocommerce_login_form', 'add_recaptcha_field_login');
add_action('woocommerce_register_form', 'add_recaptcha_field_register');

/**
 * @return void
 */
function add_recaptcha_field_login(): void
{
    $site_key = get_option('woocommerce_recaptcha_site_key');
    
    echo '<div id="login-recaptcha" class="recaptcha-box" data-sitekey="' . esc_attr($site_key) . '"></div>';
}

/**
 * @return void
 */
function add_recaptcha_field_register(): void
{
    $site_key = get_option('woocommerce_recaptcha_site_key');
    
    echo '<div id="register-recaptcha" class="recaptcha-box" data-sitekey="' . esc_attr($site_key) . '"></div>';
}

// Validate reCAPTCHA on form submission
add_filter('woocommerce_process_login_errors', 'validate_recaptcha', 10, 3);
add_filter('woocommerce_process_registration_errors', 'validate_recaptcha', 10, 3);

/**
 * @param $validation_error
 * @param $username
 * @param $password
 * @return mixed|WP_Error
 */
function validate_recaptcha($validation_error, $username, $password): mixed
{
    $secret_key = get_option('woocommerce_recaptcha_secret_key');
    $recaptcha_response = $_POST['g-recaptcha-response'];

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
            'secret' => $secret_key,
            'response' => $recaptcha_response
        )
    ));

    $response_keys = json_decode(wp_remote_retrieve_body($response), true);

    if (intval($response_keys["success"]) !== 1) {
        return new WP_Error('invalid-recaptcha', __('Please verify that you are not a robot.', 'woocommerce'));
    }

    return $validation_error;
}

/**
 * @return string
 */
function recaptcha_is_debug(): string
{
    return (defined( 'SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
}