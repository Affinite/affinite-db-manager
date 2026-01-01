/**
 * Edit icon component for Affinite DB Manager.
 * Material Design edit icon.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';

/**
 * Edit icon component.
 *
 * @param {Object} props Component props.
 * @param {string} props.className Additional CSS classes.
 * @param {number} props.size Icon size in pixels (default: 20).
 * @returns {JSX.Element} Edit icon component.
 */
const EditIcon = memo(({ className = '', size = 20 }) => {
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
				d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
				fill="currentColor"
			/>
		</svg>
	);
});

EditIcon.displayName = 'EditIcon';

export default EditIcon;

