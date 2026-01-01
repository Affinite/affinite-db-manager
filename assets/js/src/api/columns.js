/**
 * Columns API functions.
 *
 * @package Affinite\DBManager
 */

import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/affinite-db-manager/v1';

/**
 * Get columns for a table.
 *
 * @param {string} tableName Table name.
 * @returns {Promise<Array>} List of columns.
 */
export const getColumns = async (tableName) => {
	return apiFetch({ path: `${API_BASE}/tables/${tableName}/columns` });
};

/**
 * Add a column to a table.
 *
 * @param {string} tableName Table name.
 * @param {Object} column Column definition.
 * @returns {Promise<Object>} Addition result.
 */
export const addColumn = async (tableName, column) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/columns`,
		method: 'POST',
		data: column,
	});
};

/**
 * Update a column in a table.
 *
 * @param {string} tableName Table name.
 * @param {string} columnName Current column name.
 * @param {Object} column New column definition.
 * @returns {Promise<Object>} Update result.
 */
export const updateColumn = async (tableName, columnName, column) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/columns/${columnName}`,
		method: 'PUT',
		data: column,
	});
};

/**
 * Delete a column from a table.
 *
 * @param {string} tableName Table name.
 * @param {string} columnName Column name.
 * @returns {Promise<Object>} Deletion result.
 */
export const deleteColumn = async (tableName, columnName) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/columns/${columnName}`,
		method: 'DELETE',
	});
};
