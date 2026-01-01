<?php
/**
 * Uninstall script for Affinite DB Manager.
 *
 * This file is called when the plugin is uninstalled.
 * It handles cleaning up plugin data from the database.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user capabilities.
if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

/**
 * Delete plugin options.
 */
function affinite_db_manager_delete_options(): void {
	delete_option( 'affinite_db_manager_settings' );
}

/**
 * Delete plugin options from all sites in multisite.
 */
function affinite_db_manager_delete_multisite_options(): void {
	global $wpdb;

	$blog_ids = $wpdb->get_col(
		"SELECT blog_id FROM {$wpdb->blogs} WHERE archived = '0' AND spam = '0' AND deleted = '0'"
	);

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		affinite_db_manager_delete_options();
		restore_current_blog();
	}
}

// Handle multisite.
if ( is_multisite() ) {
	affinite_db_manager_delete_multisite_options();
} else {
	affinite_db_manager_delete_options();
}
