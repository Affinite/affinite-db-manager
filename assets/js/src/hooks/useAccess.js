/**
 * Access hook for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useMemo } from '@wordpress/element';

/**
 * Hook for checking access permissions.
 *
 * @param {Object} settings Current settings.
 * @returns {Object} Access state.
 */
export const useAccess = (settings) => {
	const isActive = useMemo(() => {
		return settings?.active ?? false;
	}, [settings]);

	const hasAccess = useMemo(() => {
		if (!settings) {
			return false;
		}

		// If no emails are whitelisted, nobody can see tables.
		if (!settings.allowed_emails || settings.allowed_emails.length === 0) {
			return false;
		}

		// Check if current user's email is in the whitelist.
		// This is handled server-side, so we assume access if we got settings.
		return true;
	}, [settings]);

	const canManage = useMemo(() => {
		// If we can see settings, we can manage.
		return settings !== null;
	}, [settings]);

	return {
		isActive,
		hasAccess,
		canManage,
	};
};
