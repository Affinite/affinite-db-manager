/**
 * Modal component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Modal component.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Modal title.
 * @param {JSX.Element} props.children Modal content.
 * @param {Function} props.onClose Callback when modal is closed.
 * @param {JSX.Element} props.footer Optional footer content.
 * @param {string} props.className Additional CSS class name.
 * @param {number|string} props.width Custom width (in pixels or CSS value).
 * @returns {JSX.Element} Modal component.
 */
const Modal = ({ title, children, onClose, footer, className = '', width }) => {
	// Handle escape key.
	useEffect(() => {
		const handleEscape = (e) => {
			if (e.key === 'Escape') {
				onClose();
			}
		};

		document.addEventListener('keydown', handleEscape);
		return () => document.removeEventListener('keydown', handleEscape);
	}, [onClose]);

	// Prevent body scroll when modal is open.
	useEffect(() => {
		document.body.style.overflow = 'hidden';
		return () => {
			document.body.style.overflow = '';
		};
	}, []);

	/**
	 * Handle overlay click.
	 *
	 * @param {Event} e Click event.
	 */
	const handleOverlayClick = (e) => {
		if (e.target === e.currentTarget) {
			onClose();
		}
	};

	return (
		<div
			className="affinite-db-manager__modal-overlay"
			onClick={handleOverlayClick}
			role="dialog"
			aria-modal="true"
			aria-labelledby="modal-title"
		>
			<div 
				className={`affinite-db-manager__modal ${className}`.trim()}
				style={width ? { width: typeof width === 'number' ? `${width}px` : width, maxWidth: typeof width === 'number' ? `${width}px` : width } : {}}
			>
				<div className="affinite-db-manager__modal-header">
					<h3 id="modal-title">{title}</h3>
					<Button
						variant="link"
						onClick={onClose}
						aria-label="Close modal"
					>
						&times;
					</Button>
				</div>
				<div className="affinite-db-manager__modal-body">
					{children}
				</div>
				{footer && (
					<div className="affinite-db-manager__modal-footer">
						{footer}
					</div>
				)}
			</div>
		</div>
	);
};

export default Modal;
