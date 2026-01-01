<?php
/**
 * Relation service for Affinite DB Manager.
 *
 * Handles foreign key operations like adding and deleting relations.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Services;

use Affinite\DBManager\Database\Schema;

/**
 * Relation service class.
 */
final class RelationService {

	/**
	 * Schema instance.
	 *
	 * @var Schema
	 */
	private Schema $schema;

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
		$this->schema         = new Schema();
	}

	/**
	 * Get foreign keys for a table.
	 *
	 * @param string $table_name Table name.
	 * @return array<array{name: string, column: string, referenced_table: string, referenced_column: string, on_delete: string, on_update: string}>|\WP_Error
	 */
	public function get_relations( string $table_name ): array|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		return $this->schema->get_foreign_keys( $table_name );
	}

	/**
	 * Add a foreign key to a table.
	 *
	 * @param string $table_name Table name.
	 * @param array{name?: string, column: string, referenced_table: string, referenced_column: string, on_delete?: string, on_update?: string} $relation Relation definition.
	 * @return bool|\WP_Error Whether the relation was added successfully or error.
	 */
	public function add_relation( string $table_name, array $relation ): bool|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot modify a locked table.', 'affinite-db-manager' )
			);
		}

		// Validate column.
		if ( empty( $relation['column'] ) ) {
			return new \WP_Error(
				'invalid_column',
				__( 'Column is required.', 'affinite-db-manager' )
			);
		}

		// Check if column exists.
		if ( ! $this->schema->column_exists( $table_name, $relation['column'] ) ) {
			return new \WP_Error(
				'column_not_found',
				__( 'Column not found in the table.', 'affinite-db-manager' )
			);
		}

		// Validate referenced table.
		if ( empty( $relation['referenced_table'] ) ) {
			return new \WP_Error(
				'invalid_referenced_table',
				__( 'Referenced table is required.', 'affinite-db-manager' )
			);
		}

		if ( ! $this->schema->table_exists( $relation['referenced_table'] ) ) {
			return new \WP_Error(
				'referenced_table_not_found',
				__( 'Referenced table not found.', 'affinite-db-manager' )
			);
		}

		// Validate referenced column.
		if ( empty( $relation['referenced_column'] ) ) {
			return new \WP_Error(
				'invalid_referenced_column',
				__( 'Referenced column is required.', 'affinite-db-manager' )
			);
		}

		if ( ! $this->schema->column_exists( $relation['referenced_table'], $relation['referenced_column'] ) ) {
			return new \WP_Error(
				'referenced_column_not_found',
				__( 'Referenced column not found in the referenced table.', 'affinite-db-manager' )
			);
		}

		// Validate on_delete and on_update.
		$valid_actions = array( 'CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION', 'SET DEFAULT' );

		if ( ! empty( $relation['on_delete'] ) && ! in_array( strtoupper( $relation['on_delete'] ), $valid_actions, true ) ) {
			return new \WP_Error(
				'invalid_on_delete',
				__( 'Invalid ON DELETE action.', 'affinite-db-manager' )
			);
		}

		if ( ! empty( $relation['on_update'] ) && ! in_array( strtoupper( $relation['on_update'] ), $valid_actions, true ) ) {
			return new \WP_Error(
				'invalid_on_update',
				__( 'Invalid ON UPDATE action.', 'affinite-db-manager' )
			);
		}

		return $this->schema->add_foreign_key( $table_name, $relation );
	}

	/**
	 * Delete a foreign key from a table.
	 *
	 * @param string $table_name    Table name.
	 * @param string $relation_name Foreign key name.
	 * @return bool|\WP_Error Whether the relation was deleted successfully or error.
	 */
	public function delete_relation( string $table_name, string $relation_name ): bool|\WP_Error {
		$table_name    = sanitize_text_field( $table_name );
		$relation_name = sanitize_text_field( $relation_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot modify a locked table.', 'affinite-db-manager' )
			);
		}

		return $this->schema->drop_foreign_key( $table_name, $relation_name );
	}
}
