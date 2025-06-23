<?php
/**
 * Fired when the plugin is uninstalled.
 */

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// @TODO: Define uninstall functionality here: https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/