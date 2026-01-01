/**
 * Create table modal component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState } from '@wordpress/element';
import { Button, TextControl, SelectControl, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Modal from '../common/Modal';
import { createTable } from '../../api/tables';
import { COLUMN_TYPES } from '../../utils/columnTypes';

/**
 * Create table modal component.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onClose Callback when modal is closed.
 * @param {Function} props.onSuccess Callback when table is created successfully.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Create table modal component.
 */
const CreateTableModal = ({ onClose, onSuccess, showNotification }) => {
	const [tableName, setTableName] = useState('');
	const [columns, setColumns] = useState([
		{
			name: 'id',
			type: 'BIGINT',
			length: 20,
			nullable: false,
			auto_increment: true,
			primary: true,
		},
	]);
	const [loading, setLoading] = useState(false);

	/**
	 * Add a new column.
	 */
	const addColumn = () => {
		setColumns([
			...columns,
			{
				name: '',
				type: 'VARCHAR',
				length: 255,
				nullable: true,
				auto_increment: false,
				primary: false,
			},
		]);
	};

	/**
	 * Update a column.
	 *
	 * @param {number} index Column index.
	 * @param {string} field Field to update.
	 * @param {*} value New value.
	 */
	const updateColumn = (index, field, value) => {
		const newColumns = [...columns];
		newColumns[index] = { ...newColumns[index], [field]: value };
		setColumns(newColumns);
	};

	/**
	 * Remove a column.
	 *
	 * @param {number} index Column index.
	 */
	const removeColumn = (index) => {
		if (columns.length <= 1) {
			showNotification(__('At least one column is required.', 'affinite-db-manager'), 'warning');
			return;
		}
		setColumns(columns.filter((_, i) => i !== index));
	};

	/**
	 * Handle form submission.
	 */
	const handleSubmit = async () => {
		if (!tableName) {
			showNotification(__('Table name is required.', 'affinite-db-manager'), 'error');
			return;
		}

		// Validate columns.
		for (const column of columns) {
			if (!column.name) {
				showNotification(__('All columns must have a name.', 'affinite-db-manager'), 'error');
				return;
			}
		}

		setLoading(true);
		try {
			await createTable(tableName, columns);
			showNotification(__('Table created successfully.', 'affinite-db-manager'), 'success');
			onSuccess();
		} catch (error) {
			showNotification(error.message || __('Failed to create table.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	const footer = (
		<>
			<Button variant="secondary" onClick={onClose} disabled={loading}>
				{__('Cancel', 'affinite-db-manager')}
			</Button>
			<Button variant="primary" onClick={handleSubmit} isBusy={loading} disabled={loading}>
				{__('Create', 'affinite-db-manager')}
			</Button>
		</>
	);

	return (
		<Modal
			title={__('New Table', 'affinite-db-manager')}
			onClose={onClose}
			footer={footer}
			width={1000}
		>
			<div className="affinite-db-manager__form-group">
				<label>{__('Table Name', 'affinite-db-manager')}</label>
				<div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
					<span>wp_</span>
					<TextControl
						value={tableName}
						onChange={setTableName}
						placeholder={__('my_table', 'affinite-db-manager')}
					/>
				</div>
			</div>

			<div className="affinite-db-manager__form-group">
				<label>{__('Columns', 'affinite-db-manager')}</label>
				{columns.map((column, index) => (
					<div key={index} style={{ marginBottom: '15px', padding: '10px', background: '#f6f7f7', borderRadius: '4px' }}>
						<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 80px', gap: '10px', marginBottom: '10px' }}>
							<TextControl
								label={__('Name', 'affinite-db-manager')}
								value={column.name}
								onChange={(value) => updateColumn(index, 'name', value)}
							/>
							<SelectControl
								label={__('Type', 'affinite-db-manager')}
								value={column.type}
								options={COLUMN_TYPES}
								onChange={(value) => updateColumn(index, 'type', value)}
							/>
							<TextControl
								label={__('Length', 'affinite-db-manager')}
								type="number"
								value={column.length || ''}
								onChange={(value) => updateColumn(index, 'length', parseInt(value) || null)}
							/>
						</div>
						<div style={{ display: 'flex', gap: '20px', alignItems: 'center' }}>
							<CheckboxControl
								label={__('Nullable', 'affinite-db-manager')}
								checked={column.nullable}
								onChange={(value) => updateColumn(index, 'nullable', value)}
							/>
							<CheckboxControl
								label={__('Auto Increment', 'affinite-db-manager')}
								checked={column.auto_increment}
								onChange={(value) => updateColumn(index, 'auto_increment', value)}
							/>
							<CheckboxControl
								label={__('Primary Key', 'affinite-db-manager')}
								checked={column.primary}
								onChange={(value) => updateColumn(index, 'primary', value)}
							/>
							<Button
								variant="link"
								isDestructive
								onClick={() => removeColumn(index)}
							>
								{__('Remove', 'affinite-db-manager')}
							</Button>
						</div>
					</div>
				))}
				<Button variant="secondary" onClick={addColumn}>
					{__('+ Add Column', 'affinite-db-manager')}
				</Button>
			</div>
		</Modal>
	);
};

export default CreateTableModal;
