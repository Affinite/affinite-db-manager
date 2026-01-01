/**
 * Settings page component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import StatusToggle from './StatusToggle';
import EmailWhitelist from './EmailWhitelist';
import TableLocks from './TableLocks';

/**
 * Settings page component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.settings Current settings.
 * @param {Function} props.onUpdate Callback when settings are updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Settings page component.
 */
const SettingsPage = memo(({ settings, onUpdate, showNotification }) => {
	if (!settings) {
		return null;
	}

	return (
		<div className="affinite-db-manager__settings">
			<div className="affinite-db-manager__card">
				<div className="affinite-db-manager__card-header">
					<h2>{__('Status', 'affinite-db-manager')}</h2>
				</div>
				<div className="affinite-db-manager__card-body">
					<StatusToggle
						active={settings.active ?? false}
						onUpdate={onUpdate}
						showNotification={showNotification}
					/>
				</div>
			</div>

			<div className="affinite-db-manager__card">
				<div className="affinite-db-manager__card-header">
					<h2>{__('Allowed Users', 'affinite-db-manager')}</h2>
				</div>
				<div className="affinite-db-manager__card-body">
					<EmailWhitelist
						emails={settings.allowed_emails ?? []}
						onUpdate={onUpdate}
						showNotification={showNotification}
					/>
				</div>
			</div>

			<div className="affinite-db-manager__card">
				<div className="affinite-db-manager__card-header">
					<h2>{__('Table Locks', 'affinite-db-manager')}</h2>
				</div>
				<div className="affinite-db-manager__card-body">
					<TableLocks
						active={settings.active ?? false}
						lockedTables={settings.locked_tables ?? []}
						onUpdate={onUpdate}
						showNotification={showNotification}
					/>
				</div>
			</div>
		</div>
	);
});

SettingsPage.displayName = 'SettingsPage';

export default SettingsPage;
