/**
 * Manager page component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useCallback, memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useAccess } from '../../hooks/useAccess';
import TableList from './TableList';
import TableDetail from './TableDetail';
import WarningIcon from '../common/WarningIcon';

/**
 * Manager page component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.settings Current settings.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Manager page component.
 */
const ManagerPage = memo(({ settings, showNotification }) => {
	const { isActive, hasAccess } = useAccess(settings);
	const [selectedTable, setSelectedTable] = useState(null);

	const handleBack = useCallback(() => {
		setSelectedTable(null);
	}, []);

	const handleSelectTable = useCallback((tableName) => {
		setSelectedTable(tableName);
	}, []);

	// Show no access message if not active or no access.
	if (!isActive || !hasAccess) {
		const noEmailsConfigured = !settings?.allowed_emails || settings.allowed_emails.length === 0;
		
		return (
			<div className="affinite-db-manager__no-access">
				<div className="affinite-db-manager__no-access-icon">
					<WarningIcon size={48} />
				</div>
				{noEmailsConfigured ? (
					<>
						<p>{__('No users are allowed to view tables yet.', 'affinite-db-manager')}</p>
						<p>{__('Please add at least one email address in the Settings tab to enable table access.', 'affinite-db-manager')}</p>
					</>
				) : (
					<>
						<p>{__('DB Manager is not active or you do not have permission.', 'affinite-db-manager')}</p>
						<p>{__('Contact administrator.', 'affinite-db-manager')}</p>
					</>
				)}
			</div>
		);
	}

	// Show table detail if a table is selected.
	if (selectedTable) {
		return (
			<TableDetail
				tableName={selectedTable}
				onBack={handleBack}
				showNotification={showNotification}
			/>
		);
	}

	// Show table list.
	return (
		<TableList
			onSelectTable={handleSelectTable}
			showNotification={showNotification}
		/>
	);
});

export default ManagerPage;
