/**
 * View icon component for Affinite DB Manager.
 * Material Design visibility icon.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';

/**
 * View icon component.
 *
 * @param {Object} props Component props.
 * @param {string} props.className Additional CSS classes.
 * @param {number} props.size Icon size in pixels (default: 20).
 * @returns {JSX.Element} View icon component.
 */
const ViewIcon = memo(({ className = '', size = 20 }) => {
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
				d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"
				fill="currentColor"
			/>
		</svg>
	);
});

ViewIcon.displayName = 'ViewIcon';

export default ViewIcon;

