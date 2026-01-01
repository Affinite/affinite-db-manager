/**
 * Notification component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Notification component.
 *
 * @param {Object} props Component props.
 * @param {string} props.message Notification message.
 * @param {string} props.type Notification type (success, error, warning, info).
 * @param {Function} props.onDismiss Callback when notification is dismissed.
 * @param {number} props.autoDismiss Auto dismiss after ms (0 to disable).
 * @returns {JSX.Element} Notification component.
 */
const Notification = ({ message, type = 'info', onDismiss, autoDismiss = 5000 }) => {
	useEffect(() => {
		if (autoDismiss > 0 && onDismiss) {
			const timer = setTimeout(onDismiss, autoDismiss);
			return () => clearTimeout(timer);
		}
	}, [autoDismiss, onDismiss]);

	return (
		<div className={`affinite-db-manager__notification affinite-db-manager__notification--${type}`}>
			<span>{message}</span>
			{onDismiss && (
				<Button
					variant="link"
					onClick={onDismiss}
					aria-label="Dismiss notification"
				>
					&times;
				</Button>
			)}
		</div>
	);
};

export default Notification;
