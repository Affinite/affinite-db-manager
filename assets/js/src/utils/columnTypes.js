/**
 * Column types utility for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

/**
 * Available column types for select controls.
 */
export const COLUMN_TYPES = [
	{ label: 'TINYINT', value: 'TINYINT' },
	{ label: 'SMALLINT', value: 'SMALLINT' },
	{ label: 'MEDIUMINT', value: 'MEDIUMINT' },
	{ label: 'INT', value: 'INT' },
	{ label: 'BIGINT', value: 'BIGINT' },
	{ label: 'DECIMAL', value: 'DECIMAL' },
	{ label: 'FLOAT', value: 'FLOAT' },
	{ label: 'DOUBLE', value: 'DOUBLE' },
	{ label: 'CHAR', value: 'CHAR' },
	{ label: 'VARCHAR', value: 'VARCHAR' },
	{ label: 'TINYTEXT', value: 'TINYTEXT' },
	{ label: 'TEXT', value: 'TEXT' },
	{ label: 'MEDIUMTEXT', value: 'MEDIUMTEXT' },
	{ label: 'LONGTEXT', value: 'LONGTEXT' },
	{ label: 'TINYBLOB', value: 'TINYBLOB' },
	{ label: 'BLOB', value: 'BLOB' },
	{ label: 'MEDIUMBLOB', value: 'MEDIUMBLOB' },
	{ label: 'LONGBLOB', value: 'LONGBLOB' },
	{ label: 'DATE', value: 'DATE' },
	{ label: 'TIME', value: 'TIME' },
	{ label: 'DATETIME', value: 'DATETIME' },
	{ label: 'TIMESTAMP', value: 'TIMESTAMP' },
	{ label: 'YEAR', value: 'YEAR' },
	{ label: 'JSON', value: 'JSON' },
];

/**
 * Types that require length specification.
 */
export const LENGTH_REQUIRED_TYPES = ['CHAR', 'VARCHAR', 'BINARY', 'VARBINARY'];

/**
 * Types that support optional length.
 */
export const LENGTH_OPTIONAL_TYPES = ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT', 'DECIMAL', 'FLOAT', 'DOUBLE'];

/**
 * Check if type requires length.
 *
 * @param {string} type Column type.
 * @returns {boolean} Whether length is required.
 */
export const requiresLength = (type) => {
	return LENGTH_REQUIRED_TYPES.includes(type.toUpperCase());
};

/**
 * Check if type supports length.
 *
 * @param {string} type Column type.
 * @returns {boolean} Whether length is supported.
 */
export const supportsLength = (type) => {
	return LENGTH_REQUIRED_TYPES.includes(type.toUpperCase()) ||
		LENGTH_OPTIONAL_TYPES.includes(type.toUpperCase());
};
