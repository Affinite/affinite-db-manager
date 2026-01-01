/**
 * Table list component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useMemo, useCallback, memo } from '@wordpress/element';
import { Button, Spinner, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useTables } from '../../hooks/useTables';
import { deleteTable } from '../../api/tables';
import SearchInput from '../common/SearchInput';
import ConfirmDialog from '../common/ConfirmDialog';
import CreateTableModal from './CreateTableModal';
import LockIcon from '../common/LockIcon';
import ViewIcon from '../common/ViewIcon';
import EditIcon from '../common/EditIcon';
import DeleteIcon from '../common/DeleteIcon';

/**
 * Table list component.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onSelectTable Callback when a table is selected.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Table list component.
 */
const TableList = memo(({ onSelectTable, showNotification }) => {
	const { tables, loading, error, refetch } = useTables();
	const [search, setSearch] = useState('');
	const [hideLocked, setHideLocked] = useState(false);
	const [actionLoading, setActionLoading] = useState(false);
	const [confirmDelete, setConfirmDelete] = useState(null);
	const [showCreateModal, setShowCreateModal] = useState(false);

	// Filter tables based on search and locked status.
	const filteredTables = useMemo(() => {
		let filtered = tables;

		// Filter by locked status
		if (hideLocked) {
			filtered = filtered.filter((table) => !table.is_locked);
		}

		// Filter by search
		if (search) {
			const searchLower = search.toLowerCase();
			filtered = filtered.filter((table) =>
				table.name.toLowerCase().includes(searchLower)
			);
		}

		return filtered;
	}, [tables, search, hideLocked]);

	/**
	 * Handle viewing a table.
	 *
	 * @param {string} tableName Table name.
	 */
	const handleView = (tableName) => {
		onSelectTable(tableName);
	};

	/**
	 * Handle deleting a table.
	 *
	 * @param {Object} table Table object.
	 */
	const handleDelete = (table) => {
		setConfirmDelete(table);
	};

	/**
	 * Perform the actual delete.
	 */
	const performDelete = async () => {
		if (!confirmDelete) {
			return;
		}

		setActionLoading(true);
		try {
			await deleteTable(confirmDelete.name);
			showNotification(__('Table deleted successfully.', 'affinite-db-manager'), 'success');
			setConfirmDelete(null);
			refetch();
		} catch (error) {
			showNotification(error.message || __('Failed to delete table.', 'affinite-db-manager'), 'error');
		} finally {
			setActionLoading(false);
		}
	};

	/**
	 * Handle table creation success.
	 */
	const handleCreateSuccess = () => {
		setShowCreateModal(false);
		refetch();
	};

	if (loading) {
		return (
			<div className="affinite-db-manager__loading">
				<Spinner />
				<span>{__('Loading tables...', 'affinite-db-manager')}</span>
			</div>
		);
	}

	if (error) {
		return (
			<div className="affinite-db-manager__error">
				<p>{error}</p>
				<Button variant="primary" onClick={refetch}>
					{__('Retry', 'affinite-db-manager')}
				</Button>
			</div>
		);
	}

	return (
		<div className="affinite-db-manager__table-list-page">
			<div className="affinite-db-manager__toolbar">
				<Button
					variant="primary"
					onClick={() => setShowCreateModal(true)}
				>
					{__('+ New Table', 'affinite-db-manager')}
				</Button>
				<div className="affinite-db-manager__toolbar-filters">
					<SearchInput
						value={search}
						onChange={setSearch}
						placeholder={__('Search tables...', 'affinite-db-manager')}
					/>
					<CheckboxControl
						label={__('Hide locked tables', 'affinite-db-manager')}
						checked={hideLocked}
						onChange={setHideLocked}
					/>
				</div>
			</div>

			<div className="affinite-db-manager__card">
				<table className="affinite-db-manager__data-table">
					<thead>
						<tr>
							<th>{__('Table', 'affinite-db-manager')}</th>
							<th>{__('Columns', 'affinite-db-manager')}</th>
							<th>{__('Rows', 'affinite-db-manager')}</th>
							<th>{__('Actions', 'affinite-db-manager')}</th>
						</tr>
					</thead>
					<tbody>
						{filteredTables.map((table) => (
							<tr key={table.name}>
								<td>
									<span className="affinite-db-manager__table-name">
										<LockIcon locked={table.is_locked} />
										{table.name}
									</span>
								</td>
								<td>{table.columns}</td>
								<td>{table.rows.toLocaleString()}</td>
								<td>
									<div className="affinite-db-manager__actions">
										<Button
											variant="secondary"
											onClick={() => handleView(table.name)}
											isSmall
											title={__('View', 'affinite-db-manager')}
										>
											<ViewIcon />
										</Button>
										{!table.is_locked && (
											<>
												<Button
													variant="secondary"
													onClick={() => handleView(table.name)}
													isSmall
													title={__('Edit', 'affinite-db-manager')}
												>
													<EditIcon />
												</Button>
												<Button
													variant="secondary"
													onClick={() => handleDelete(table)}
													isSmall
													isDestructive
													title={__('Delete', 'affinite-db-manager')}
												>
													<DeleteIcon />
												</Button>
											</>
										)}
									</div>
								</td>
							</tr>
						))}
						{filteredTables.length === 0 && (
							<tr>
								<td colSpan="4" style={{ textAlign: 'center' }}>
									{__('No tables found.', 'affinite-db-manager')}
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</div>

			{confirmDelete && (
				<ConfirmDialog
					title={__('Delete Table', 'affinite-db-manager')}
					message={__(`Are you sure you want to delete table "${confirmDelete.name}"? This action cannot be undone.`, 'affinite-db-manager')}
					confirmText={confirmDelete.name}
					confirmLabel={__('Delete', 'affinite-db-manager')}
					isDangerous
					onConfirm={performDelete}
					onCancel={() => setConfirmDelete(null)}
				/>
			)}

			{showCreateModal && (
				<CreateTableModal
					onClose={() => setShowCreateModal(false)}
					onSuccess={handleCreateSuccess}
					showNotification={showNotification}
				/>
			)}
		</div>
	);
});

export default TableList;
