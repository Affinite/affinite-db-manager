/**
 * Tables API functions.
 *
 * @package Affinite\DBManager
 */

import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/affinite-db-manager/v1';

/**
 * Get all tables.
 *
 * @returns {Promise<Array>} List of tables.
 */
export const getTables = async () => {
	return apiFetch({ path: `${API_BASE}/tables` });
};

/**
 * Get a single table.
 *
 * @param {string} name Table name.
 * @returns {Promise<Object>} Table details.
 */
export const getTable = async (name) => {
	return apiFetch({ path: `${API_BASE}/tables/${name}` });
};

/**
 * Create a new table.
 *
 * @param {string} name Table name.
 * @param {Array} columns Columns definition.
 * @returns {Promise<Object>} Creation result.
 */
export const createTable = async (name, columns) => {
	return apiFetch({
		path: `${API_BASE}/tables`,
		method: 'POST',
		data: { name, columns },
	});
};

/**
 * Delete a table.
 *
 * @param {string} name Table name.
 * @returns {Promise<Object>} Deletion result.
 */
export const deleteTable = async (name) => {
	return apiFetch({
		path: `${API_BASE}/tables/${name}`,
		method: 'DELETE',
	});
};

/**
 * Lock a table.
 *
 * @param {string} name Table name.
 * @returns {Promise<Object>} Lock result.
 */
export const lockTable = async (name) => {
	return apiFetch({
		path: `${API_BASE}/tables/${name}/lock`,
		method: 'POST',
	});
};

/**
 * Unlock a table.
 *
 * @param {string} name Table name.
 * @returns {Promise<Object>} Unlock result.
 */
export const unlockTable = async (name) => {
	return apiFetch({
		path: `${API_BASE}/tables/${name}/unlock`,
		method: 'POST',
	});
};
