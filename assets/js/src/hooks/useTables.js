/**
 * Tables hook for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { getTables, getTable } from '../api/tables';

/**
 * Hook for managing tables list state.
 *
 * @returns {Object} Tables state and actions.
 */
export const useTables = () => {
	const [tables, setTables] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);

	/**
	 * Fetch tables from API.
	 */
	const fetchTables = useCallback(async () => {
		try {
			setLoading(true);
			setError(null);
			const data = await getTables();
			setTables(data);
		} catch (err) {
			setError(err.message || 'Failed to load tables');
		} finally {
			setLoading(false);
		}
	}, []);

	/**
	 * Refetch tables.
	 */
	const refetch = useCallback(() => {
		fetchTables();
	}, [fetchTables]);

	// Initial fetch.
	useEffect(() => {
		fetchTables();
	}, [fetchTables]);

	return {
		tables,
		loading,
		error,
		refetch,
	};
};

/**
 * Hook for managing single table state.
 *
 * @param {string} tableName Table name.
 * @returns {Object} Table state and actions.
 */
export const useTable = (tableName) => {
	const [table, setTable] = useState(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);

	/**
	 * Fetch table from API.
	 */
	const fetchTable = useCallback(async () => {
		if (!tableName) {
			setTable(null);
			setLoading(false);
			return;
		}

		try {
			setLoading(true);
			setError(null);
			const data = await getTable(tableName);
			setTable(data);
		} catch (err) {
			setError(err.message || 'Failed to load table');
		} finally {
			setLoading(false);
		}
	}, [tableName]);

	/**
	 * Refetch table.
	 */
	const refetch = useCallback(() => {
		fetchTable();
	}, [fetchTable]);

	// Fetch when table name changes.
	useEffect(() => {
		fetchTable();
	}, [fetchTable]);

	return {
		table,
		loading,
		error,
		refetch,
	};
};
