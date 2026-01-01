/**
 * Confirm dialog component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useState } from '@wordpress/element';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Modal from './Modal';

/**
 * Confirm dialog component.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Dialog title.
 * @param {string} props.message Confirmation message.
 * @param {string} props.confirmText Text to type for confirmation (optional).
 * @param {string} props.confirmLabel Confirm button label.
 * @param {string} props.cancelLabel Cancel button label.
 * @param {boolean} props.isDangerous Whether this is a dangerous action.
 * @param {Function} props.onConfirm Callback when confirmed.
 * @param {Function} props.onCancel Callback when cancelled.
 * @returns {JSX.Element} Confirm dialog component.
 */
const ConfirmDialog = ({
	title,
	message,
	confirmText = '',
	confirmLabel = __('Confirm', 'affinite-db-manager'),
	cancelLabel = __('Cancel', 'affinite-db-manager'),
	isDangerous = false,
	onConfirm,
	onCancel,
}) => {
	const [inputValue, setInputValue] = useState('');
	const [loading, setLoading] = useState(false);

	const requiresInput = confirmText.length > 0;
	const inputMatches = inputValue === confirmText;
	const canConfirm = !requiresInput || inputMatches;

	/**
	 * Handle confirm action.
	 */
	const handleConfirm = async () => {
		if (!canConfirm) {
			return;
		}

		setLoading(true);
		try {
			await onConfirm();
		} finally {
			setLoading(false);
		}
	};

	const footer = (
		<>
			<Button
				variant="secondary"
				onClick={onCancel}
				disabled={loading}
			>
				{cancelLabel}
			</Button>
			<Button
				variant={isDangerous ? 'primary' : 'primary'}
				onClick={handleConfirm}
				disabled={!canConfirm || loading}
				isBusy={loading}
				isDestructive={isDangerous}
			>
				{confirmLabel}
			</Button>
		</>
	);

	return (
		<Modal title={title} onClose={onCancel} footer={footer}>
			<p>{message}</p>
			{requiresInput && (
				<div className="affinite-db-manager__confirm-input">
					<label>
						{__('To confirm, type', 'affinite-db-manager')} <strong>{confirmText}</strong>:
					</label>
					<TextControl
						value={inputValue}
						onChange={setInputValue}
						placeholder={confirmText}
					/>
				</div>
			)}
		</Modal>
	);
};

export default ConfirmDialog;
