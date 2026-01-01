<?php
/**
 * Plugin Name: Affinite DB Manager
 * Plugin URI: https://affinite.cz
 * Description: Complex database adminer for WordPress
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 8.1
 * Network: false
 * Author: Affinite
 * Author URI: https://affinite.cz
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: affinite-db-manager
 * Domain Path: /languages
 *
 * @package Affinite\DBManager
 */

declare(strict_types=1);

namespace Affinite\DBManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version constant.
 */
define( 'AFFINITE_DB_MANAGER_VERSION', '1.0.0' );

/**
 * Plugin directory path constant.
 */
define( 'AFFINITE_DB_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL constant.
 */
define( 'AFFINITE_DB_MANAGER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename constant.
 */
define( 'AFFINITE_DB_MANAGER_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( AFFINITE_DB_MANAGER_PATH . 'vendor/autoload.php' ) ) {
	require_once AFFINITE_DB_MANAGER_PATH . 'vendor/autoload.php';
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function affinite_db_manager_init(): void {
	Plugin::get_instance();
}

// Register activation hook.
register_activation_hook( __FILE__, [ Plugin::class, 'activate' ] );

// Register deactivation hook.
register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivate' ] );

// Initialize plugin after plugins loaded.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\affinite_db_manager_init' );
