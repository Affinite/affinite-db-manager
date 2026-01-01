/**
 * Settings API functions.
 *
 * @package Affinite\DBManager
 */

import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/affinite-db-manager/v1';

/**
 * Get settings.
 *
 * @returns {Promise<Object>} Settings object.
 */
export const getSettings = async () => {
	return apiFetch({ path: `${API_BASE}/settings` });
};

/**
 * Update settings.
 *
 * @param {Object} settings Settings to update.
 * @returns {Promise<Object>} Updated settings.
 */
export const updateSettings = async (settings) => {
	return apiFetch({
		path: `${API_BASE}/settings`,
		method: 'POST',
		data: settings,
	});
};

/**
 * Activate DB Manager.
 *
 * @returns {Promise<Object>} Activation result.
 */
export const activate = async () => {
	return apiFetch({
		path: `${API_BASE}/settings/activate`,
		method: 'POST',
	});
};

/**
 * Deactivate DB Manager.
 *
 * @returns {Promise<Object>} Deactivation result.
 */
export const deactivate = async () => {
	return apiFetch({
		path: `${API_BASE}/settings/deactivate`,
		method: 'POST',
	});
};

/**
 * Add email to whitelist.
 *
 * @param {string} email Email to add.
 * @returns {Promise<Object>} Result with updated emails.
 */
export const addEmail = async (email) => {
	return apiFetch({
		path: `${API_BASE}/settings/emails`,
		method: 'POST',
		data: { email },
	});
};

/**
 * Remove email from whitelist.
 *
 * @param {string} email Email to remove.
 * @returns {Promise<Object>} Result with updated emails.
 */
export const removeEmail = async (email) => {
	return apiFetch({
		path: `${API_BASE}/settings/emails?email=${encodeURIComponent(email)}`,
		method: 'DELETE',
	});
};
