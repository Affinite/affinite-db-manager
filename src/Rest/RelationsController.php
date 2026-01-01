<?php
/**
 * Relations REST API controller for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Rest;

use Affinite\DBManager\Services\AccessService;
use Affinite\DBManager\Services\RelationService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Relations REST API controller.
 */
final class RelationsController extends WP_REST_Controller {

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
	 * Relation service instance.
	 *
	 * @var RelationService
	 */
	private RelationService $relation_service;

	/**
	 * Constructor.
	 *
	 * @param AccessService $access_service Access service instance.
	 */
	public function __construct( AccessService $access_service ) {
		$this->access_service   = $access_service;
		$this->relation_service = new RelationService( $access_service );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/relations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_relations' ),
					'permission_callback' => array( $this, 'get_relations_permissions_check' ),
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
					'callback'            => array( $this, 'add_relation' ),
					'permission_callback' => array( $this, 'modify_relations_permissions_check' ),
					'args'                => $this->get_relation_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tables/(?P<table>[a-zA-Z0-9_]+)/relations/(?P<relation>[a-zA-Z0-9_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_relation' ),
					'permission_callback' => array( $this, 'modify_relations_permissions_check' ),
					'args'                => array(
						'table'    => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'relation' => array(
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
	 * Check if user has permission to read relations.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_relations_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->access_service->current_user_can_manage() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view relations.', 'affinite-db-manager' ),
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
	 * Check if user has permission to modify relations.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if allowed, WP_Error otherwise.
	 */
	public function modify_relations_permissions_check( WP_REST_Request $request ): bool|\WP_Error {
		$check = $this->get_relations_permissions_check( $request );

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$table_name = $request->get_param( 'table' );

		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot modify relations of a locked table.', 'affinite-db-manager' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get relations for a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function get_relations( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$relations  = $this->relation_service->get_relations( $table_name );

		if ( is_wp_error( $relations ) ) {
			return $relations;
		}

		return new WP_REST_Response( $relations, 200 );
	}

	/**
	 * Add a relation to a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function add_relation( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name = $request->get_param( 'table' );
		$params     = $request->get_json_params();

		$relation = array(
			'name'              => isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : null,
			'column'            => sanitize_text_field( $params['column'] ?? '' ),
			'referenced_table'  => sanitize_text_field( $params['referenced_table'] ?? '' ),
			'referenced_column' => sanitize_text_field( $params['referenced_column'] ?? '' ),
			'on_delete'         => sanitize_text_field( $params['on_delete'] ?? 'RESTRICT' ),
			'on_update'         => sanitize_text_field( $params['on_update'] ?? 'RESTRICT' ),
		);

		$result = $this->relation_service->add_relation( $table_name, $relation );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Relation added successfully.', 'affinite-db-manager' ),
			),
			201
		);
	}

	/**
	 * Delete a relation from a table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error Response object or error.
	 */
	public function delete_relation( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$table_name    = $request->get_param( 'table' );
		$relation_name = $request->get_param( 'relation' );

		$result = $this->relation_service->delete_relation( $table_name, $relation_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Relation deleted successfully.', 'affinite-db-manager' ),
			),
			200
		);
	}

	/**
	 * Get relation arguments schema.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_relation_args(): array {
		return array(
			'table'             => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'name'              => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'column'            => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'referenced_table'  => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'referenced_column' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'on_delete'         => array(
				'type'              => 'string',
				'enum'              => array( 'CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION', 'SET DEFAULT' ),
				'default'           => 'RESTRICT',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'on_update'         => array(
				'type'              => 'string',
				'enum'              => array( 'CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION', 'SET DEFAULT' ),
				'default'           => 'RESTRICT',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
