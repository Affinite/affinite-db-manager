/**
 * Relation editor component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addRelation, deleteRelation } from '../../api/relations';
import { getTables } from '../../api/tables';
import { getColumns } from '../../api/columns';
import Modal from '../common/Modal';
import ConfirmDialog from '../common/ConfirmDialog';
import DeleteIcon from '../common/DeleteIcon';

const ON_DELETE_OPTIONS = [
	{ label: 'RESTRICT', value: 'RESTRICT' },
	{ label: 'CASCADE', value: 'CASCADE' },
	{ label: 'SET NULL', value: 'SET NULL' },
	{ label: 'NO ACTION', value: 'NO ACTION' },
];

const ON_UPDATE_OPTIONS = [...ON_DELETE_OPTIONS];

/**
 * Relation editor component.
 *
 * @param {Object} props Component props.
 * @param {string} props.tableName Table name.
 * @param {Array} props.relations List of relations.
 * @param {Array} props.columns List of columns for selection.
 * @param {boolean} props.isLocked Whether table is locked.
 * @param {Function} props.onUpdate Callback when relations are updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Relation editor component.
 */
const RelationEditor = ({ tableName, relations, columns, isLocked, onUpdate, showNotification }) => {
	const [showAddModal, setShowAddModal] = useState(false);
	const [confirmDelete, setConfirmDelete] = useState(null);
	const [loading, setLoading] = useState(false);
	const [tables, setTables] = useState([]);
	const [refColumns, setRefColumns] = useState([]);

	// New relation form state.
	const [newRelation, setNewRelation] = useState({
		column: '',
		referenced_table: '',
		referenced_column: '',
		on_delete: 'RESTRICT',
		on_update: 'RESTRICT',
	});

	// Fetch tables on mount.
	useEffect(() => {
		const fetchTables = async () => {
			try {
				const data = await getTables();
				setTables(data);
			} catch (error) {
				// Silent fail.
			}
		};
		fetchTables();
	}, []);

	// Fetch referenced table columns when referenced table changes.
	useEffect(() => {
		const fetchRefColumns = async () => {
			if (!newRelation.referenced_table) {
				setRefColumns([]);
				return;
			}

			try {
				const data = await getColumns(newRelation.referenced_table);
				setRefColumns(data);
			} catch (error) {
				setRefColumns([]);
			}
		};
		fetchRefColumns();
	}, [newRelation.referenced_table]);

	/**
	 * Reset new relation form.
	 */
	const resetNewRelation = () => {
		setNewRelation({
			column: '',
			referenced_table: '',
			referenced_column: '',
			on_delete: 'RESTRICT',
			on_update: 'RESTRICT',
		});
	};

	/**
	 * Handle adding a new relation.
	 */
	const handleAdd = async () => {
		if (!newRelation.column) {
			showNotification(__('Column is required.', 'affinite-db-manager'), 'error');
			return;
		}

		if (!newRelation.referenced_table) {
			showNotification(__('Referenced table is required.', 'affinite-db-manager'), 'error');
			return;
		}

		if (!newRelation.referenced_column) {
			showNotification(__('Referenced column is required.', 'affinite-db-manager'), 'error');
			return;
		}

		setLoading(true);
		try {
			await addRelation(tableName, newRelation);
			showNotification(__('Relation added successfully.', 'affinite-db-manager'), 'success');
			setShowAddModal(false);
			resetNewRelation();
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to add relation.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Handle deleting a relation.
	 */
	const handleDelete = async () => {
		if (!confirmDelete) {
			return;
		}

		setLoading(true);
		try {
			await deleteRelation(tableName, confirmDelete);
			showNotification(__('Relation deleted successfully.', 'affinite-db-manager'), 'success');
			setConfirmDelete(null);
			onUpdate();
		} catch (error) {
			showNotification(error.message || __('Failed to delete relation.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	const columnOptions = [
		{ label: __('Select column...', 'affinite-db-manager'), value: '' },
		...columns.map((c) => ({ label: c.name, value: c.name })),
	];

	const tableOptions = [
		{ label: __('Select table...', 'affinite-db-manager'), value: '' },
		...tables.map((t) => ({ label: t.name, value: t.name })),
	];

	const refColumnOptions = [
		{ label: __('Select column...', 'affinite-db-manager'), value: '' },
		...refColumns.map((c) => ({ label: c.name, value: c.name })),
	];

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
		<div className="affinite-db-manager__relation-editor">
			{!isLocked && (
				<div style={{ marginBottom: '15px' }}>
					<Button variant="primary" onClick={() => setShowAddModal(true)}>
						{__('+ Add Relation', 'affinite-db-manager')}
					</Button>
				</div>
			)}

			<table className="affinite-db-manager__data-table">
				<thead>
					<tr>
						<th>{__('Column', 'affinite-db-manager')}</th>
						<th>{__('References', 'affinite-db-manager')}</th>
						<th>{__('ON DELETE', 'affinite-db-manager')}</th>
						<th>{__('ON UPDATE', 'affinite-db-manager')}</th>
						{!isLocked && <th>{__('Actions', 'affinite-db-manager')}</th>}
					</tr>
				</thead>
				<tbody>
					{relations.map((relation) => (
						<tr key={relation.name}>
							<td><strong>{relation.column}</strong></td>
							<td>{relation.referenced_table}.{relation.referenced_column}</td>
							<td>{relation.on_delete}</td>
							<td>{relation.on_update}</td>
							{!isLocked && (
								<td>
									<Button
									variant="secondary"
									onClick={() => setConfirmDelete(relation.name)}
									isSmall
									isDestructive
								>
									<DeleteIcon />
								</Button>
								</td>
							)}
						</tr>
					))}
					{relations.length === 0 && (
						<tr>
							<td colSpan={isLocked ? 4 : 5} style={{ textAlign: 'center' }}>
								{__('No relations found.', 'affinite-db-manager')}
							</td>
						</tr>
					)}
				</tbody>
			</table>

			{showAddModal && (
				<Modal
					title={__('Add Relation (Foreign Key)', 'affinite-db-manager')}
					onClose={() => setShowAddModal(false)}
					footer={addModalFooter}
				>
					<div className="affinite-db-manager__form-group">
						<SelectControl
							label={__('Column', 'affinite-db-manager')}
							value={newRelation.column}
							options={columnOptions}
							onChange={(value) => setNewRelation({ ...newRelation, column: value })}
						/>
					</div>
					<div className="affinite-db-manager__form-group">
						<SelectControl
							label={__('Referenced Table', 'affinite-db-manager')}
							value={newRelation.referenced_table}
							options={tableOptions}
							onChange={(value) => setNewRelation({ ...newRelation, referenced_table: value, referenced_column: '' })}
						/>
					</div>
					<div className="affinite-db-manager__form-group">
						<SelectControl
							label={__('Referenced Column', 'affinite-db-manager')}
							value={newRelation.referenced_column}
							options={refColumnOptions}
							onChange={(value) => setNewRelation({ ...newRelation, referenced_column: value })}
							disabled={!newRelation.referenced_table}
						/>
					</div>
					<div className="affinite-db-manager__form-group">
						<SelectControl
							label={__('ON DELETE', 'affinite-db-manager')}
							value={newRelation.on_delete}
							options={ON_DELETE_OPTIONS}
							onChange={(value) => setNewRelation({ ...newRelation, on_delete: value })}
						/>
					</div>
					<div className="affinite-db-manager__form-group">
						<SelectControl
							label={__('ON UPDATE', 'affinite-db-manager')}
							value={newRelation.on_update}
							options={ON_UPDATE_OPTIONS}
							onChange={(value) => setNewRelation({ ...newRelation, on_update: value })}
						/>
					</div>
				</Modal>
			)}

			{confirmDelete && (
				<ConfirmDialog
					title={__('Delete Relation', 'affinite-db-manager')}
					message={__(`Are you sure you want to delete this foreign key?`, 'affinite-db-manager')}
					confirmLabel={__('Delete', 'affinite-db-manager')}
					isDangerous
					onConfirm={handleDelete}
					onCancel={() => setConfirmDelete(null)}
				/>
			)}
		</div>
	);
};

export default RelationEditor;
