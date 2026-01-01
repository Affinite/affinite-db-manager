/**
 * Table detail component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect, useCallback, memo } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useTable } from '../../hooks/useTables';
import { getColumns } from '../../api/columns';
import { getIndexes } from '../../api/indexes';
import { getRelations } from '../../api/relations';
import { getData } from '../../api/data';
import ColumnEditor from './ColumnEditor';
import IndexManager from './IndexManager';
import RelationEditor from './RelationEditor';
import DataPreview from './DataPreview';
import LockIcon from '../common/LockIcon';
import ArrowBackIcon from '../common/ArrowBackIcon';

const TABS = {
	COLUMNS: 'columns',
	INDEXES: 'indexes',
	RELATIONS: 'relations',
	DATA: 'data',
};

/**
 * Table detail component.
 *
 * @param {Object} props Component props.
 * @param {string} props.tableName Table name.
 * @param {Function} props.onBack Callback to go back to table list.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Table detail component.
 */
const TableDetail = memo(({ tableName, onBack, showNotification }) => {
	const { table, loading, error, refetch } = useTable(tableName);
	const [activeTab, setActiveTab] = useState(TABS.COLUMNS);
	const [columns, setColumns] = useState([]);
	const [indexes, setIndexes] = useState([]);
	const [relations, setRelations] = useState([]);
	const [data, setData] = useState(null);
	const [tabLoading, setTabLoading] = useState(false);
	const [initialLoad, setInitialLoad] = useState(true);

	// Reset state when table name changes
	useEffect(() => {
		setColumns([]);
		setIndexes([]);
		setRelations([]);
		setData(null);
		setInitialLoad(true);
		setActiveTab(TABS.COLUMNS);
	}, [tableName]);

	// Load Columns, Indexes, and Relations immediately on mount
	useEffect(() => {
		if (!tableName || !table || !initialLoad) {
			return;
		}

		const loadInitialData = async () => {
			setTabLoading(true);
			try {
				// Load all three tabs in parallel
				const [columnsData, indexesData, relationsData] = await Promise.all([
					getColumns(tableName),
					getIndexes(tableName),
					getRelations(tableName),
				]);

				setColumns(columnsData);
				setIndexes(indexesData);
				setRelations(relationsData);
				setInitialLoad(false);
			} catch (err) {
				showNotification(err.message || __('Failed to load data.', 'affinite-db-manager'), 'error');
				setInitialLoad(false);
			} finally {
				setTabLoading(false);
			}
		};

		loadInitialData();
	}, [tableName, table, initialLoad, showNotification]);

	// Load Data tab only when clicked
	useEffect(() => {
		if (activeTab !== TABS.DATA || data !== null || !tableName) {
			return;
		}

		const fetchData = async () => {
			setTabLoading(true);
			try {
				const dataResult = await getData(tableName);
				setData(dataResult);
			} catch (err) {
				showNotification(err.message || __('Failed to load data.', 'affinite-db-manager'), 'error');
			} finally {
				setTabLoading(false);
			}
		};

		fetchData();
	}, [activeTab, tableName, data, showNotification]);

	/**
	 * Refresh current tab data.
	 */
	const refreshTabData = useCallback(async () => {
		if (!tableName) {
			return;
		}

		setTabLoading(true);
		try {
			switch (activeTab) {
				case TABS.COLUMNS:
					const columnsData = await getColumns(tableName);
					setColumns(columnsData);
					break;
				case TABS.INDEXES:
					const indexesData = await getIndexes(tableName);
					setIndexes(indexesData);
					break;
				case TABS.RELATIONS:
					const relationsData = await getRelations(tableName);
					setRelations(relationsData);
					break;
				case TABS.DATA:
					const dataResult = await getData(tableName);
					setData(dataResult);
					break;
			}
		} catch (err) {
			showNotification(err.message || __('Failed to refresh data.', 'affinite-db-manager'), 'error');
		} finally {
			setTabLoading(false);
		}
	}, [tableName, activeTab, showNotification]);

	if (loading) {
		return (
			<div className="affinite-db-manager__loading">
				<Spinner />
				<span>{__('Loading table...', 'affinite-db-manager')}</span>
			</div>
		);
	}

	if (error || !table) {
		return (
			<div className="affinite-db-manager__error">
				<p>{error || __('Table not found.', 'affinite-db-manager')}</p>
				<Button variant="primary" onClick={onBack}>
					{__('Back', 'affinite-db-manager')}
				</Button>
			</div>
		);
	}

	return (
		<div className="affinite-db-manager__table-detail">
			<div className="affinite-db-manager__back">
				<Button variant="link" onClick={onBack}>
					<ArrowBackIcon /> {__('Back to Tables', 'affinite-db-manager')}
				</Button>
			</div>

			<div className="affinite-db-manager__card">
				<div className="affinite-db-manager__card-header">
					<h2>
						<LockIcon locked={table.is_locked} />
						{table.name}
					</h2>
					<span>
						{table.columns} {__('columns', 'affinite-db-manager')} • {table.rows.toLocaleString()} {__('rows', 'affinite-db-manager')}
					</span>
				</div>

				<div className="affinite-db-manager__tabs">
					<button
						className={`affinite-db-manager__tab ${activeTab === TABS.COLUMNS ? 'affinite-db-manager__tab--active' : ''}`}
						onClick={() => setActiveTab(TABS.COLUMNS)}
					>
						{__('Columns', 'affinite-db-manager')}
					</button>
					<button
						className={`affinite-db-manager__tab ${activeTab === TABS.INDEXES ? 'affinite-db-manager__tab--active' : ''}`}
						onClick={() => setActiveTab(TABS.INDEXES)}
					>
						{__('Indexes', 'affinite-db-manager')}
					</button>
					<button
						className={`affinite-db-manager__tab ${activeTab === TABS.RELATIONS ? 'affinite-db-manager__tab--active' : ''}`}
						onClick={() => setActiveTab(TABS.RELATIONS)}
					>
						{__('Relations', 'affinite-db-manager')}
					</button>
					<button
						className={`affinite-db-manager__tab ${activeTab === TABS.DATA ? 'affinite-db-manager__tab--active' : ''}`}
						onClick={() => setActiveTab(TABS.DATA)}
					>
						{__('Data', 'affinite-db-manager')}
					</button>
				</div>

				<div className="affinite-db-manager__card-body">
					{tabLoading && (initialLoad || activeTab === TABS.DATA) ? (
						<div className="affinite-db-manager__loading">
							<Spinner />
							<span>{__('Loading...', 'affinite-db-manager')}</span>
						</div>
					) : (
						<>
							{activeTab === TABS.COLUMNS && (
								<ColumnEditor
									tableName={tableName}
									columns={columns}
									isLocked={table.is_locked}
									onUpdate={refreshTabData}
									showNotification={showNotification}
								/>
							)}
							{activeTab === TABS.INDEXES && (
								<IndexManager
									tableName={tableName}
									indexes={indexes}
									columns={columns}
									isLocked={table.is_locked}
									onUpdate={refreshTabData}
									showNotification={showNotification}
								/>
							)}
							{activeTab === TABS.RELATIONS && (
								<RelationEditor
									tableName={tableName}
									relations={relations}
									columns={columns}
									isLocked={table.is_locked}
									onUpdate={refreshTabData}
									showNotification={showNotification}
								/>
							)}
							{activeTab === TABS.DATA && (
								<DataPreview
									tableName={tableName}
									data={data}
									showNotification={showNotification}
								/>
							)}
						</>
					)}
				</div>
			</div>
		</div>
	);
});

export default TableDetail;
