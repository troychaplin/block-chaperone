import { ToggleControl, VisuallyHidden } from '@wordpress/components';

interface PolicyToggleProps {
	/** Accessible label; visually hidden because the column header names it. */
	label: string;
	checked: boolean;
	disabled?: boolean;
	onChange: (value: boolean) => void;
}

/**
 * Compact on/off control for a table cell (Hide, Unregister).
 *
 * @param props          Component props.
 * @param props.label    Accessible label.
 * @param props.checked  Current value.
 * @param props.disabled Whether the toggle is locked.
 * @param props.onChange Called with the new value.
 */
export function PolicyToggle({ label, checked, disabled = false, onChange }: PolicyToggleProps) {
	return (
		<div className="block-chaperone-toggle-cell">
			<ToggleControl
				__nextHasNoMarginBottom
				label={<VisuallyHidden>{label}</VisuallyHidden>}
				checked={checked}
				disabled={disabled}
				onChange={onChange}
			/>
		</div>
	);
}
