<?php
/**
 * Access service for Affinite DB Manager.
 *
 * Handles access control, whitelist management, and table locking.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Services;

/**
 * Access service class.
 */
final class AccessService {

	/**
	 * Option name for settings.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'affinite_db_manager_settings';

	/**
	 * Get plugin settings.
	 *
	 * @return array{active: bool, allowed_emails: array<string>, locked_tables: array<string>}
	 */
	public function get_settings(): array {
		$defaults = array(
			'active'         => false,
			'allowed_emails' => array(),
			'locked_tables'  => array(),
		);

		$settings = get_option( self::OPTION_NAME, $defaults );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array{active?: bool, allowed_emails?: array<string>, locked_tables?: array<string>} $settings Settings to update.
	 * @return bool Whether the settings were updated successfully.
	 */
	public function update_settings( array $settings ): bool {
		$current_settings = $this->get_settings();

		if ( isset( $settings['active'] ) ) {
			$current_settings['active'] = (bool) $settings['active'];
		}

		if ( isset( $settings['allowed_emails'] ) && is_array( $settings['allowed_emails'] ) ) {
			$current_settings['allowed_emails'] = array_map( 'sanitize_email', $settings['allowed_emails'] );
			$current_settings['allowed_emails'] = array_filter( $current_settings['allowed_emails'] );
			$current_settings['allowed_emails'] = array_values( array_unique( $current_settings['allowed_emails'] ) );
		}

		if ( isset( $settings['locked_tables'] ) && is_array( $settings['locked_tables'] ) ) {
			$current_settings['locked_tables'] = array_map( 'sanitize_text_field', $settings['locked_tables'] );
			$current_settings['locked_tables'] = array_filter( $current_settings['locked_tables'] );
			$current_settings['locked_tables'] = array_values( array_unique( $current_settings['locked_tables'] ) );
		}

		return update_option( self::OPTION_NAME, $current_settings, false );
	}

	/**
	 * Check if DB Manager is active.
	 *
	 * @return bool Whether DB Manager is active.
	 */
	public function is_active(): bool {
		$settings = $this->get_settings();

		return (bool) $settings['active'];
	}

	/**
	 * Activate DB Manager.
	 *
	 * @return bool Whether activation was successful.
	 */
	public function activate(): bool {
		return $this->update_settings( array( 'active' => true ) );
	}

	/**
	 * Deactivate DB Manager.
	 *
	 * @return bool Whether deactivation was successful.
	 */
	public function deactivate(): bool {
		return $this->update_settings( array( 'active' => false ) );
	}

	/**
	 * Check if current user can see the menu item.
	 *
	 * @return bool Whether current user can see menu.
	 */
	public function current_user_can_see_menu(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$settings = $this->get_settings();

		// If no emails are whitelisted, all admins can see menu.
		if ( empty( $settings['allowed_emails'] ) ) {
			return true;
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user || 0 === $current_user->ID ) {
			return false;
		}

		return in_array( $current_user->user_email, $settings['allowed_emails'], true );
	}

	/**
	 * Check if current user has access to DB Manager (tables).
	 *
	 * @return bool Whether current user has access.
	 */
	public function current_user_has_access(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$settings = $this->get_settings();

		// If no emails are whitelisted, nobody can see tables.
		if ( empty( $settings['allowed_emails'] ) ) {
			return false;
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user || 0 === $current_user->ID ) {
			return false;
		}

		return in_array( $current_user->user_email, $settings['allowed_emails'], true );
	}

	/**
	 * Check if current user can manage DB Manager (settings).
	 *
	 * Only admins can manage settings, regardless of whitelist.
	 *
	 * @return bool Whether current user can manage.
	 */
	public function current_user_can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Add email to whitelist.
	 *
	 * @param string $email Email to add.
	 * @return bool Whether the email was added successfully.
	 */
	public function add_allowed_email( string $email ): bool {
		$email = sanitize_email( $email );

		if ( empty( $email ) ) {
			return false;
		}

		$settings = $this->get_settings();

		if ( in_array( $email, $settings['allowed_emails'], true ) ) {
			return true;
		}

		$settings['allowed_emails'][] = $email;

		return $this->update_settings( array( 'allowed_emails' => $settings['allowed_emails'] ) );
	}

	/**
	 * Remove email from whitelist.
	 *
	 * @param string $email Email to remove.
	 * @return bool Whether the email was removed successfully.
	 */
	public function remove_allowed_email( string $email ): bool {
		$email    = sanitize_email( $email );
		$settings = $this->get_settings();

		$key = array_search( $email, $settings['allowed_emails'], true );

		if ( false === $key ) {
			return true;
		}

		unset( $settings['allowed_emails'][ $key ] );

		return $this->update_settings( array( 'allowed_emails' => array_values( $settings['allowed_emails'] ) ) );
	}

	/**
	 * Check if a table is locked.
	 *
	 * @param string $table Table name.
	 * @return bool Whether the table is locked.
	 */
	public function is_table_locked( string $table ): bool {
		$table    = sanitize_text_field( $table );
		$settings = $this->get_settings();

		return in_array( $table, $settings['locked_tables'], true );
	}

	/**
	 * Lock a table.
	 *
	 * @param string $table Table name.
	 * @return bool Whether the table was locked successfully.
	 */
	public function lock_table( string $table ): bool {
		$table = sanitize_text_field( $table );

		if ( empty( $table ) ) {
			return false;
		}

		$settings = $this->get_settings();

		if ( in_array( $table, $settings['locked_tables'], true ) ) {
			return true;
		}

		$settings['locked_tables'][] = $table;

		return $this->update_settings( array( 'locked_tables' => $settings['locked_tables'] ) );
	}

	/**
	 * Unlock a table.
	 *
	 * @param string $table Table name.
	 * @return bool Whether the table was unlocked successfully.
	 */
	public function unlock_table( string $table ): bool {
		$table    = sanitize_text_field( $table );
		$settings = $this->get_settings();

		$key = array_search( $table, $settings['locked_tables'], true );

		if ( false === $key ) {
			return true;
		}

		unset( $settings['locked_tables'][ $key ] );

		return $this->update_settings( array( 'locked_tables' => array_values( $settings['locked_tables'] ) ) );
	}

	/**
	 * Check if a table is a WordPress core table.
	 *
	 * @param string $table Table name.
	 * @return bool Whether the table is a core table.
	 */
	public function is_core_table( string $table ): bool {
		global $wpdb;

		$core_tables = array(
			$wpdb->prefix . 'posts',
			$wpdb->prefix . 'postmeta',
			$wpdb->prefix . 'comments',
			$wpdb->prefix . 'commentmeta',
			$wpdb->prefix . 'terms',
			$wpdb->prefix . 'term_taxonomy',
			$wpdb->prefix . 'term_relationships',
			$wpdb->prefix . 'termmeta',
			$wpdb->prefix . 'options',
			$wpdb->prefix . 'users',
			$wpdb->prefix . 'usermeta',
			$wpdb->prefix . 'links',
		);

		// Add multisite tables if applicable.
		if ( is_multisite() ) {
			$multisite_tables = array(
				$wpdb->prefix . 'blogs',
				$wpdb->prefix . 'blogmeta',
				$wpdb->prefix . 'site',
				$wpdb->prefix . 'sitemeta',
				$wpdb->prefix . 'signups',
				$wpdb->prefix . 'registration_log',
			);

			$core_tables = array_merge( $core_tables, $multisite_tables );
		}

		return in_array( $table, $core_tables, true );
	}

	/**
	 * Get allowed emails.
	 *
	 * @return array<string> List of allowed emails.
	 */
	public function get_allowed_emails(): array {
		$settings = $this->get_settings();

		return $settings['allowed_emails'];
	}

	/**
	 * Get locked tables.
	 *
	 * @return array<string> List of locked tables.
	 */
	public function get_locked_tables(): array {
		$settings = $this->get_settings();

		return $settings['locked_tables'];
	}
}
