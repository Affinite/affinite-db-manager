/**
 * Generic API hook for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useCallback } from '@wordpress/element';

/**
 * Hook for making API calls with loading and error state.
 *
 * @returns {Object} API call function and state.
 */
export const useApi = () => {
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState(null);

	/**
	 * Make an API call.
	 *
	 * @param {Function} apiFunction API function to call.
	 * @param {...*} args Arguments to pass to the function.
	 * @returns {Promise<*>} API response.
	 */
	const call = useCallback(async (apiFunction, ...args) => {
		try {
			setLoading(true);
			setError(null);
			const result = await apiFunction(...args);
			return result;
		} catch (err) {
			const errorMessage = err.message || 'An error occurred';
			setError(errorMessage);
			throw err;
		} finally {
			setLoading(false);
		}
	}, []);

	/**
	 * Clear error state.
	 */
	const clearError = useCallback(() => {
		setError(null);
	}, []);

	return {
		call,
		loading,
		error,
		clearError,
	};
};
