/**
 * Index manager component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState } from '@wordpress/element';
import { Button, TextControl, SelectControl, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addIndex, deleteIndex } from '../../api/indexes';
import Modal from '../common/Modal';
import ConfirmDialog from '../common/ConfirmDialog';
import DeleteIcon from '../common/DeleteIcon';

const INDEX_TYPES = [
	{ label: 'INDEX', value: 'INDEX' },
	{ label: 'UNIQUE', value: 'UNIQUE' },
	{ label: 'FULLTEXT', value: 'FULLTEXT' },
	{ label: 'SPATIAL', value: 'SPATIAL' },
];

/**
 * Index manager component.
 *
 * @param {Object} props Component props.
 * @param {string} props.tableName Table name.
 * @param {Array} props.indexes List of indexes.
 * @param {Array} props.columns List of columns for selection.
 * @param {boolean} props.isLocked Whether table is locked.
 * @param {Function} props.onUpdate Callback when indexes are updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Index manager component.
 */
const IndexManager = ({ tableName, indexes, columns, isLocked, onUpdate, showNotification }) => {
	const [showAddModal, setShowAddModal] = useState(false);
	const [confirmDelete, setConfirmDelete] = useState(null);
	const [loading, setLoading] = useState(false);

	// New index form state.
	const [newIndex, setNewIndex] = useState({
		name: '',
		type: 'INDEX',
		columns: [],
	});

	/**
	 * Reset new index form.
	 */
	const resetNewIndex = () => {
		setNewIndex({
			name: '',
			type: 'INDEX',
			columns: [],
		});
	};

	/**
	 * Handle adding a new index.
	 */
	const handleAdd = async () => {
		if (!newIndex.name) {
			showNotification(__('Index name is required.', 'affinite-db-manager'), 'error');
			return;
		}

		if (newIndex.columns.length === 0) {
			showNotification(__('Select at least one column.', 'affinite-db-manager'), 'error');
			return;
		}

		setLoading(true);
		try {
			await addIndex(tableName, newIndex);
			showNotification(__('Index added successfully.', 'affinite-db-manager'), 'success');
			setShowAddModal(false);
			resetNewIndex();
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to add index.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Handle deleting an index.
	 */
	const handleDelete = async () => {
		if (!confirmDelete) {
			return;
		}

		setLoading(true);
		try {
			await deleteIndex(tableName, confirmDelete);
			showNotification(__('Index deleted successfully.', 'affinite-db-manager'), 'success');
			setConfirmDelete(null);
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to delete index.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Toggle column selection.
	 *
	 * @param {string} columnName Column name.
	 */
	const toggleColumn = (columnName) => {
		const currentColumns = newIndex.columns;
		if (currentColumns.includes(columnName)) {
			setNewIndex({
				...newIndex,
				columns: currentColumns.filter((c) => c !== columnName),
			});
		} else {
			setNewIndex({
				...newIndex,
				columns: [...currentColumns, columnName],
			});
		}
	};

	const addModalFooter = (
		<>
			<Button variant="secondary" onClick={() => setShowAddModal(false)} disabled={loading}>
				{__('Cancel', 'affinite-db-manager')}
			</Button>
			<Button variant="primary" onClick={handleAdd} isBusy={loading} disabled={loading}>
				{__('Add', 'affinite-db-manager')}
			</Button>
		</>
	);

	return (
		<div className="affinite-db-manager__index-manager">
			{!isLocked && (
				<div style={{ marginBottom: '15px' }}>
					<Button variant="primary" onClick={() => setShowAddModal(true)}>
						{__('+ Add Index', 'affinite-db-manager')}
					</Button>
				</div>
			)}

			<table className="affinite-db-manager__data-table">
				<thead>
					<tr>
						<th>{__('Name', 'affinite-db-manager')}</th>
						<th>{__('Type', 'affinite-db-manager')}</th>
						<th>{__('Columns', 'affinite-db-manager')}</th>
						{!isLocked && <th>{__('Actions', 'affinite-db-manager')}</th>}
					</tr>
				</thead>
				<tbody>
					{indexes.map((index) => (
						<tr key={index.name}>
							<td><strong>{index.name}</strong></td>
							<td>{index.type}</td>
							<td>{index.columns.join(', ')}</td>
							{!isLocked && (
								<td>
									{index.name !== 'PRIMARY' && (
										<Button
											variant="secondary"
											onClick={() => setConfirmDelete(index.name)}
											isSmall
											isDestructive
										>
											<DeleteIcon />
										</Button>
									)}
								</td>
							)}
						</tr>
					))}
					{indexes.length === 0 && (
						<tr>
							<td colSpan={isLocked ? 3 : 4} style={{ textAlign: 'center' }}>
								{__('No indexes found.', 'affinite-db-manager')}
							</td>
						</tr>
					)}
				</tbody>
			</table>

			{showAddModal && (
				<Modal
					title={__('Add Index', 'affinite-db-manager')}
					onClose={() => setShowAddModal(false)}
					footer={addModalFooter}
				>
					<div className="affinite-db-manager__form-group">
						<TextControl
							label={__('Index Name', 'affinite-db-manager')}
							value={newIndex.name}
							onChange={(value) => setNewIndex({ ...newIndex, name: value })}
						/>
					</div>
					<div className="affinite-db-manager__form-group">
						<SelectControl
							label={__('Type', 'affinite-db-manager')}
							value={newIndex.type}
							options={INDEX_TYPES}
							onChange={(value) => setNewIndex({ ...newIndex, type: value })}
						/>
					</div>
					<div className="affinite-db-manager__form-group">
						<label>{__('Columns', 'affinite-db-manager')}</label>
						<div style={{ marginTop: '10px' }}>
							{columns.map((column) => (
								<div key={column.name} style={{ marginBottom: '5px' }}>
									<CheckboxControl
										label={column.name}
										checked={newIndex.columns.includes(column.name)}
										onChange={() => toggleColumn(column.name)}
									/>
								</div>
							))}
						</div>
					</div>
				</Modal>
			)}

			{confirmDelete && (
				<ConfirmDialog
					title={__('Delete Index', 'affinite-db-manager')}
					message={__(`Are you sure you want to delete index "${confirmDelete}"?`, 'affinite-db-manager')}
					confirmLabel={__('Delete', 'affinite-db-manager')}
					isDangerous
					onConfirm={handleDelete}
					onCancel={() => setConfirmDelete(null)}
				/>
			)}
		</div>
	);
};

export default IndexManager;
