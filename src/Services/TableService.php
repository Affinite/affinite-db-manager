<?php
/**
 * Table service for Affinite DB Manager.
 *
 * Handles table operations like listing, creating, and deleting tables.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Services;

use Affinite\DBManager\Database\Schema;

/**
 * Table service class.
 */
final class TableService {

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
	 * Get all tables with metadata.
	 *
	 * @return array<array{name: string, columns: int, rows: int, is_locked: bool, is_core: bool}>
	 */
	public function get_tables(): array {
		$tables = $this->schema->get_all_tables();
		$result = array();

		foreach ( $tables as $table ) {
			$result[] = array(
				'name'      => $table['name'],
				'columns'   => $table['columns'],
				'rows'      => $table['rows'],
				'is_locked' => $this->access_service->is_table_locked( $table['name'] ),
				'is_core'   => $this->access_service->is_core_table( $table['name'] ),
			);
		}

		return $result;
	}

	/**
	 * Get a single table with full details.
	 *
	 * @param string $table_name Table name.
	 * @return array{name: string, columns: int, rows: int, is_locked: bool, is_core: bool, structure: array}|null
	 */
	public function get_table( string $table_name ): ?array {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return null;
		}

		$structure = $this->schema->describe_table( $table_name );
		$row_count = $this->schema->get_row_count( $table_name );

		return array(
			'name'      => $table_name,
			'columns'   => count( $structure ),
			'rows'      => $row_count,
			'is_locked' => $this->access_service->is_table_locked( $table_name ),
			'is_core'   => $this->access_service->is_core_table( $table_name ),
			'structure' => $structure,
		);
	}

	/**
	 * Create a new table.
	 *
	 * @param string                                                                                                    $table_name Table name.
	 * @param array<array{name: string, type: string, length?: int, nullable?: bool, default?: mixed, auto_increment?: bool, primary?: bool}> $columns Columns definition.
	 * @return bool|\WP_Error Whether the table was created successfully or error.
	 */
	public function create_table( string $table_name, array $columns ): bool|\WP_Error {
		global $wpdb;

		$table_name = sanitize_text_field( $table_name );

		// Validate table name.
		if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table_name ) ) {
			return new \WP_Error(
				'invalid_table_name',
				__( 'Invalid table name. Use only letters, numbers, and underscores.', 'affinite-db-manager' )
			);
		}

		// Add prefix if not present.
		if ( strpos( $table_name, $wpdb->prefix ) !== 0 ) {
			$table_name = $wpdb->prefix . $table_name;
		}

		// Check if table already exists.
		if ( $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_exists',
				__( 'Table already exists.', 'affinite-db-manager' )
			);
		}

		// Validate columns.
		if ( empty( $columns ) ) {
			return new \WP_Error(
				'no_columns',
				__( 'At least one column is required.', 'affinite-db-manager' )
			);
		}

		$result = $this->schema->create_table( $table_name, $columns );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Delete a table.
	 *
	 * @param string $table_name Table name.
	 * @return bool|\WP_Error Whether the table was deleted successfully or error.
	 */
	public function delete_table( string $table_name ): bool|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		// Check if table exists.
		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		// Check if table is locked.
		if ( $this->access_service->is_table_locked( $table_name ) ) {
			return new \WP_Error(
				'table_locked',
				__( 'Cannot delete a locked table.', 'affinite-db-manager' )
			);
		}

		return $this->schema->drop_table( $table_name );
	}

	/**
	 * Lock a table.
	 *
	 * @param string $table_name Table name.
	 * @return bool|\WP_Error Whether the table was locked successfully or error.
	 */
	public function lock_table( string $table_name ): bool|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		return $this->access_service->lock_table( $table_name );
	}

	/**
	 * Unlock a table.
	 *
	 * @param string $table_name Table name.
	 * @return bool|\WP_Error Whether the table was unlocked successfully or error.
	 */
	public function unlock_table( string $table_name ): bool|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		return $this->access_service->unlock_table( $table_name );
	}
}
