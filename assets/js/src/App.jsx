/**
 * Main App component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useCallback, memo } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import SettingsPage from './components/settings/SettingsPage';
import ManagerPage from './components/manager/ManagerPage';
import Notification from './components/common/Notification';
import { useSettings } from './hooks/useSettings';

/**
 * Main App component.
 *
 * @param {Object} props Component props.
 * @param {string} props.page Current page (manager or settings).
 * @returns {JSX.Element} App component.
 */
const App = ({ page }) => {
	const { settings, loading, error, refetch } = useSettings();
	const [notification, setNotification] = useState(null);

	/**
	 * Show notification message.
	 *
	 * @param {string} message Notification message.
	 * @param {string} type Notification type (success, error, warning, info).
	 */
	const showNotification = useCallback((message, type = 'success') => {
		setNotification({ message, type });
	}, []);

	/**
	 * Clear notification.
	 */
	const clearNotification = useCallback(() => {
		setNotification(null);
	}, []);

	if (loading) {
		return (
			<div className="affinite-db-manager">
				<div className="affinite-db-manager__loading">
					<Spinner />
					<span>{__('Loading...', 'affinite-db-manager')}</span>
				</div>
			</div>
		);
	}

	if (error) {
		return (
			<div className="affinite-db-manager">
				<div className="affinite-db-manager__error">
					<p>{__('Error loading data:', 'affinite-db-manager')} {error}</p>
				</div>
			</div>
		);
	}

	return (
		<div className="affinite-db-manager">
			{notification && (
				<Notification
					message={notification.message}
					type={notification.type}
					onDismiss={clearNotification}
				/>
			)}

			{page === 'settings' && (
				<SettingsPage
					settings={settings}
					onUpdate={refetch}
					showNotification={showNotification}
				/>
			)}

			{page === 'manager' && (
				<ManagerPage
					settings={settings}
					showNotification={showNotification}
				/>
			)}
		</div>
	);
};

export default App;
