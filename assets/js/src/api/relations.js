/**
 * Relations API functions.
 *
 * @package Affinite\DBManager
 */

import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/affinite-db-manager/v1';

/**
 * Get relations for a table.
 *
 * @param {string} tableName Table name.
 * @returns {Promise<Array>} List of relations.
 */
export const getRelations = async (tableName) => {
	return apiFetch({ path: `${API_BASE}/tables/${tableName}/relations` });
};

/**
 * Add a relation to a table.
 *
 * @param {string} tableName Table name.
 * @param {Object} relation Relation definition.
 * @returns {Promise<Object>} Addition result.
 */
export const addRelation = async (tableName, relation) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/relations`,
		method: 'POST',
		data: relation,
	});
};

/**
 * Delete a relation from a table.
 *
 * @param {string} tableName Table name.
 * @param {string} relationName Relation name.
 * @returns {Promise<Object>} Deletion result.
 */
export const deleteRelation = async (tableName, relationName) => {
	return apiFetch({
		path: `${API_BASE}/tables/${tableName}/relations/${relationName}`,
		method: 'DELETE',
	});
};
