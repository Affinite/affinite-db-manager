<?php
/**
 * Indexes REST API controller for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Rest;

use Affinite\DBManager\Services\AccessService;
use Affinite\DBManager\Services\IndexService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Indexes REST API controller.
 */
final class IndexesController extends WP_REST_Controller {

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
	 * Index service instance.
	 *
	 * @var IndexService
	 */
	private IndexService $index_service;

	/**
	 * Constructor.
	 *
	 * @param AccessService $access_service Access service instance.
	 */
	public function __construct( AccessService $access_service ) {
		$this->access_service = $access_service;
		$this->index_service  = new IndexService( $access_service );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/indexes',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_indexes' ),
					'permission_callback' => array( $this, 'get_indexes_permissions_check' ),
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
					'callback'            => array( $this, 'add_index' ),
					'permission_callback' => array( $this, 'modify_indexes_permissions_check' ),
					'args'                => $this->get_index_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/indexes/(?P<index>[a-zA-Z0-9_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_index' ),
					'permission_callback' => array( $this, 'modify_indexes_permissions_check' ),
					'args'                => array(
						'table' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'index' => array(
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
	 * Check if user has permission to read indexes.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_indexes_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view indexes.', 'affinite-db-manager' ),
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
	 * Check if user has permission to modify indexes.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function modify_indexes_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		$check = $this->get_indexes_permissions_check( $request );

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$table_name = $request->get_param( 'table' );

		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot modify indexes of a locked table.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get indexes for a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function get_indexes( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$indexes    = $this->index_service->get_indexes( $table_name );

		if ( is_wp_error( $indexes ) ) {
			return $indexes;
		}

		return new WP_REST_Response( $indexes, 200 );
	}

	/**
	 * Add an index to a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function add_index( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$params     = $request->get_json_params();

		$index = array(
			'name'    => sanitize_text_field( $params['name'] ?? '' ),
			'type'    => sanitize_text_field( $params['type'] ?? 'INDEX' ),
			'columns' => array_map( 'sanitize_text_field', $params['columns'] ?? array() ),
		);

		$result = $this->index_service->add_index( $table_name, $index );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Index added successfully.', 'affinite-db-manager' ),
			),
			201
		);
	}

	/**
	 * Delete an index from a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function delete_index( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$index_name = $request->get_param( 'index' );

		$result = $this->index_service->delete_index( $table_name, $index_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Index deleted successfully.', 'affinite-db-manager' ),
			),
			200
		);
	}

	/**
	 * Get index arguments schema.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_index_args(): array {
		return array(
			'table'   => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'name'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'    => array(
				'type'              => 'string',
				'enum'              => array( 'INDEX', 'UNIQUE', 'FULLTEXT', 'SPATIAL' ),
				'default'           => 'INDEX',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'columns' => array(
				'required' => true,
				'type'     => 'array',
				'items'    => array(
					'type' => 'string',
				),
			),
		);
	}
}
