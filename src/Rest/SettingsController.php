<?php
/**
 * Settings REST API controller for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Rest;

use Affinite\DBManager\Services\AccessService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Settings REST API controller.
 */
final class SettingsController extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'affinite-db-manager/v1';

	/**
	 * Resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

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
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_settings_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => $this->get_settings_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'activate' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deactivate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'deactivate' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/emails',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_email' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => array(
						'email' => array(
							'required'          => true,
							'type'              => 'string',
							'format'            => 'email',
							'sanitize_callback' => 'sanitize_email',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_email' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => array(
						'email' => array(
							'required'          => true,
							'type'              => 'string',
							'format'            => 'email',
							'sanitize_callback' => 'sanitize_email',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if user has permission to read settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_settings_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view settings.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if user has permission to update settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function update_settings_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to modify settings.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = $this->access_service->get_settings();

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$params = $request->get_json_params();

		$settings_to_update = array();

		if ( isset( $params['active'] ) ) {
			$settings_to_update['active'] = (bool) $params['active'];
		}

		if ( isset( $params['allowed_emails'] ) && is_array( $params['allowed_emails'] ) ) {
			$settings_to_update['allowed_emails'] = $params['allowed_emails'];
		}

		if ( isset( $params['locked_tables'] ) && is_array( $params['locked_tables'] ) ) {
			$settings_to_update['locked_tables'] = $params['locked_tables'];
		}

		$result = $this->access_service->update_settings( $settings_to_update );

		if ( ! $result ) {
			return new \WP_Error(
				'update_failed',
				__( 'Failed to update settings.', 'affinite-db-manager' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $this->access_service->get_settings(),
			),
			200
		);
	}

	/**
	 * Activate DB Manager.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function activate( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->access_service->activate();

		return new WP_REST_Response(
			array(
				'success' => $result,
				'active'  => $this->access_service->is_active(),
			),
			200
		);
	}

	/**
	 * Deactivate DB Manager.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function deactivate( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->access_service->deactivate();

		return new WP_REST_Response(
			array(
				'success' => $result,
				'active'  => $this->access_service->is_active(),
			),
			200
		);
	}

	/**
	 * Add email to whitelist.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function add_email( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$email = $request->get_param( 'email' );

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new \WP_Error(
				'invalid_email',
				__( 'Invalid email address.', 'affinite-db-manager' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->access_service->add_allowed_email( $email );

		return new WP_REST_Response(
			array(
				'success' => $result,
				'emails'  => $this->access_service->get_allowed_emails(),
			),
			200
		);
	}

	/**
	 * Remove email from whitelist.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function remove_email( WP_REST_Request $request ): WP_REST_Response {
		$email = $request->get_param( 'email' );

		$result = $this->access_service->remove_allowed_email( $email );

		return new WP_REST_Response(
			array(
				'success' => $result,
				'emails'  => $this->access_service->get_allowed_emails(),
			),
			200
		);
	}

	/**
	 * Get settings arguments schema.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_settings_args(): array {
		return array(
			'active'         => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'allowed_emails' => array(
				'type'  => 'array',
				'items' => array(
					'type'   => 'string',
					'format' => 'email',
				),
			),
			'locked_tables'  => array(
				'type'  => 'array',
				'items' => array(
					'type' => 'string',
				),
			),
		);
	}
}
