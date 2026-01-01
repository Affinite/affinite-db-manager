/**
 * Column editor component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState } from '@wordpress/element';
import { Button, TextControl, SelectControl, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addColumn, updateColumn, deleteColumn } from '../../api/columns';
import Modal from '../common/Modal';
import ConfirmDialog from '../common/ConfirmDialog';
import { COLUMN_TYPES } from '../../utils/columnTypes';
import EditIcon from '../common/EditIcon';
import DeleteIcon from '../common/DeleteIcon';

/**
 * Column editor component.
 *
 * @param {Object} props Component props.
 * @param {string} props.tableName Table name.
 * @param {Array} props.columns List of columns.
 * @param {boolean} props.isLocked Whether table is locked.
 * @param {Function} props.onUpdate Callback when columns are updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Column editor component.
 */
const ColumnEditor = ({ tableName, columns, isLocked, onUpdate, showNotification }) => {
	const [showAddModal, setShowAddModal] = useState(false);
	const [editColumn, setEditColumn] = useState(null);
	const [confirmDelete, setConfirmDelete] = useState(null);
	const [loading, setLoading] = useState(false);

	// New column form state.
	const [newColumn, setNewColumn] = useState({
		name: '',
		type: 'VARCHAR',
		length: 255,
		nullable: true,
		default: '',
		auto_increment: false,
	});

	/**
	 * Reset new column form.
	 */
	const resetNewColumn = () => {
		setNewColumn({
			name: '',
			type: 'VARCHAR',
			length: 255,
			nullable: true,
			default: '',
			auto_increment: false,
		});
	};

	/**
	 * Handle adding a new column.
	 */
	const handleAdd = async () => {
		if (!newColumn.name) {
			showNotification(__('Column name is required.', 'affinite-db-manager'), 'error');
			return;
		}

		setLoading(true);
		try {
			await addColumn(tableName, newColumn);
			showNotification(__('Column added successfully.', 'affinite-db-manager'), 'success');
			setShowAddModal(false);
			resetNewColumn();
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to add column.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Handle updating a column.
	 */
	const handleUpdate = async () => {
		if (!editColumn) {
			return;
		}

		setLoading(true);
		try {
			await updateColumn(tableName, editColumn.originalName, {
				name: editColumn.name,
				type: editColumn.type,
				length: editColumn.length,
				nullable: editColumn.nullable,
				default: editColumn.default,
				auto_increment: editColumn.auto_increment,
			});
			showNotification(__('Column updated successfully.', 'affinite-db-manager'), 'success');
			setEditColumn(null);
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to update column.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Handle deleting a column.
	 */
	const handleDelete = async () => {
		if (!confirmDelete) {
			return;
		}

		setLoading(true);
		try {
			await deleteColumn(tableName, confirmDelete);
			showNotification(__('Column deleted successfully.', 'affinite-db-manager'), 'success');
			setConfirmDelete(null);
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to delete column.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Parse column type and length from type string.
	 *
	 * @param {string} typeString Type string like "VARCHAR(255)".
	 * @returns {Object} Parsed type and length.
	 */
	const parseColumnType = (typeString) => {
		const match = typeString.match(/^(\w+)(?:\((\d+)(?:,\d+)?\))?/i);
		if (match) {
			return {
				type: match[1].toUpperCase(),
				length: match[2] ? parseInt(match[2]) : null,
			};
		}
		return { type: typeString.toUpperCase(), length: null };
	};

	/**
	 * Open edit modal for a column.
	 *
	 * @param {Object} column Column to edit.
	 */
	const openEditModal = (column) => {
		const { type, length } = parseColumnType(column.type);
		setEditColumn({
			originalName: column.name,
			name: column.name,
			type,
			length,
			nullable: column.nullable,
			default: column.default || '',
			auto_increment: column.extra.toLowerCase().includes('auto_increment'),
		});
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

	const editModalFooter = (
		<>
			<Button variant="secondary" onClick={() => setEditColumn(null)} disabled={loading}>
				{__('Cancel', 'affinite-db-manager')}
			</Button>
			<Button variant="primary" onClick={handleUpdate} isBusy={loading} disabled={loading}>
				{__('Save', 'affinite-db-manager')}
			</Button>
		</>
	);

	return (
		<div className="affinite-db-manager__column-editor">
			{!isLocked && (
				<div style={{ marginBottom: '15px' }}>
					<Button variant="primary" onClick={() => setShowAddModal(true)}>
						{__('+ Add Column', 'affinite-db-manager')}
					</Button>
				</div>
			)}

			<table className="affinite-db-manager__data-table">
				<thead>
					<tr>
						<th>{__('Name', 'affinite-db-manager')}</th>
						<th>{__('Type', 'affinite-db-manager')}</th>
						<th>{__('Null', 'affinite-db-manager')}</th>
						<th>{__('Default', 'affinite-db-manager')}</th>
						<th>{__('Extra', 'affinite-db-manager')}</th>
						{!isLocked && <th>{__('Actions', 'affinite-db-manager')}</th>}
					</tr>
				</thead>
				<tbody>
					{columns.map((column) => (
						<tr key={column.name}>
							<td>
								<strong>{column.name}</strong>
								{column.key === 'PRI' && <span title="Primary Key"> 🔑</span>}
							</td>
							<td>{column.type}</td>
							<td>{column.nullable ? 'YES' : 'NO'}</td>
							<td>{column.default ?? '-'}</td>
							<td>{column.extra || '-'}</td>
							{!isLocked && (
								<td>
									<div className="affinite-db-manager__actions">
										<Button
											variant="secondary"
											onClick={() => openEditModal(column)}
											isSmall
										>
											<EditIcon />
										</Button>
										<Button
											variant="secondary"
											onClick={() => setConfirmDelete(column.name)}
											isSmall
											isDestructive
										>
											<DeleteIcon />
										</Button>
									</div>
								</td>
							)}
						</tr>
					))}
				</tbody>
			</table>

			{showAddModal && (
				<Modal
					title={__('Add Column', 'affinite-db-manager')}
					onClose={() => setShowAddModal(false)}
					footer={addModalFooter}
				>
					<ColumnForm
						column={newColumn}
						onChange={setNewColumn}
					/>
				</Modal>
			)}

			{editColumn && (
				<Modal
					title={__('Edit Column', 'affinite-db-manager')}
					onClose={() => setEditColumn(null)}
					footer={editModalFooter}
				>
					<ColumnForm
						column={editColumn}
						onChange={setEditColumn}
					/>
				</Modal>
			)}

			{confirmDelete && (
				<ConfirmDialog
					title={__('Delete Column', 'affinite-db-manager')}
					message={__(`Are you sure you want to delete column "${confirmDelete}"?`, 'affinite-db-manager')}
					confirmLabel={__('Delete', 'affinite-db-manager')}
					isDangerous
					onConfirm={handleDelete}
					onCancel={() => setConfirmDelete(null)}
				/>
			)}
		</div>
	);
};

/**
 * Column form component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.column Column data.
 * @param {Function} props.onChange Callback when column changes.
 * @returns {JSX.Element} Column form component.
 */
const ColumnForm = ({ column, onChange }) => {
	const updateField = (field, value) => {
		onChange({ ...column, [field]: value });
	};

	return (
		<>
			<div className="affinite-db-manager__form-group">
				<TextControl
					label={__('Name', 'affinite-db-manager')}
					value={column.name}
					onChange={(value) => updateField('name', value)}
				/>
			</div>
			<div className="affinite-db-manager__form-group">
				<SelectControl
					label={__('Type', 'affinite-db-manager')}
					value={column.type}
					options={COLUMN_TYPES}
					onChange={(value) => updateField('type', value)}
				/>
			</div>
			<div className="affinite-db-manager__form-group">
				<TextControl
					label={__('Length', 'affinite-db-manager')}
					type="number"
					value={column.length || ''}
					onChange={(value) => updateField('length', parseInt(value) || null)}
				/>
			</div>
			<div className="affinite-db-manager__form-group affinite-db-manager__form-group--inline">
				<CheckboxControl
					label={__('Nullable', 'affinite-db-manager')}
					checked={column.nullable}
					onChange={(value) => updateField('nullable', value)}
				/>
			</div>
			<div className="affinite-db-manager__form-group">
				<TextControl
					label={__('Default', 'affinite-db-manager')}
					value={column.default || ''}
					onChange={(value) => updateField('default', value)}
				/>
			</div>
			<div className="affinite-db-manager__form-group affinite-db-manager__form-group--inline">
				<CheckboxControl
					label={__('Auto Increment', 'affinite-db-manager')}
					checked={column.auto_increment}
					onChange={(value) => updateField('auto_increment', value)}
				/>
			</div>
		</>
	);
};

export default ColumnEditor;
