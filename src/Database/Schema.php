<?php
/**
 * Schema class for Affinite DB Manager.
 *
 * Handles database schema operations like DESCRIBE, CREATE, ALTER, DROP.
 *
 * @package Affinite\DBManager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Affinite\DBManager\Database;

/**
 * Schema class.
 */
final class Schema {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Allowed column types.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_TYPES = array(
		'TINYINT',
		'SMALLINT',
		'MEDIUMINT',
		'INT',
		'BIGINT',
		'DECIMAL',
		'FLOAT',
		'DOUBLE',
		'BIT',
		'CHAR',
		'VARCHAR',
		'BINARY',
		'VARBINARY',
		'TINYBLOB',
		'BLOB',
		'MEDIUMBLOB',
		'LONGBLOB',
		'TINYTEXT',
		'TEXT',
		'MEDIUMTEXT',
		'LONGTEXT',
		'ENUM',
		'SET',
		'DATE',
		'TIME',
		'DATETIME',
		'TIMESTAMP',
		'YEAR',
		'JSON',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		// No initialization needed - using global $wpdb.
	}

	/**
	 * Get all tables in the database.
	 *
	 * @return array<array{name: string, columns: int, rows: int}>
	 */
	public function get_all_tables(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		$result = array();

		foreach ( $tables as $table ) {
			
			//$sql = $wpdb->prepare( 'SHOW COLUMNS FROM %i', $table );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$columns = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i', $table ) );

			$row_count = $this->get_row_count( $table );

			$result[] = array(
				'name'    => $table,
				'columns' => count( $columns ),
				'rows'    => $row_count,
			);
		}

		return $result;
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table_name Table name.
	 * @return bool Whether the table exists.
	 */
	public function table_exists( string $table_name ): bool {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables     = $wpdb->get_col( 'SHOW TABLES' );

		return in_array( $table_name, $tables, true );
	}

	/**
	 * Get row count for a table.
	 *
	 * @param string $table_name Table name.
	 * @return int Row count.
	 */
	public function get_row_count( string $table_name ): int {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		if ( ! $this->table_exists( $table_name ) ) {
			return 0;
		}

		// Whitelist check - only allow existing tables.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$all_tables = $wpdb->get_col( 'SHOW TABLES' );
		if ( ! in_array( $table_name, $all_tables, true ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i',
				$table_name
			)
		);

		return (int) $count;
	}

	/**
	 * Describe a table (get column information).
	 *
	 * @param string $table_name Table name.
	 * @return array<array{name: string, type: string, nullable: bool, default: mixed, extra: string, key: string}>
	 */
	public function describe_table( string $table_name ): array {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		if ( ! $this->table_exists( $table_name ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM %i',
				$table_name
			),
			ARRAY_A
		);
		$result  = array();

		foreach ( $columns as $column ) {
			$result[] = array(
				'name'     => $column['Field'],
				'type'     => $column['Type'],
				'nullable' => 'YES' === $column['Null'],
				'default'  => $column['Default'],
				'extra'    => $column['Extra'],
				'key'      => $column['Key'],
			);
		}

		return $result;
	}

	/**
	 * Check if a column exists in a table.
	 *
	 * @param string $table_name  Table name.
	 * @param string $column_name Column name.
	 * @return bool Whether the column exists.
	 */
	public function column_exists( string $table_name, string $column_name ): bool {
		$table_name  = $this->sanitize_identifier( $table_name );
		$column_name = $this->sanitize_identifier( $column_name );

		$columns = $this->describe_table( $table_name );

		foreach ( $columns as $column ) {
			if ( $column['name'] === $column_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Create a new table.
	 *
	 * @param string $table_name Table name.
	 * @param array<array{name: string, type: string, length?: int, nullable?: bool, default?: mixed, auto_increment?: bool, primary?: bool}> $columns Columns definition.
	 * @return bool|\WP_Error Whether the table was created successfully or error.
	 */
	public function create_table( string $table_name, array $columns ): bool|\WP_Error {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		$column_definitions = array();
		$primary_key        = null;

		foreach ( $columns as $column ) {
			$column_name = $this->sanitize_identifier( $column['name'] );
			$column_type = $this->validate_column_type( $column['type'], $column['length'] ?? null );

			if ( is_wp_error( $column_type ) ) {
				return $column_type;
			}

			$definition = "`{$column_name}` {$column_type}";

			if ( isset( $column['nullable'] ) && ! $column['nullable'] ) {
				$definition .= ' NOT NULL';
			} else {
				$definition .= ' NULL';
			}

			if ( isset( $column['auto_increment'] ) && $column['auto_increment'] ) {
				$definition .= ' AUTO_INCREMENT';
			}

			if ( isset( $column['default'] ) && null !== $column['default'] && ! ( $column['auto_increment'] ?? false ) ) {
				$default     = $this->escape_default_value( $column['default'] );
				$definition .= " DEFAULT {$default}";
			}

			$column_definitions[] = $definition;

			if ( isset( $column['primary'] ) && $column['primary'] ) {
				$primary_key = $column_name;
			}
		}

		// Column definitions are constructed from validated and sanitized inputs.
		// Column names are sanitized via sanitize_identifier(), types are validated
		// via validate_column_type(), and default values are escaped via escape_default_value().
		// Table name and primary key are sanitized identifiers and will be prepared.
		$sql = "CREATE TABLE %i (\n";
		$sql .= implode( ",\n", $column_definitions );

		$prepare_values = array( $table_name );

		if ( null !== $primary_key ) {
			$sql .= ",\nPRIMARY KEY (%i)";
			$prepare_values[] = $primary_key;
		}

		$sql .= "\n) {$wpdb->get_charset_collate()}";

		$result = $wpdb->query( $wpdb->prepare( $sql, ...$prepare_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Column definitions are validated and sanitized; table name and primary key are prepared.

		if ( false === $result ) {
			return new \WP_Error(
				'create_table_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to create table: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Drop a table.
	 *
	 * @param string $table_name Table name.
	 * @return bool|\WP_Error Whether the table was dropped successfully or error.
	 */
	public function drop_table( string $table_name ): bool|\WP_Error {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema cleanup during uninstall.
		$result = $wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %i', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema cleanup during uninstall.
				$table_name
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB call required here.

		if ( false === $result ) {
			return new \WP_Error(
				'drop_table_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to drop table: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Add a column to a table.
	 *
	 * @param string $table_name Table name.
	 * @param array{name: string, type: string, length?: int, nullable?: bool, default?: mixed, auto_increment?: bool, after?: string} $column Column definition.
	 * @return bool|\WP_Error Whether the column was added successfully or error.
	 */
	public function add_column( string $table_name, array $column ): bool|\WP_Error {
		global $wpdb;
		$table_name  = $this->sanitize_identifier( $table_name );
		$column_name = $this->sanitize_identifier( $column['name'] );
		$column_type = $this->validate_column_type( $column['type'], $column['length'] ?? null );

		if ( is_wp_error( $column_type ) ) {
			return $column_type;
		}

		$sql_parts = array( 'ALTER TABLE %i ADD COLUMN %i %s' );
		$prepare_values = array( $table_name, $column_name, $column_type );

		if ( isset( $column['nullable'] ) && ! $column['nullable'] ) {
			$sql_parts[] = 'NOT NULL';
		} else {
			$sql_parts[] = 'NULL';
		}

		if ( isset( $column['auto_increment'] ) && $column['auto_increment'] ) {
			$sql_parts[] = 'AUTO_INCREMENT';
		}

		if ( isset( $column['default'] ) && null !== $column['default'] && ! ( $column['auto_increment'] ?? false ) ) {
			// Default values are escaped via escape_default_value() which validates and sanitizes inputs.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Default value is escaped and validated.
			$default = $this->escape_default_value( $column['default'] );
			$sql_parts[] = "DEFAULT {$default}";
		}

		if ( isset( $column['after'] ) && ! empty( $column['after'] ) ) {
			$after = $this->sanitize_identifier( $column['after'] );
			$sql_parts[] = 'AFTER %i';
			$prepare_values[] = $after;
		}

		$sql = implode( ' ', $sql_parts );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query(
			$wpdb->prepare( $sql, ...$prepare_values )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $result ) {
			return new \WP_Error(
				'add_column_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to add column: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
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
		global $wpdb;
		$table_name      = $this->sanitize_identifier( $table_name );
		$column_name     = $this->sanitize_identifier( $column_name );
		$new_column_name = isset( $column['name'] ) ? $this->sanitize_identifier( $column['name'] ) : $column_name;
		$column_type     = $this->validate_column_type( $column['type'], $column['length'] ?? null );

		if ( is_wp_error( $column_type ) ) {
			return $column_type;
		}

		$sql_parts = array( 'ALTER TABLE %i CHANGE COLUMN %i %i %s' );
		$prepare_values = array( $table_name, $column_name, $new_column_name, $column_type );

		if ( isset( $column['nullable'] ) && ! $column['nullable'] ) {
			$sql_parts[] = 'NOT NULL';
		} else {
			$sql_parts[] = 'NULL';
		}

		if ( isset( $column['auto_increment'] ) && $column['auto_increment'] ) {
			$sql_parts[] = 'AUTO_INCREMENT';
		}

		if ( isset( $column['default'] ) && null !== $column['default'] && ! ( $column['auto_increment'] ?? false ) ) {
			// Default values are escaped via escape_default_value() which validates and sanitizes inputs.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Default value is escaped and validated.
			$default = $this->escape_default_value( $column['default'] );
			$sql_parts[] = "DEFAULT {$default}";
		}

		$sql = implode( ' ', $sql_parts );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( $sql, ...$prepare_values ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new \WP_Error(
				'modify_column_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to modify column: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Drop a column from a table.
	 *
	 * @param string $table_name  Table name.
	 * @param string $column_name Column name.
	 * @return bool|\WP_Error Whether the column was dropped successfully or error.
	 */
	public function drop_column( string $table_name, string $column_name ): bool|\WP_Error {
		global $wpdb;
		$table_name  = $this->sanitize_identifier( $table_name );
		$column_name = $this->sanitize_identifier( $column_name );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE %i DROP COLUMN %i', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema cleanup during uninstall.
				$table_name,
				$column_name
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
	

		if ( false === $result ) {
			return new \WP_Error(
				'drop_column_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to drop column: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Get indexes for a table.
	 *
	 * @param string $table_name Table name.
	 * @return array<array{name: string, type: string, columns: array<string>}>
	 */
	public function get_indexes( string $table_name ): array {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		if ( ! $this->table_exists( $table_name ) ) {
			return array();
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$indexes = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW INDEX FROM %i',
				$table_name
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$result  = array();

		foreach ( $indexes as $index ) {
			$index_name = $index['Key_name'];

			if ( ! isset( $result[ $index_name ] ) ) {
				$type = 'INDEX';
				if ( 'PRIMARY' === $index_name ) {
					$type = 'PRIMARY';
				} elseif ( '0' === $index['Non_unique'] ) {
					$type = 'UNIQUE';
				} elseif ( 'FULLTEXT' === $index['Index_type'] ) {
					$type = 'FULLTEXT';
				} elseif ( 'SPATIAL' === $index['Index_type'] ) {
					$type = 'SPATIAL';
				}

				$result[ $index_name ] = array(
					'name'    => $index_name,
					'type'    => $type,
					'columns' => array(),
				);
			}

			$result[ $index_name ]['columns'][] = $index['Column_name'];
		}

		return array_values( $result );
	}

	/**
	 * Add an index to a table.
	 *
	 * @param string $table_name Table name.
	 * @param array{name: string, type: string, columns: array<string>} $index Index definition.
	 * @return bool|\WP_Error Whether the index was added successfully or error.
	 */
	public function add_index( string $table_name, array $index ): bool|\WP_Error {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );
		$index_name = $this->sanitize_identifier( $index['name'] );
		$index_type = strtoupper( $index['type'] );

		$columns = array_map( array( $this, 'sanitize_identifier' ), $index['columns'] );
		$column_count = count( $columns );
		$column_placeholders = implode( ', ', array_fill( 0, $column_count, '%i' ) );

		$prepare_values = array_merge( array( $table_name ), $columns );

		if ( 'PRIMARY' === $index_type ) {
			$sql = "ALTER TABLE %i ADD PRIMARY KEY ({$column_placeholders})";
		} elseif ( 'UNIQUE' === $index_type ) {
			$sql = "ALTER TABLE %i ADD UNIQUE INDEX %i ({$column_placeholders})";
			$prepare_values = array_merge( array( $table_name, $index_name ), $columns );
		} elseif ( 'FULLTEXT' === $index_type ) {
			$sql = "ALTER TABLE %i ADD FULLTEXT INDEX %i ({$column_placeholders})";
			$prepare_values = array_merge( array( $table_name, $index_name ), $columns );
		} elseif ( 'SPATIAL' === $index_type ) {
			$sql = "ALTER TABLE %i ADD SPATIAL INDEX %i ({$column_placeholders})";
			$prepare_values = array_merge( array( $table_name, $index_name ), $columns );
		} else {
			$sql = "ALTER TABLE %i ADD INDEX %i ({$column_placeholders})";
			$prepare_values = array_merge( array( $table_name, $index_name ), $columns );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, ...$prepare_values ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new \WP_Error(
				'add_index_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to add index: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Drop an index from a table.
	 *
	 * @param string $table_name Table name.
	 * @param string $index_name Index name.
	 * @return bool|\WP_Error Whether the index was dropped successfully or error.
	 */
	public function drop_index( string $table_name, string $index_name ): bool|\WP_Error {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );
		$index_name = $this->sanitize_identifier( $index_name );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema cleanup during uninstall.
		$result = $wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE %i DROP INDEX %i', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema cleanup during uninstall.
				$table_name,
				$index_name
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new \WP_Error(
				'drop_index_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to drop index: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Get foreign keys for a table.
	 *
	 * @param string $table_name Table name.
	 * @return array<array{name: string, column: string, referenced_table: string, referenced_column: string, on_delete: string, on_update: string}>
	 */
	public function get_foreign_keys( string $table_name ): array {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		if ( ! $this->table_exists( $table_name ) ) {
			return array();
		}

		$database = defined( 'DB_NAME' ) ? DB_NAME : '';

		$sql = $wpdb->prepare(
			"SELECT
				CONSTRAINT_NAME,
				COLUMN_NAME,
				REFERENCED_TABLE_NAME,
				REFERENCED_COLUMN_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND REFERENCED_TABLE_NAME IS NOT NULL",
			$database,
			$table_name
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$foreign_keys = $wpdb->get_results( $sql, ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$result       = array();

		foreach ( $foreign_keys as $fk ) {
			// Get ON DELETE and ON UPDATE rules.
			$rule_sql = $wpdb->prepare(
				"SELECT DELETE_RULE, UPDATE_RULE
				FROM information_schema.REFERENTIAL_CONSTRAINTS
				WHERE CONSTRAINT_SCHEMA = %s
					AND CONSTRAINT_NAME = %s
					AND TABLE_NAME = %s",
				$database,
				$fk['CONSTRAINT_NAME'],
				$table_name
			);

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$rules = $wpdb->get_row( $rule_sql, ARRAY_A );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

			$result[] = array(
				'name'              => $fk['CONSTRAINT_NAME'],
				'column'            => $fk['COLUMN_NAME'],
				'referenced_table'  => $fk['REFERENCED_TABLE_NAME'],
				'referenced_column' => $fk['REFERENCED_COLUMN_NAME'],
				'on_delete'         => $rules['DELETE_RULE'] ?? 'RESTRICT',
				'on_update'         => $rules['UPDATE_RULE'] ?? 'RESTRICT',
			);
		}

		return $result;
	}

	/**
	 * Add a foreign key to a table.
	 *
	 * @param string $table_name Table name.
	 * @param array{name?: string, column: string, referenced_table: string, referenced_column: string, on_delete?: string, on_update?: string} $relation Relation definition.
	 * @return bool|\WP_Error Whether the foreign key was added successfully or error.
	 */
	public function add_foreign_key( string $table_name, array $relation ): bool|\WP_Error {
		global $wpdb;
		$table_name        = $this->sanitize_identifier( $table_name );
		$column            = $this->sanitize_identifier( $relation['column'] );
		$referenced_table  = $this->sanitize_identifier( $relation['referenced_table'] );
		$referenced_column = $this->sanitize_identifier( $relation['referenced_column'] );

		$fk_name = isset( $relation['name'] ) && ! empty( $relation['name'] )
			? $this->sanitize_identifier( $relation['name'] )
			: "fk_{$table_name}_{$column}";

		$on_delete = isset( $relation['on_delete'] ) ? strtoupper( $relation['on_delete'] ) : 'RESTRICT';
		$on_update = isset( $relation['on_update'] ) ? strtoupper( $relation['on_update'] ) : 'RESTRICT';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $wpdb->query( 			
			$wpdb->prepare(
				"ALTER TABLE %i ADD CONSTRAINT %i FOREIGN KEY (%i) REFERENCES %i (%i) ON DELETE %s ON UPDATE %s",
				$table_name,
				$fk_name,
				$column,
				$referenced_table,
				$referenced_column,
				$on_delete,
				$on_update
			)
		);
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new \WP_Error(
				'add_foreign_key_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to add foreign key: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Drop a foreign key from a table.
	 *
	 * @param string $table_name Table name.
	 * @param string $fk_name    Foreign key name.
	 * @return bool|\WP_Error Whether the foreign key was dropped successfully or error.
	 */
	public function drop_foreign_key( string $table_name, string $fk_name ): bool|\WP_Error {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );
		$fk_name    = $this->sanitize_identifier( $fk_name );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE %i DROP FOREIGN KEY %i',
				$table_name,
				$fk_name
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new \WP_Error(
				'drop_foreign_key_failed',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Failed to drop foreign key: %s', 'affinite-db-manager' ),
					$wpdb->last_error
				)
			);
		}

		return true;
	}

	/**
	 * Get data preview from a table.
	 *
	 * @param string $table_name Table name.
	 * @param int    $limit      Maximum number of rows to return.
	 * @param int    $offset     Offset for pagination.
	 * @return array{columns: array<string>, rows: array<array<mixed>>, total: int}|\WP_Error
	 */
	public function get_data_preview( string $table_name, int $limit = 100, int $offset = 0 ): array|\WP_Error {
		global $wpdb;
		$table_name = $this->sanitize_identifier( $table_name );

		if ( ! $this->table_exists( $table_name ) ) {
			return new \WP_Error(
				'table_not_found',
				__( 'Table not found.', 'affinite-db-manager' )
			);
		}

		$columns_info = $this->describe_table( $table_name );
		$columns      = array_column( $columns_info, 'name' );

		$total = $this->get_row_count( $table_name );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i LIMIT %d OFFSET %d",
				$table_name,
				$limit,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'columns' => $columns,
			'rows'    => $rows ?? array(),
			'total'   => $total,
		);
	}

	/**
	 * Sanitize a database identifier (table name, column name, etc.).
	 *
	 * @param string $identifier Identifier to sanitize.
	 * @return string Sanitized identifier.
	 */
	private function sanitize_identifier( string $identifier ): string {
		// Remove backticks and other potentially dangerous characters.
		$identifier = preg_replace( '/[^a-zA-Z0-9_]/', '', $identifier );

		return $identifier ?? '';
	}

	/**
	 * Validate and format column type.
	 *
	 * @param string   $type   Column type.
	 * @param int|null $length Column length.
	 * @return string|\WP_Error Formatted column type or error.
	 */
	private function validate_column_type( string $type, ?int $length ): string|\WP_Error {
		$type       = strtoupper( $type );
		$base_type  = preg_replace( '/\(.*\)/', '', $type );
		$base_type  = trim( $base_type ?? '' );

		if ( ! in_array( $base_type, self::ALLOWED_TYPES, true ) ) {
			return new \WP_Error(
				'invalid_column_type',
				sprintf(
					/* translators: %s: Column type */
					__( 'Invalid column type: %s', 'affinite-db-manager' ),
					$type
				)
			);
		}

		// Types that require length.
		$length_required = array( 'CHAR', 'VARCHAR', 'BINARY', 'VARBINARY' );

		if ( in_array( $base_type, $length_required, true ) ) {
			if ( null === $length || $length <= 0 ) {
				$length = ( 'CHAR' === $base_type || 'BINARY' === $base_type ) ? 1 : 255;
			}

			return "{$base_type}({$length})";
		}

		// Types that can have optional length.
		$length_optional = array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT' );

		if ( in_array( $base_type, $length_optional, true ) && null !== $length && $length > 0 ) {
			return "{$base_type}({$length})";
		}

		// DECIMAL/FLOAT/DOUBLE with precision.
		if ( in_array( $base_type, array( 'DECIMAL', 'FLOAT', 'DOUBLE' ), true ) ) {
			if ( null !== $length && $length > 0 ) {
				return "{$base_type}({$length},2)";
			}

			return $base_type;
		}

		return $base_type;
	}

	/**
	 * Escape default value for SQL.
	 *
	 * @param mixed $value Default value.
	 * @return string Escaped value.
	 */
	private function escape_default_value( mixed $value ): string {
		if ( null === $value ) {
			return 'NULL';
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		// Check for SQL functions - whitelist approach.
		$sql_functions = array( 'CURRENT_TIMESTAMP', 'NOW()', 'NULL' );
		$value_upper   = strtoupper( (string) $value );
		if ( in_array( $value_upper, $sql_functions, true ) ) {
			return $value_upper;
		}

		// Escape string values properly.
		return "'" . esc_sql( (string) $value ) . "'";
	}
}
