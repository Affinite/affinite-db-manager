/**
 * Table locks component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getTables, lockTable, unlockTable } from '../../api/tables';
import SearchInput from '../common/SearchInput';
import ConfirmDialog from '../common/ConfirmDialog';
import LockIcon from '../common/LockIcon';

/**
 * Table locks component.
 *
 * @param {Object} props Component props.
 * @param {boolean} props.active Whether DB Manager is active.
 * @param {Array} props.lockedTables List of locked table names.
 * @param {Function} props.onUpdate Callback when locks are updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Table locks component.
 */
const TableLocks = ({ active, lockedTables, onUpdate, showNotification }) => {
	const [tables, setTables] = useState([]);
	const [loading, setLoading] = useState(false);
	const [localLockedTables, setLocalLockedTables] = useState(new Set(lockedTables));
	const [updatingTables, setUpdatingTables] = useState(new Set());
	const [search, setSearch] = useState('');
	const [confirmUnlock, setConfirmUnlock] = useState(null);

	// Sync local state with props
	useEffect(() => {
		setLocalLockedTables(new Set(lockedTables));
	}, [lockedTables]);

	// Load tables when active, clear when inactive
	useEffect(() => {
		let cancelled = false;

		if (!active) {
			// Clear tables when inactive
			setTables([]);
			setLoading(false);
			return;
		}

		// Load tables when active
		const fetchTables = async () => {
			try {
				setLoading(true);
				const data = await getTables();
				if (!cancelled) {
					setTables(data);
				}
			} catch (error) {
				if (!cancelled) {
					showNotification(error.message || __('Failed to load tables.', 'affinite-db-manager'), 'error');
				}
			} finally {
				if (!cancelled) {
					setLoading(false);
				}
			}
		};

		fetchTables();

		return () => {
			cancelled = true;
		};
	}, [active, showNotification]);

	// Filter tables based on search.
	const filteredTables = useMemo(() => {
		if (!search) {
			return tables;
		}

		const searchLower = search.toLowerCase();
		return tables.filter((table) =>
			table.name.toLowerCase().includes(searchLower)
		);
	}, [tables, search]);

	/**
	 * Handle locking a table with optimistic update.
	 *
	 * @param {string} tableName Table name.
	 */
	const handleLock = useCallback(async (tableName) => {
		if (updatingTables.has(tableName)) {
			return;
		}

		// Optimistic update
		setLocalLockedTables(prev => new Set(prev).add(tableName));
		setUpdatingTables(prev => new Set(prev).add(tableName));

		try {
			await lockTable(tableName);
			showNotification(__('Table locked successfully.', 'affinite-db-manager'), 'success');
			
			// Silent background refresh
			if (onUpdate) {
				onUpdate();
			}
		} catch (error) {
			// Rollback on error
			setLocalLockedTables(prev => {
				const next = new Set(prev);
				next.delete(tableName);
				return next;
			});
			showNotification(error.message || __('Failed to lock table.', 'affinite-db-manager'), 'error');
		} finally {
			setUpdatingTables(prev => {
				const next = new Set(prev);
				next.delete(tableName);
				return next;
			});
		}
	}, [updatingTables, onUpdate, showNotification]);

	/**
	 * Handle unlocking a table.
	 *
	 * @param {Object} table Table object.
	 */
	const handleUnlock = useCallback((table) => {
		// If it's a core table, show confirmation dialog.
		if (table.is_core) {
			setConfirmUnlock(table);
			return;
		}

		performUnlock(table.name);
	}, []);

	/**
	 * Perform the actual unlock with optimistic update.
	 *
	 * @param {string} tableName Table name.
	 */
	const performUnlock = useCallback(async (tableName) => {
		if (updatingTables.has(tableName)) {
			return;
		}

		// Optimistic update
		setLocalLockedTables(prev => {
			const next = new Set(prev);
			next.delete(tableName);
			return next;
		});
		setUpdatingTables(prev => new Set(prev).add(tableName));

		try {
			await unlockTable(tableName);
			showNotification(__('Table unlocked successfully.', 'affinite-db-manager'), 'success');
			setConfirmUnlock(null);
			
			// Silent background refresh
			if (onUpdate) {
				onUpdate();
			}
		} catch (error) {
			// Rollback on error
			setLocalLockedTables(prev => new Set(prev).add(tableName));
			showNotification(error.message || __('Failed to unlock table.', 'affinite-db-manager'), 'error');
		} finally {
			setUpdatingTables(prev => {
				const next = new Set(prev);
				next.delete(tableName);
				return next;
			});
		}
	}, [updatingTables, onUpdate, showNotification]);

	/**
	 * Check if table is locked.
	 *
	 * @param {string} tableName Table name.
	 * @returns {boolean} Whether table is locked.
	 */
	const isLocked = useCallback((tableName) => {
		return localLockedTables.has(tableName);
	}, [localLockedTables]);

	// Show message when inactive
	if (!active) {
		return (
			<div className="affinite-db-manager__table-locks-inactive">
				<p>{__('Activate DB Manager to view and manage table locks.', 'affinite-db-manager')}</p>
			</div>
		);
	}

	if (loading) {
		return (
			<div className="affinite-db-manager__loading">
				<Spinner />
				<span>{__('Loading tables...', 'affinite-db-manager')}</span>
			</div>
		);
	}

	return (
		<div className="affinite-db-manager__table-locks">
			<div className="affinite-db-manager__table-search">
				<SearchInput
					value={search}
					onChange={setSearch}
					placeholder={__('Search tables...', 'affinite-db-manager')}
				/>
			</div>

			<div className="affinite-db-manager__table-list">
				{filteredTables.map((table) => {
					const locked = isLocked(table.name);
					const isUpdating = updatingTables.has(table.name);
					return (
						<div
							key={table.name}
							className={`affinite-db-manager__table-item ${
								locked ? 'affinite-db-manager__table-item--locked' : ''
							} ${isUpdating ? 'affinite-db-manager__table-item--updating' : ''}`}
						>
							<div className="affinite-db-manager__table-name">
								<LockIcon locked={locked} />
								<span>{table.name}</span>
								{table.is_core && (
									<span className="affinite-db-manager__core-badge" title={__('WordPress Core Table', 'affinite-db-manager')}>
										core
									</span>
								)}
							</div>
							<Button
								variant="secondary"
								onClick={() => locked ? handleUnlock(table) : handleLock(table.name)}
								disabled={isUpdating}
								isSmall
							>
								{isUpdating ? '...' : (locked ? __('Unlock', 'affinite-db-manager') : __('Lock', 'affinite-db-manager'))}
							</Button>
						</div>
					);
				})}

				{filteredTables.length === 0 && !loading && (
					<div className="affinite-db-manager__no-tables">
						{__('No tables found.', 'affinite-db-manager')}
					</div>
				)}
			</div>

			{confirmUnlock && (
				<ConfirmDialog
					title={__('Warning', 'affinite-db-manager')}
					message={__(
						`Table ${confirmUnlock.name} is a WordPress core table. Modifications may damage your website.`,
						'affinite-db-manager'
					)}
					confirmText={confirmUnlock.name}
					confirmLabel={__('Unlock', 'affinite-db-manager')}
					isDangerous
					onConfirm={() => performUnlock(confirmUnlock.name)}
					onCancel={() => setConfirmUnlock(null)}
				/>
			)}
		</div>
	);
};

export default TableLocks;
