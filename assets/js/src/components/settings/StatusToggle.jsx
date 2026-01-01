/**
 * Status toggle component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { activate, deactivate } from '../../api/settings';

/**
 * Status toggle component.
 *
 * @param {Object} props Component props.
 * @param {boolean} props.active Current active state.
 * @param {Function} props.onUpdate Callback when status is updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Status toggle component.
 */
const StatusToggle = ({ active, onUpdate, showNotification }) => {
	const [isUpdating, setIsUpdating] = useState(false);
	const [localActive, setLocalActive] = useState(active);

	// Sync local state with props when they change externally
	useEffect(() => {
		setLocalActive(active);
	}, [active]);

	/**
	 * Handle status change with optimistic update.
	 *
	 * @param {boolean} newStatus New status value.
	 */
	const handleChange = useCallback(async (newStatus) => {
		if (localActive === newStatus || isUpdating) {
			return;
		}

		// Optimistic update
		const previousActive = localActive;
		setLocalActive(newStatus);
		setIsUpdating(true);

		try {
			const apiCall = newStatus ? activate() : deactivate();
			await apiCall;
			
			showNotification(
				newStatus
					? __('DB Manager activated.', 'affinite-db-manager')
					: __('DB Manager deactivated.', 'affinite-db-manager'),
				'success'
			);
			
			// Only refetch if callback is provided
			if (onUpdate) {
				onUpdate();
			}
		} catch (error) {
			// Rollback on error
			setLocalActive(previousActive);
			showNotification(
				error.message || __('Failed to update status.', 'affinite-db-manager'),
				'error'
			);
		} finally {
			setIsUpdating(false);
		}
	}, [localActive, isUpdating, onUpdate, showNotification]);

	return (
		<div className="affinite-db-manager__status">
			<ToggleControl
				label={__('DB Manager Status', 'affinite-db-manager')}
				checked={localActive}
				onChange={handleChange}
				disabled={isUpdating}
				help={
					localActive
						? __('DB Manager is active and accessible.', 'affinite-db-manager')
						: __('DB Manager is inactive and not accessible.', 'affinite-db-manager')
				}
			/>
			{isUpdating && (
				<span className="affinite-db-manager__updating-indicator">
					{__('Updating...', 'affinite-db-manager')}
				</span>
			)}
		</div>
	);
};

export default StatusToggle;
