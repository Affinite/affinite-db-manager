/**
 * Delete icon component for Affinite DB Manager.
 * Material Design delete icon.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';

/**
 * Delete icon component.
 *
 * @param {Object} props Component props.
 * @param {string} props.className Additional CSS classes.
 * @param {number} props.size Icon size in pixels (default: 20).
 * @returns {JSX.Element} Delete icon component.
 */
const DeleteIcon = memo(({ className = '', size = 20 }) => {
	const iconClassName = `affinite-db-manager__icon-svg ${className}`.trim();

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
				d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"
				fill="currentColor"
			/>
		</svg>
	);
});

DeleteIcon.displayName = 'DeleteIcon';

export default DeleteIcon;

