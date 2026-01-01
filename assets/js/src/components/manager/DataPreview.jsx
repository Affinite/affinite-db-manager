/**
 * Data preview component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getData } from '../../api/data';

/**
 * Data preview component.
 *
 * @param {Object} props Component props.
 * @param {string} props.tableName Table name.
 * @param {Object} props.data Data preview object.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Data preview component.
 */
const DataPreview = ({ tableName, data, showNotification }) => {
	const [currentData, setCurrentData] = useState(data);
	const [loading, setLoading] = useState(false);
	const [page, setPage] = useState(0);
	const limit = 100;

	if (!currentData) {
		return (
			<div className="affinite-db-manager__no-data">
				{__('No data available.', 'affinite-db-manager')}
			</div>
		);
	}

	const { columns, rows, total } = currentData;
	const totalPages = Math.ceil(total / limit);

	/**
	 * Load page data.
	 *
	 * @param {number} newPage Page number.
	 */
	const loadPage = async (newPage) => {
		setLoading(true);
		try {
			const result = await getData(tableName, limit, newPage * limit);
			setCurrentData(result);
			setPage(newPage);
		} catch (error) {
			showNotification(error.message || __('Failed to load data.', 'affinite-db-manager'), 'error');
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Format cell value for display.
	 *
	 * @param {*} value Cell value.
	 * @returns {string} Formatted value.
	 */
	const formatValue = (value) => {
		if (value === null) {
			return <em>NULL</em>;
		}

		if (typeof value === 'boolean') {
			return value ? 'true' : 'false';
		}

		const strValue = String(value);
		if (strValue.length > 100) {
			return strValue.substring(0, 100) + '...';
		}

		return strValue;
	};

	return (
		<div className="affinite-db-manager__data-preview">
			<p>
				{__('Showing', 'affinite-db-manager')} {rows.length} {__('of', 'affinite-db-manager')} {total.toLocaleString()} {__('rows', 'affinite-db-manager')}
			</p>

			<div style={{ overflowX: 'auto' }}>
				<table className="affinite-db-manager__data-table">
					<thead>
						<tr>
							{columns.map((column) => (
								<th key={column}>{column}</th>
							))}
						</tr>
					</thead>
					<tbody>
						{rows.map((row, index) => (
							<tr key={index}>
								{columns.map((column) => (
									<td key={column}>{formatValue(row[column])}</td>
								))}
							</tr>
						))}
						{rows.length === 0 && (
							<tr>
								<td colSpan={columns.length} style={{ textAlign: 'center' }}>
									{__('No data found.', 'affinite-db-manager')}
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</div>

			{totalPages > 1 && (
				<div className="affinite-db-manager__pagination">
					<span>
						{__('Page', 'affinite-db-manager')} {page + 1} {__('of', 'affinite-db-manager')} {totalPages}
					</span>
					<div>
						<Button
							variant="secondary"
							onClick={() => loadPage(page - 1)}
							disabled={page === 0 || loading}
							isSmall
						>
							{__('Previous', 'affinite-db-manager')}
						</Button>
						<Button
							variant="secondary"
							onClick={() => loadPage(page + 1)}
							disabled={page >= totalPages - 1 || loading}
							isSmall
							style={{ marginLeft: '10px' }}
						>
							{__('Next', 'affinite-db-manager')}
						</Button>
					</div>
				</div>
			)}
		</div>
	);
};

export default DataPreview;
