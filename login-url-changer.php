<?php
/*
Plugin Name: Login URL Changer
Plugin URI: 
Description: Change the default WordPress login URL to a custom one for better security.
Version: 1.0
Author: Samiul H Bro
Author URI: https://facebook.com/samiulhpranto
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: login-url-changer
*/


defined('ABSPATH') or exit;

// Define your custom login slug
define('LUC_LOGIN_SLUG', 'my-login'); // You can change this to anything you want

// Check if the current request matches the custom login URL
add_action('init', function () {
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    
    if ($request_uri === LUC_LOGIN_SLUG) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
});


// Blocks Default LOGIN URL
add_action('init', function () {
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // If someone tries to access wp-login.php directly, block it
    if ($request_uri === 'wp-login.php' || strpos($request_uri, 'wp-login.php?') === 0) {
        wp_die('This page is not available.', 'Not Found', ['response' => 404]);
    }
});


// Block /wp-admin Access for Non-Logged-In Users
add_action('admin_init', function () {
    // Allow access to AJAX from frontend
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    // If user is not logged in and trying to access /wp-admin
    if (!is_user_logged_in()) {
        wp_redirect(home_url());
        exit;
    }
});
