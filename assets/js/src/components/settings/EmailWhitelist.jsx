/**
 * Email whitelist component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addEmail, removeEmail } from '../../api/settings';

/**
 * Email whitelist component.
 *
 * @param {Object} props Component props.
 * @param {Array} props.emails List of whitelisted emails.
 * @param {Function} props.onUpdate Callback when emails are updated.
 * @param {Function} props.showNotification Callback to show notification.
 * @returns {JSX.Element} Email whitelist component.
 */
const EmailWhitelist = ({ emails, onUpdate, showNotification }) => {
	const [newEmail, setNewEmail] = useState('');
	const [localEmails, setLocalEmails] = useState(emails);
	const [updatingEmails, setUpdatingEmails] = useState(new Set());

	// Sync local state with props
	useEffect(() => {
		setLocalEmails(emails);
	}, [emails]);

	/**
	 * Handle adding a new email with optimistic update.
	 */
	const handleAdd = useCallback(async () => {
		const emailToAdd = newEmail.trim();
		
		if (!emailToAdd) {
			return;
		}

		// Basic email validation.
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailToAdd)) {
			showNotification(__('Please enter a valid email address.', 'affinite-db-manager'), 'error');
			return;
		}

		// Check if already exists
		if (localEmails.includes(emailToAdd)) {
			showNotification(__('Email already in whitelist.', 'affinite-db-manager'), 'warning');
			return;
		}

		// Optimistic update
		const previousEmails = [...localEmails];
		setLocalEmails([...localEmails, emailToAdd]);
		setNewEmail('');
		setUpdatingEmails(prev => new Set(prev).add(emailToAdd));

		try {
			await addEmail(emailToAdd);
			showNotification(__('Email added successfully.', 'affinite-db-manager'), 'success');
			
			// Silent background refresh
			if (onUpdate) {
				onUpdate();
			}
		} catch (error) {
			// Rollback on error
			setLocalEmails(previousEmails);
			showNotification(error.message || __('Failed to add email.', 'affinite-db-manager'), 'error');
		} finally {
			setUpdatingEmails(prev => {
				const next = new Set(prev);
				next.delete(emailToAdd);
				return next;
			});
		}
	}, [newEmail, localEmails, onUpdate, showNotification]);

	/**
	 * Handle removing an email with optimistic update.
	 *
	 * @param {string} email Email to remove.
	 */
	const handleRemove = useCallback(async (email) => {
		if (updatingEmails.has(email)) {
			return;
		}

		// Optimistic update
		const previousEmails = [...localEmails];
		setLocalEmails(localEmails.filter(e => e !== email));
		setUpdatingEmails(prev => new Set(prev).add(email));

		try {
			await removeEmail(email);
			showNotification(__('Email removed successfully.', 'affinite-db-manager'), 'success');
			
			// Silent background refresh
			if (onUpdate) {
				onUpdate();
			}
		} catch (error) {
			// Rollback on error
			setLocalEmails(previousEmails);
			showNotification(error.message || __('Failed to remove email.', 'affinite-db-manager'), 'error');
		} finally {
			setUpdatingEmails(prev => {
				const next = new Set(prev);
				next.delete(email);
				return next;
			});
		}
	}, [localEmails, updatingEmails, onUpdate, showNotification]);

	/**
	 * Handle key press in input.
	 *
	 * @param {Event} e Key event.
	 */
	const handleKeyPress = useCallback((e) => {
		if (e.key === 'Enter') {
			handleAdd();
		}
	}, [handleAdd]);

	return (
		<div className="affinite-db-manager__email-whitelist">
			<p className="description">
				{__('Leave empty to allow all administrators. Add emails to restrict access to specific users.', 'affinite-db-manager')}
			</p>

			{localEmails.length > 0 ? (
				<ul className="affinite-db-manager__email-list">
					{localEmails.map((email) => {
						const isUpdating = updatingEmails.has(email);
						return (
							<li key={email} className="affinite-db-manager__email-item">
								<span>{email}</span>
								<Button
									variant="link"
									onClick={() => handleRemove(email)}
									disabled={isUpdating}
									isDestructive
									aria-label={__('Remove email', 'affinite-db-manager')}
								>
									{isUpdating ? '...' : '×'}
								</Button>
							</li>
						);
					})}
				</ul>
			) : (
				<p><em>{__('No email restrictions. All administrators have access.', 'affinite-db-manager')}</em></p>
			)}

			<div className="affinite-db-manager__add-email">
				<TextControl
					type="email"
					value={newEmail}
					onChange={setNewEmail}
					onKeyPress={handleKeyPress}
					placeholder={__('user@example.com', 'affinite-db-manager')}
					disabled={updatingEmails.size > 0}
				/>
				<Button
					variant="secondary"
					onClick={handleAdd}
					disabled={!newEmail.trim() || updatingEmails.size > 0}
				>
					{__('Add', 'affinite-db-manager')}
				</Button>
			</div>
		</div>
	);
};

export default EmailWhitelist;
