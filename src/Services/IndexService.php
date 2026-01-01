<?php
/**
 * Index service for Affinite DB Manager.
 *
 * Handles index operations like adding and deleting indexes.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Services;

use Affinite\DBManager\Database\Schema;

/**
 * Index service class.
 */
final class IndexService {

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
	 * Get indexes for a table.
	 *
	 * @param string $table_name Table name.
	 * @return array<array{name: string, type: string, columns: array<string>}>|\WP_Error
	 */
	public function get_indexes( string $table_name ): array|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		return $this->schema->get_indexes( $table_name );
	}

	/**
	 * Add an index to a table.
	 *
	 * @param string        $table_name Table name.
	 * @param array{name: string, type: string, columns: array<string>} $index Index definition.
	 * @return bool|\WP_Error Whether the index was added successfully or error.
	 */
	public function add_index( string $table_name, array $index ): bool|\WP_Error {
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

		// Validate index name.
		if ( empty( $index['name'] ) || ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $index['name'] ) ) {
			return new \WP_Error(
				'invalid_index_name',
				__( 'Invalid index name. Use only letters, numbers, and underscores.', 'affinite-db-manager' )
			);
		}

		// Validate index type.
		$valid_types = array( 'INDEX', 'UNIQUE', 'FULLTEXT', 'SPATIAL' );
		if ( empty( $index['type'] ) || ! in_array( strtoupper( $index['type'] ), $valid_types, true ) ) {
			return new \WP_Error(
				'invalid_index_type',
				__( 'Invalid index type. Must be INDEX, UNIQUE, FULLTEXT, or SPATIAL.', 'affinite-db-manager' )
			);
		}

		// Validate columns.
		if ( empty( $index['columns'] ) || ! is_array( $index['columns'] ) ) {
			return new \WP_Error(
				'invalid_index_columns',
				__( 'At least one column is required for the index.', 'affinite-db-manager' )
			);
		}

		return $this->schema->add_index( $table_name, $index );
	}

	/**
	 * Delete an index from a table.
	 *
	 * @param string $table_name Table name.
	 * @param string $index_name Index name.
	 * @return bool|\WP_Error Whether the index was deleted successfully or error.
	 */
	public function delete_index( string $table_name, string $index_name ): bool|\WP_Error {
		$table_name = sanitize_text_field( $table_name );
		$index_name = sanitize_text_field( $index_name );

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

		// Cannot delete PRIMARY key through this method.
		if ( 'PRIMARY' === strtoupper( $index_name ) ) {
			return new \WP_Error(
				'cannot_delete_primary',
				__( 'Cannot delete PRIMARY key. Modify the column instead.', 'affinite-db-manager' )
			);
		}

		return $this->schema->drop_index( $table_name, $index_name );
	}
}
