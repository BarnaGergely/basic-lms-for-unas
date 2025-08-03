<?php

/**
 * Uninstall script for Basic LMS for UNAS
 *
 * This file is executed when the plugin is uninstalled (deleted).
 * It cleans up all data created by the plugin.
 *
 * @package basic_lms_for_unas
 */

// Exit if accessed directly or if not in uninstall context
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function basic_lms_for_unas_uninstall() {
    // Get the managed page ID
    $page_id = get_option('basic_lms_activation_page_id');
    
    // Delete the managed page if it exists
    if ($page_id) {
        wp_delete_post($page_id, true); // true = force delete, bypass trash
    }
    
    // Clean up plugin options
    delete_option('basic_lms_activation_page_id');
    
    // Clean up any transients if we were using them
    delete_transient('basic_lms_unas_token');
    
    // Clean up user meta data if needed (be careful with this)
    // This would remove all the custom roles added by the plugin
    // Uncomment only if you want to remove all course access when uninstalling
    /*
    global $wpdb;
    $roles_to_remove = ['unaslms_course_1', 'unaslms_course_2', 'kurzus1'];
    foreach ($roles_to_remove as $role) {
        // Get all users with this role
        $users = get_users(array('role' => $role));
        foreach ($users as $user) {
            $user_obj = new WP_User($user->ID);
            $user_obj->remove_role($role);
        }
    }
    */
    
    // Flush rewrite rules one final time
    flush_rewrite_rules();
}

// Execute the uninstall function
basic_lms_for_unas_uninstall();