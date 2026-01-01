<?php
/**
 * Tables REST API controller for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Rest;

use Affinite\DBManager\Services\AccessService;
use Affinite\DBManager\Services\TableService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tables REST API controller.
 */
final class TablesController extends WP_REST_Controller {

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
	protected $rest_base = 'tables';

	/**
	 * Access service instance.
	 *
	 * @var AccessService
	 */
	private AccessService $access_service;

	/**
	 * Table service instance.
	 *
	 * @var TableService
	 */
	private TableService $table_service;

	/**
	 * Constructor.
	 *
	 * @param AccessService $access_service Access service instance.
	 */
	public function __construct( AccessService $access_service ) {
		$this->access_service = $access_service;
		$this->table_service  = new TableService( $access_service );
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
					'callback'            => array( $this, 'get_tables' ),
					'permission_callback' => array( $this, 'get_tables_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_table' ),
					'permission_callback' => array( $this, 'create_table_permissions_check' ),
					'args'                => $this->get_create_table_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<name>[a-zA-Z0-9_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_table' ),
					'permission_callback' => array( $this, 'get_tables_permissions_check' ),
					'args'                => array(
						'name' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_table' ),
					'permission_callback' => array( $this, 'delete_table_permissions_check' ),
					'args'                => array(
						'name' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<name>[a-zA-Z0-9_]+)/lock',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'lock_table' ),
					'permission_callback' => array( $this, 'modify_table_permissions_check' ),
					'args'                => array(
						'name' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<name>[a-zA-Z0-9_]+)/unlock',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'unlock_table' ),
					'permission_callback' => array( $this, 'modify_table_permissions_check' ),
					'args'                => array(
						'name' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if user has permission to read tables.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_tables_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view tables.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( ! $this->access_service->is_active() ) {
			return new \WP_Error(
				'db_manager_inactive',
				__( 'DB Manager is not active.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( ! $this->access_service->current_user_has_access() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access DB Manager.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if user has permission to create tables.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function create_table_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		return $this->get_tables_permissions_check( $request );
	}

	/**
	 * Check if user has permission to delete tables.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function delete_table_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		$check = $this->get_tables_permissions_check( $request );

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$table_name = $request->get_param( 'name' );

		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot delete a locked table.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if user has permission to modify table locks.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function modify_table_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		return $this->get_tables_permissions_check( $request );
	}

	/**
	 * Get all tables.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_tables( WP_REST_Request $request ): WP_REST_Response {
		$tables = $this->table_service->get_tables();

		return new WP_REST_Response( $tables, 200 );
	}

	/**
	 * Get a single table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function get_table( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'name' );
		$table      = $this->table_service->get_table( $table_name );

		if ( null === $table ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $table, 200 );
	}

	/**
	 * Create a new table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function create_table( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$params     = $request->get_json_params();
		$table_name = sanitize_text_field( $params['name'] ?? '' );
		$columns    = $params['columns'] ?? array();

		$result = $this->table_service->create_table( $table_name, $columns );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Table created successfully.', 'affinite-db-manager' ),
			),
			201
		);
	}

	/**
	 * Delete a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function delete_table( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'name' );
		$result     = $this->table_service->delete_table( $table_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Table deleted successfully.', 'affinite-db-manager' ),
			),
			200
		);
	}

	/**
	 * Lock a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function lock_table( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'name' );
		$result     = $this->table_service->lock_table( $table_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'is_locked' => true,
			),
			200
		);
	}

	/**
	 * Unlock a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function unlock_table( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'name' );
		$result     = $this->table_service->unlock_table( $table_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'is_locked' => false,
			),
			200
		);
	}

	/**
	 * Get create table arguments schema.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_create_table_args(): array {
		return array(
			'name'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'columns' => array(
				'required' => true,
				'type'     => 'array',
				'items'    => array(
					'type'       => 'object',
					'properties' => array(
						'name'           => array(
							'type'     => 'string',
							'required' => true,
						),
						'type'           => array(
							'type'     => 'string',
							'required' => true,
						),
						'length'         => array(
							'type' => 'integer',
						),
						'nullable'       => array(
							'type' => 'boolean',
						),
						'default'        => array(
							'type' => array( 'string', 'number', 'null' ),
						),
						'auto_increment' => array(
							'type' => 'boolean',
						),
						'primary'        => array(
							'type' => 'boolean',
						),
					),
				),
			),
		);
	}
}
