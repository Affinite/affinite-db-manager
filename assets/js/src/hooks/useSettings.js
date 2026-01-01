/**
 * Settings hook for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { getSettings, updateSettings } from '../api/settings';

/**
 * Hook for managing settings state with optimistic updates.
 *
 * @returns {Object} Settings state and actions.
 */
export const useSettings = () => {
	const [settings, setSettings] = useState(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const isInitialMount = useRef(true);

	/**
	 * Fetch settings from API.
	 */
	const fetchSettings = useCallback(async () => {
		try {
			setLoading(true);
			setError(null);
			const data = await getSettings();
			setSettings(data);
		} catch (err) {
			setError(err.message || 'Failed to load settings');
		} finally {
			setLoading(false);
		}
	}, []);

	/**
	 * Optimistically update settings locally.
	 *
	 * @param {Object} updates Partial settings to update.
	 * @returns {Promise<Object>} Updated settings.
	 */
	const updateSettingsOptimistic = useCallback(async (updates) => {
		if (!settings) {
			throw new Error('Settings not loaded');
		}

		// Optimistic update
		const newSettings = { ...settings, ...updates };
		setSettings(newSettings);

		try {
			// Update on server
			const result = await updateSettings(updates);
			// Use server response to ensure consistency
			if (result?.settings) {
				setSettings(result.settings);
			}
			return result?.settings || newSettings;
		} catch (err) {
			// Rollback on error
			setSettings(settings);
			throw err;
		}
	}, [settings]);

	/**
	 * Refetch settings silently (without loading spinner).
	 */
	const refetch = useCallback(async () => {
		try {
			const data = await getSettings();
			setSettings(data);
		} catch (err) {
			// Silent fail - don't show error on background refresh
			console.error('Failed to refresh settings:', err);
		}
	}, []);

	// Initial fetch.
	useEffect(() => {
		if (isInitialMount.current) {
			isInitialMount.current = false;
			fetchSettings();
		}
	}, [fetchSettings]);

	return {
		settings,
		loading,
		error,
		refetch,
		updateSettingsOptimistic,
	};
};
