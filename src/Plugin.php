<?php
/**
 * Main plugin class for Affinite DB Manager.
 *
 * This class is responsible for initializing the plugin, handling activation
 * and deactivation hooks, and setting up the plugin's functionality.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager;

use Affinite\DBManager\Admin\AdminPage;
use Affinite\DBManager\Rest\SettingsController;
use Affinite\DBManager\Rest\TablesController;
use Affinite\DBManager\Rest\ColumnsController;
use Affinite\DBManager\Rest\IndexesController;
use Affinite\DBManager\Rest\RelationsController;
use Affinite\DBManager\Rest\DataController;
use Affinite\DBManager\Services\AccessService;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * Plugin slug for text domain and other identifiers.
	 *
	 * @var string
	 */
	protected string $plugin_slug = 'affinite-db-manager';

	/**
	 * Singleton instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	protected static ?Plugin $instance = null;

	/**
	 * Admin page instance.
	 *
	 * @var AdminPage|null
	 */
	protected ?AdminPage $admin_page = null;

	/**
	 * Access service instance.
	 *
	 * @var AccessService|null
	 */
	protected ?AccessService $access_service = null;

	/**
	 * Constructor.
	 *
	 * Initializes the plugin by setting up hooks and loading dependencies.
	 */
	private function __construct() {
		$this->access_service = new AccessService();

		$this->init();
		if ( is_admin() ) {
			$this->admin_init();
		}
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return $this->plugin_slug;
	}

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @return Plugin The singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation hook callback.
	 *
	 * Handles tasks to perform when the plugin is activated.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 */
	public static function activate( bool $network_wide ): void {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				$blog_ids = self::get_blog_ids();
				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
					restore_current_blog();
				}
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Deactivation hook callback.
	 *
	 * Handles tasks to perform when the plugin is deactivated.
	 *
	 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
	 */
	public static function deactivate( bool $network_wide ): void {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				$blog_ids = self::get_blog_ids();
				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_deactivate();
					restore_current_blog();
				}
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Activate new site callback for multisite.
	 *
	 * @param int $blog_id The ID of the new site.
	 */
	public function activate_new_site( int $blog_id ): void {
		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}
		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog IDs in a multisite network.
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return array<int> An array of blog IDs.
	 */
	private static function get_blog_ids(): array {
		global $wpdb;

		return $wpdb->get_col(
			"SELECT blog_id FROM {$wpdb->blogs} WHERE archived = '0' AND spam = '0' AND deleted = '0'"
		);
	}

	/**
	 * Perform activation tasks for a single site.
	 */
	private static function single_activate(): void {
		self::init_default_options();
	}

	/**
	 * Initialize default plugin options.
	 */
	private static function init_default_options(): void {
		$existing_options = get_option( 'affinite_db_manager_settings' );

		if ( false === $existing_options ) {
			global $wpdb;

			// Get all existing tables and mark them as locked by default.
			$tables        = $wpdb->get_col( 'SHOW TABLES' );
			$locked_tables = array();

			foreach ( $tables as $table ) {
				$locked_tables[] = $table;
			}

			$default_options = array(
				'active'         => false,
				'allowed_emails' => array(),
				'locked_tables'  => $locked_tables,
			);

			add_option( 'affinite_db_manager_settings', $default_options, '', false );
		}
	}

	/**
	 * Perform deactivation tasks for a single site.
	 */
	private static function single_deactivate(): void {
		// Clean up scheduled events if any.
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain(): void {
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain(
			$domain,
			AFFINITE_DB_MANAGER_PATH . 'languages/' . $domain . '-' . $locale . '.mo'
		);
	}

	/**
	 * Initialize the plugin.
	 *
	 * Set up hooks and other initialization tasks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
	}

	/**
	 * Initialize admin-specific functionality.
	 */
	public function admin_init(): void {
		$this->admin_page = new AdminPage( $this->access_service );

		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_admin_menu(): void {
		// Only show menu if user has access.
		if ( ! $this->access_service->current_user_can_see_menu() ) {
			return;
		}

		add_submenu_page(
			'tools.php',
			__( 'DB Manager', 'affinite-db-manager' ),
			__( 'DB Manager', 'affinite-db-manager' ),
			'manage_options',
			'affinite-db-manager',
			array( $this->admin_page, 'render' ),
			10
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$allowed_pages = array(
			'tools_page_affinite-db-manager',
		);

		if ( ! in_array( $hook_suffix, $allowed_pages, true ) ) {
			return;
		}

		$asset_file = AFFINITE_DB_MANAGER_PATH . 'build/js/admin.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = array(
				'dependencies' => array( 'wp-element', 'wp-api-fetch', 'wp-i18n', 'wp-components' ),
				'version'      => self::VERSION,
			);
		}

		wp_enqueue_script(
			'affinite-db-manager-admin',
			AFFINITE_DB_MANAGER_URL . 'build/js/admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'affinite-db-manager-admin',
			AFFINITE_DB_MANAGER_URL . 'build/js/admin.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_localize_script(
			'affinite-db-manager-admin',
			'affiniteDbManager',
			array(
				'restUrl'     => rest_url( 'affinite-db-manager/v1/' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'currentPage' => $hook_suffix,
				'i18n'        => array(
					'save'          => __( 'Save', 'affinite-db-manager' ),
					'cancel'        => __( 'Cancel', 'affinite-db-manager' ),
					'delete'        => __( 'Delete', 'affinite-db-manager' ),
					'confirm'       => __( 'Confirm', 'affinite-db-manager' ),
					'loading'       => __( 'Loading...', 'affinite-db-manager' ),
					'error'         => __( 'Error', 'affinite-db-manager' ),
					'success'       => __( 'Success', 'affinite-db-manager' ),
					'noAccess'      => __( 'DB Manager is not active or you do not have permission.', 'affinite-db-manager' ),
					'contactAdmin'  => __( 'Contact administrator.', 'affinite-db-manager' ),
				),
			)
		);

		wp_set_script_translations(
			'affinite-db-manager-admin',
			'affinite-db-manager',
			AFFINITE_DB_MANAGER_PATH . 'languages'
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		$settings_controller  = new SettingsController( $this->access_service );
		$tables_controller    = new TablesController( $this->access_service );
		$columns_controller   = new ColumnsController( $this->access_service );
		$indexes_controller   = new IndexesController( $this->access_service );
		$relations_controller = new RelationsController( $this->access_service );
		$data_controller      = new DataController( $this->access_service );

		$settings_controller->register_routes();
		$tables_controller->register_routes();
		$columns_controller->register_routes();
		$indexes_controller->register_routes();
		$relations_controller->register_routes();
		$data_controller->register_routes();
	}

	/**
	 * Get the access service instance.
	 *
	 * @return AccessService The access service instance.
	 */
	public function get_access_service(): AccessService {
		return $this->access_service;
	}
}
