/**
 * Main entry point for Affinite DB Manager admin interface.
 *
 * @package Affinite\DBManager
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/main.css';

// Initialize the app when DOM is ready.
document.addEventListener('DOMContentLoaded', () => {
	const managerRoot = document.getElementById('affinite-db-manager-root');
	const settingsRoot = document.getElementById('affinite-db-manager-settings-root');

	if (managerRoot) {
		const root = createRoot(managerRoot);
		root.render(<App page="manager" />);
	}

	if (settingsRoot) {
		const root = createRoot(settingsRoot);
		root.render(<App page="settings" />);
	}
});
