<?php
/**
 * Column service for Affinite DB Manager.
 *
 * Handles column operations like adding, modifying, and deleting columns.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Services;

use Affinite\DBManager\Database\Schema;

/**
 * Column service class.
 */
final class ColumnService {

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
	 * Get columns for a table.
	 *
	 * @param string $table_name Table name.
	 * @return array<array{name: string, type: string, nullable: bool, default: mixed, extra: string}>|\WP_Error
	 */
	public function get_columns( string $table_name ): array|\WP_Error {
		$table_name = sanitize_text_field( $table_name );

		if ( ! $this->schema->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		return $this->schema->describe_table( $table_name );
	}

	/**
	 * Add a column to a table.
	 *
	 * @param string $table_name  Table name.
	 * @param array{name: string, type: string, length?: int, nullable?: bool, default?: mixed, auto_increment?: bool, after?: string} $column Column definition.
	 * @return bool|\WP_Error Whether the column was added successfully or error.
	 */
	public function add_column( string $table_name, array $column ): bool|\WP_Error {
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

		// Validate column name.
		if ( empty( $column['name'] ) || ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column['name'] ) ) {
			return new \WP_Error(
				'invalid_column_name',
				__( 'Invalid column name. Use only letters, numbers, and underscores.', 'affinite-db-manager' )
			);
		}

		// Validate column type.
		if ( empty( $column['type'] ) ) {
			return new \WP_Error(
				'invalid_column_type',
				__( 'Column type is required.', 'affinite-db-manager' )
			);
		}

		return $this->schema->add_column( $table_name, $column );
	}

	/**
	 * Modify a column in a table.
	 *
	 * @param string $table_name  Table name.
	 * @param string $column_name Current column name.
	 * @param array{name?: string, type: string, length?: int, nullable?: bool, default?: mixed, auto_increment?: bool} $column New column definition.
	 * @return bool|\WP_Error Whether the column was modified successfully or error.
	 */
	public function modify_column( string $table_name, string $column_name, array $column ): bool|\WP_Error {
		$table_name  = sanitize_text_field( $table_name );
		$column_name = sanitize_text_field( $column_name );

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

		// Check if column exists.
		if ( ! $this->schema->column_exists( $table_name, $column_name ) ) {
			return new \WP_Error(
				'column_not_found',
				__( 'Column not found.', 'affinite-db-manager' )
			);
		}

		// Validate new column name if changing.
		if ( ! empty( $column['name'] ) && ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column['name'] ) ) {
			return new \WP_Error(
				'invalid_column_name',
				__( 'Invalid column name. Use only letters, numbers, and underscores.', 'affinite-db-manager' )
			);
		}

		return $this->schema->modify_column( $table_name, $column_name, $column );
	}

	/**
	 * Delete a column from a table.
	 *
	 * @param string $table_name  Table name.
	 * @param string $column_name Column name.
	 * @return bool|\WP_Error Whether the column was deleted successfully or error.
	 */
	public function delete_column( string $table_name, string $column_name ): bool|\WP_Error {
		$table_name  = sanitize_text_field( $table_name );
		$column_name = sanitize_text_field( $column_name );

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

		if ( ! $this->schema->column_exists( $table_name, $column_name ) ) {
			return new \WP_Error(
				'column_not_found',
				__( 'Column not found.', 'affinite-db-manager' )
			);
		}

		return $this->schema->drop_column( $table_name, $column_name );
	}
}
