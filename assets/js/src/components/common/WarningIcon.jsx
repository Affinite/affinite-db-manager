/**
 * Warning icon component for Affinite DB Manager.
 * Material Design warning icon.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';

/**
 * Warning icon component.
 *
 * @param {Object} props Component props.
 * @param {string} props.className Additional CSS classes.
 * @param {number} props.size Icon size in pixels (default: 24).
 * @returns {JSX.Element} Warning icon component.
 */
const WarningIcon = memo(({ className = '', size = 24 }) => {
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
				d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"
				fill="currentColor"
			/>
		</svg>
	);
});

WarningIcon.displayName = 'WarningIcon';

export default WarningIcon;

