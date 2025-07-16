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


// =============== ACTIVATION HOOK =============== //

register_activation_hook(__FILE__, function () {
    // Set a transient to show the welcome notice on next page load
    set_transient('luc_show_activation_notice', true, 30);
});



/**
 * Get the custom login slug from plugin settings
 * Defaults to 'my-login' if not set
 *
 * @return string
 */
function luc_get_login_slug()
{
    $slug = get_option('luc_custom_login_slug', 'my-login');

    // Sanitize: remove leading/trailing slashes and whitespace
    $slug = trim($slug, "/ \t\n\r\0\x0B");

    return $slug ?: 'my-login';
}



// =============== HANDLE CUSTOM LOGIN URL =============== //

add_action('init', function () {
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $custom_slug = luc_get_login_slug();

    if ($request_uri === $custom_slug) {
        // This is your custom login page
        global $error, $interim_login, $user_login;
        $error = '';
        $interim_login = false;
        $user_login = '';
        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    // 🔽 ADD THIS EMERGENCY BLOCK BELOW THAT ⬇️
    if ($request_uri === 'login-emergency') {
        // Emergency login fallback
        global $error, $interim_login, $user_login;
        $error = '';
        $interim_login = false;
        $user_login = '';
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





// =============== ADMIN SETTINGS PAGE =============== //

/**
 * Add settings link under "Settings" menu in wp-admin
 */
add_action('admin_menu', function () {
    add_options_page(
        'Login URL Changer Settings',   // Page title
        'Login URL Changer',            // Menu title
        'manage_options',               // Capability
        'luc-settings',                 // Menu slug
        'luc_render_settings_page'      // Callback to render settings
    );
});

/**
 * Register plugin settings
 */
add_action('admin_init', function () {
    register_setting('luc_settings_group', 'luc_custom_login_slug');
});

/**
 * Render the plugin settings page
 */
function luc_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Login URL Changer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('luc_settings_group');
            do_settings_sections('luc_settings_group');

            // Get the saved slug, default to 'my-login'
            $slug = get_option('luc_custom_login_slug', 'my-login');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Custom Login Slug</th>
                    <td>
                        <input type="text" name="luc_custom_login_slug" value="<?php echo esc_attr($slug); ?>" />
                        <p class="description">This will be the new login URL. Example:
                            <code><?php echo home_url('/'); ?><strong><?php echo esc_html($slug); ?></strong></code>
                        </p>
                        <p><strong>Important:</strong> After changing this, bookmark your new login URL!</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// =============== ADMIN NOTICE =============== //

add_action('admin_notices', function () {
    // Check if transient is set
    if (get_transient('luc_show_activation_notice')) {
        delete_transient('luc_show_activation_notice'); // Remove it after use

        $slug = luc_get_login_slug();
        $login_url = esc_url(home_url("/$slug"));

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Login URL Changer Activated!</strong><br>';
        echo 'Your new login URL is: <a href="' . $login_url . '" target="_blank"><code>' . $login_url . '</code></a>.<br>';
        echo 'Please bookmark it now to avoid getting locked out.</p>';
        echo '</div>';
    }
});
