/**
 * Data API functions.
 *
 * @package Affinite\DBManager
 */

import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/affinite-db-manager/v1';

/**
 * Get data preview for a table.
 *
 * @param {string} tableName Table name.
 * @param {number} limit Maximum rows to return.
 * @param {number} offset Offset for pagination.
 * @returns {Promise<Object>} Data preview with columns, rows, and total.
 */
export const getData = async (tableName, limit = 100, offset = 0) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/data?limit=${limit}&offset=${offset}`,
	});
};
