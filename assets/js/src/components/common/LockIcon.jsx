/**
 * Lock icon component for Affinite DB Manager.
 * Material Design lock/unlock icons.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';

/**
 * Lock icon component.
 *
 * @param {Object} props Component props.
 * @param {boolean} props.locked Whether the icon should show locked state.
 * @param {string} props.className Additional CSS classes.
 * @param {number} props.size Icon size in pixels (default: 20).
 * @returns {JSX.Element} Lock icon component.
 */
const LockIcon = memo(({ locked = false, className = '', size = 20 }) => {
	const iconClassName = `affinite-db-manager__lock-icon-svg ${className}`.trim();

	if (locked) {
		// Locked icon (Material Design lock)
		return (
			<svg
				className={iconClassName}
				width={size}
				height={size}
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
				aria-hidden="true"
			>
				<path
					d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"
					fill="currentColor"
				/>
			</svg>
		);
	}

	// Unlocked icon (Material Design lock_open)
	return (
		<svg
			className={iconClassName}
			width={size}
			height={size}
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
		>
			<path
				d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h1.9c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2z"
				fill="currentColor"
			/>
		</svg>
	);
});

LockIcon.displayName = 'LockIcon';

export default LockIcon;

