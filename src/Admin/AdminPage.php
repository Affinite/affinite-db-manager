<?php
/**
 * Admin page for Affinite DB Manager with tabs.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Admin;

use Affinite\DBManager\Services\AccessService;

/**
 * Admin page class with tabs.
 */
final class AdminPage {

	/**
	 * Access service instance.
	 *
	 * @var AccessService
	 */
	private AccessService $access_service;

	/**
	 * Constructor.
	 *
	 * @param AccessService $access_service Access service instance.
	 */
	public function __construct( AccessService $access_service ) {
		$this->access_service = $access_service;
	}

	/**
	 * Get current active tab.
	 *
	 * @return string Active tab name.
	 */
	private function get_active_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
		return in_array( $tab, array( 'settings', 'tables' ), true ) ? $tab : 'settings';
	}

	/**
	 * Render the admin page with tabs.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'affinite-db-manager' )
			);
		}

		// Check if user can see menu (same logic as menu registration).
		if ( ! $this->access_service->current_user_can_see_menu() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'affinite-db-manager' )
			);
		}

		$active_tab = $this->get_active_tab();
		$settings_url = admin_url( 'admin.php?page=affinite-db-manager&tab=settings' );
		$tables_url   = admin_url( 'admin.php?page=affinite-db-manager&tab=tables' );

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'DB Manager', 'affinite-db-manager' ); ?></h1>

			<nav class="nav-tab-wrapper wp-clearfix">
				<a href="<?php echo esc_url( $settings_url ); ?>" 
				   class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'Settings', 'affinite-db-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( $tables_url ); ?>" 
				   class="nav-tab <?php echo $active_tab === 'tables' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'Tables', 'affinite-db-manager' ); ?>
				</a>
			</nav>

			<?php if ( 'settings' === $active_tab ) : ?>
				<div id="affinite-db-manager-settings-root"></div>
			<?php else : ?>
				<div id="affinite-db-manager-root"></div>
			<?php endif; ?>
		</div>
		<?php
	}
}

