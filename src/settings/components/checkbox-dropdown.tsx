import { Button, CheckboxControl, Dropdown } from '@wordpress/components';

import type { SlugOption } from '../types';

export interface CheckboxDropdownProps {
	/** Selected slugs. */
	value: string[];
	options: SlugOption[];
	/** Accessible name for the toggle button. */
	label: string;
	/** Heading shown above the checkboxes. */
	legend: string;
	/** Summary when nothing is selected, e.g. "All post types". */
	emptyLabel: string;
	/** Summary when more than `maxNames` are selected, e.g. "3 roles". */
	countLabel: (count: number) => string;
	/** How many names to list before switching to a count. Default 2. */
	maxNames?: number;
	/** Note listing saved slugs that aren't offered any more. */
	unknownNote: (slugs: string) => string;
	disabled?: boolean;
	/** Shown instead of the summary while disabled. */
	disabledLabel?: string;
	onChange: (value: string[]) => void;
}

/**
 * Short summary of a selection: the empty label, a few names, or a count.
 * Kept short to fit a table cell.
 *
 * @param value      Selected slugs.
 * @param options    Available options.
 * @param emptyLabel Summary when nothing is selected.
 * @param countLabel Summary when more than `maxNames` are selected.
 * @param maxNames   How many names to list before switching to a count.
 * @return Summary text.
 */
function summarize(
	value: string[],
	options: SlugOption[],
	emptyLabel: string,
	countLabel: (count: number) => string,
	maxNames: number
): string {
	if (value.length === 0) {
		return emptyLabel;
	}

	const labels = value.map(slug => options.find(option => option.value === slug)?.label ?? slug);

	return labels.length <= maxNames ? labels.join(', ') : countLabel(labels.length);
}

/**
 * Compact dropdown of checkboxes for a table cell. A dropdown keeps the cell
 * small compared with a token field.
 *
 * @param props               Component props.
 * @param props.value         Selected slugs.
 * @param props.options       Available options.
 * @param props.label         Accessible name for the toggle button.
 * @param props.legend        Heading above the checkboxes.
 * @param props.emptyLabel    Summary when nothing is selected.
 * @param props.countLabel    Summary when more than `maxNames` are selected.
 * @param props.maxNames      Names to list before switching to a count.
 * @param props.unknownNote   Note listing saved slugs that aren't offered.
 * @param props.disabled      Whether the control is locked.
 * @param props.disabledLabel Text shown while locked.
 * @param props.onChange      Called with the new selection.
 */
export function CheckboxDropdown({
	value,
	options,
	label,
	legend,
	emptyLabel,
	countLabel,
	maxNames = 2,
	unknownNote,
	disabled = false,
	disabledLabel,
	onChange,
}: CheckboxDropdownProps) {
	const summary =
		disabled && disabledLabel
			? disabledLabel
			: summarize(value, options, emptyLabel, countLabel, maxNames);

	// Saved slugs that no longer exist are kept, but not offered.
	const unknown = value.filter(slug => !options.some(option => option.value === slug));

	const toggle = (slug: string, checked: boolean) => {
		const next = checked ? [...value, slug] : value.filter(item => item !== slug);
		// Keep the stored order stable regardless of click order.
		onChange([
			...options.map(option => option.value).filter(optionSlug => next.includes(optionSlug)),
			...unknown,
		]);
	};

	return (
		<Dropdown
			className="blockpolicy-checkbox-dropdown"
			popoverProps={{ placement: 'bottom-start' }}
			renderToggle={({ isOpen, onToggle }) => (
				<Button
					__next40pxDefaultSize
					variant="tertiary"
					onClick={onToggle}
					aria-expanded={isOpen}
					disabled={disabled}
					accessibleWhenDisabled
					label={label}
					showTooltip={false}
				>
					{summary}
				</Button>
			)}
			renderContent={() => (
				<fieldset className="blockpolicy-checkbox-dropdown__content">
					<legend>{legend}</legend>
					{options.map(option => (
						<CheckboxControl
							key={option.value}
							__nextHasNoMarginBottom
							label={option.label}
							checked={value.includes(option.value)}
							onChange={checked => toggle(option.value, checked)}
						/>
					))}
					{unknown.length > 0 && (
						<p className="blockpolicy-checkbox-dropdown__note">
							{unknownNote(unknown.join(', '))}
						</p>
					)}
				</fieldset>
			)}
		/>
	);
}
