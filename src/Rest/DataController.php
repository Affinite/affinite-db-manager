<?php
/**
 * Data REST API controller for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Rest;

use Affinite\DBManager\Services\AccessService;
use Affinite\DBManager\Database\Schema;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Data REST API controller.
 */
final class DataController extends WP_REST_Controller {

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
	 * Database schema instance.
	 *
	 * @var Schema
	 */
	private Schema $db_schema;

	/**
	 * Constructor.
	 *
	 * @param AccessService $access_service Access service instance.
	 */
	public function __construct( AccessService $access_service ) {
		$this->access_service = $access_service;
		$this->db_schema      = new Schema();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/data',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_data' ),
					'permission_callback' => array( $this, 'get_data_permissions_check' ),
					'args'                => array(
						'table'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'limit'  => array(
							'type'              => 'integer',
							'default'           => 100,
							'minimum'           => 1,
							'maximum'           => 1000,
							'sanitize_callback' => 'absint',
						),
						'offset' => array(
							'type'              => 'integer',
							'default'           => 0,
							'minimum'           => 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if user has permission to read data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_data_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view data.', 'affinite-db-manager' ),
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
	 * Get data preview for a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function get_data( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$limit      = $request->get_param( 'limit' );
		$offset     = $request->get_param( 'offset' );

		$data = $this->db_schema->get_data_preview( $table_name, $limit, $offset );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return new WP_REST_Response( $data, 200 );
	}
}
