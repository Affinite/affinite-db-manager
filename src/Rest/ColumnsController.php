<?php
/**
 * Columns REST API controller for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Rest;

use Affinite\DBManager\Services\AccessService;
use Affinite\DBManager\Services\ColumnService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Columns REST API controller.
 */
final class ColumnsController extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'affinite-db-manager/v1';

	/**
	 * Access service instance.
	 *
	 * @var AccessService
	 */
	private AccessService $access_service;

	/**
	 * Column service instance.
	 *
	 * @var ColumnService
	 */
	private ColumnService $column_service;

	/**
	 * Constructor.
	 *
	 * @param AccessService $access_service Access service instance.
	 */
	public function __construct( AccessService $access_service ) {
		$this->access_service = $access_service;
		$this->column_service = new ColumnService( $access_service );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/columns',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_columns' ),
					'permission_callback' => array( $this, 'get_columns_permissions_check' ),
					'args'                => array(
						'table' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_column' ),
					'permission_callback' => array( $this, 'modify_columns_permissions_check' ),
					'args'                => $this->get_column_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/columns/(?P<column>[a-zA-Z0-9_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_column' ),
					'permission_callback' => array( $this, 'modify_columns_permissions_check' ),
					'args'                => $this->get_column_args( false ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_column' ),
					'permission_callback' => array( $this, 'modify_columns_permissions_check' ),
					'args'                => array(
						'table'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'column' => array(
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
	 * Check if user has permission to read columns.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_columns_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view columns.', 'affinite-db-manager' ),
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
	 * Check if user has permission to modify columns.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function modify_columns_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		$check = $this->get_columns_permissions_check( $request );

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$table_name = $request->get_param( 'table' );

		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot modify columns of a locked table.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get columns for a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function get_columns( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$columns    = $this->column_service->get_columns( $table_name );

		if ( is_wp_error( $columns ) ) {
			return $columns;
		}

		return new WP_REST_Response( $columns, 200 );
	}

	/**
	 * Add a column to a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function add_column( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$params     = $request->get_json_params();

		$column = array(
			'name'           => sanitize_text_field( $params['name'] ?? '' ),
			'type'           => sanitize_text_field( $params['type'] ?? '' ),
			'length'         => isset( $params['length'] ) ? absint( $params['length'] ) : null,
			'nullable'       => isset( $params['nullable'] ) ? (bool) $params['nullable'] : true,
			'default'        => $params['default'] ?? null,
			'auto_increment' => isset( $params['auto_increment'] ) ? (bool) $params['auto_increment'] : false,
			'after'          => isset( $params['after'] ) ? sanitize_text_field( $params['after'] ) : null,
		);

		$result = $this->column_service->add_column( $table_name, $column );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Column added successfully.', 'affinite-db-manager' ),
			),
			201
		);
	}

	/**
	 * Update a column in a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function update_column( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name  = $request->get_param( 'table' );
		$column_name = $request->get_param( 'column' );
		$params      = $request->get_json_params();

		$column = array(
			'name'           => isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : $column_name,
			'type'           => sanitize_text_field( $params['type'] ?? '' ),
			'length'         => isset( $params['length'] ) ? absint( $params['length'] ) : null,
			'nullable'       => isset( $params['nullable'] ) ? (bool) $params['nullable'] : true,
			'default'        => $params['default'] ?? null,
			'auto_increment' => isset( $params['auto_increment'] ) ? (bool) $params['auto_increment'] : false,
		);

		$result = $this->column_service->modify_column( $table_name, $column_name, $column );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Column updated successfully.', 'affinite-db-manager' ),
			),
			200
		);
	}

	/**
	 * Delete a column from a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function delete_column( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name  = $request->get_param( 'table' );
		$column_name = $request->get_param( 'column' );

		$result = $this->column_service->delete_column( $table_name, $column_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Column deleted successfully.', 'affinite-db-manager' ),
			),
			200
		);
	}

	/**
	 * Get column arguments schema.
	 *
	 * @param bool $name_required Whether name is required.
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_column_args( bool $name_required = true ): array {
		return array(
			'table'          => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'name'           => array(
				'required'          => $name_required,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'           => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'length'         => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'nullable'       => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'default'        => array(
				'type' => array( 'string', 'number', 'null' ),
			),
			'auto_increment' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'after'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
