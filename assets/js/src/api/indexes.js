/**
 * Indexes API functions.
 *
 * @package Affinite\DBManager
 */

import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/affinite-db-manager/v1';

/**
 * Get indexes for a table.
 *
 * @param {string} tableName Table name.
 * @returns {Promise<Array>} List of indexes.
 */
export const getIndexes = async (tableName) => {
	return apiFetch({ path: `${API_BASE}/tables/${tableName}/indexes` });
};

/**
 * Add an index to a table.
 *
 * @param {string} tableName Table name.
 * @param {Object} index Index definition.
 * @returns {Promise<Object>} Addition result.
 */
export const addIndex = async (tableName, index) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/indexes`,
		method: 'POST',
		data: index,
	});
};

/**
 * Delete an index from a table.
 *
 * @param {string} tableName Table name.
 * @param {string} indexName Index name.
 * @returns {Promise<Object>} Deletion result.
 */
export const deleteIndex = async (tableName, indexName) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/indexes/${indexName}`,
		method: 'DELETE',
	});
};
