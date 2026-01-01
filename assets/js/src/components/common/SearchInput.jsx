/**
 * Search input component for Affinite DB Manager.
 *
 * @package Affinite\DBManager
 */

import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Search input component.
 *
 * @param {Object} props Component props.
 * @param {string} props.value Current search value.
 * @param {Function} props.onChange Callback when value changes.
 * @param {string} props.placeholder Placeholder text.
 * @param {string} props.className Additional CSS class.
 * @returns {JSX.Element} Search input component.
 */
const SearchInput = ({
	value,
	onChange,
	placeholder = __('Search...', 'affinite-db-manager'),
	className = '',
}) => {
	return (
		<div className={`affinite-db-manager__search ${className}`}>
			<TextControl
				value={value}
				onChange={onChange}
				placeholder={placeholder}
				type="search"
			/>
		</div>
	);
};

export default SearchInput;
