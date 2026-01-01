/**
 * Arrow back icon component for Affinite DB Manager.
 * Material Design arrow_back icon.
 *
 * @package Affinite\DBManager
 */

import { memo } from '@wordpress/element';

/**
 * Arrow back icon component.
 *
 * @param {Object} props Component props.
 * @param {string} props.className Additional CSS classes.
 * @param {number} props.size Icon size in pixels (default: 20).
 * @returns {JSX.Element} Arrow back icon component.
 */
const ArrowBackIcon = memo(({ className = '', size = 20 }) => {
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
				d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"
				fill="currentColor"
			/>
		</svg>
	);
});

ArrowBackIcon.displayName = 'ArrowBackIcon';

export default ArrowBackIcon;

