import { Button, CheckboxControl, Dropdown } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

import type { PostTypeOption } from '../types';

interface PostTypeSelectProps {
	/** Block title, used to give the control an accessible name. */
	blockTitle: string;
	/** Post types the block is restricted (hidden from the inserter) in. */
	value: string[];
	options: PostTypeOption[];
	disabled?: boolean;
	/** Shown instead of the summary while disabled. */
	disabledLabel?: string;
	onChange: (value: string[]) => void;
}

/**
 * Short summary of the current selection, e.g. "All post types", "Pages, Posts"
 * or "3 post types". Kept short to fit the table cell; the Status column
 * already says the block is restricted.
 *
 * @param value   Selected slugs.
 * @param options Available post types.
 * @return Summary text.
 */
function summarize(value: string[], options: PostTypeOption[]): string {
	if (value.length === 0) {
		return __('All post types', 'block-policy');
	}

	const labels = value.map(slug => options.find(option => option.value === slug)?.label ?? slug);

	if (labels.length <= 2) {
		return labels.join(', ');
	}

	return sprintf(
		/* translators: %d: number of post types. */
		_n('%d post type', '%d post types', labels.length, 'block-policy'),
		labels.length
	);
}

/**
 * Dropdown of checkboxes for choosing the post types a block is restricted in.
 * A dropdown keeps the table cell compact compared with a token field.
 *
 * @param props               Component props.
 * @param props.blockTitle    Block title for the accessible name.
 * @param props.value         Selected slugs.
 * @param props.options       Available post types.
 * @param props.disabled      Whether the control is locked.
 * @param props.disabledLabel Text shown while locked.
 * @param props.onChange      Called with the new selection.
 */
export function PostTypeSelect({
	blockTitle,
	value,
	options,
	disabled = false,
	disabledLabel,
	onChange,
}: PostTypeSelectProps) {
	const summary = disabled && disabledLabel ? disabledLabel : summarize(value, options);

	// Slugs saved for post types that no longer exist are kept, but not offered.
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
			className="block-policy-post-types"
			popoverProps={{ placement: 'bottom-start' }}
			renderToggle={({ isOpen, onToggle }) => (
				<Button
					__next40pxDefaultSize
					variant="tertiary"
					onClick={onToggle}
					aria-expanded={isOpen}
					disabled={disabled}
					accessibleWhenDisabled
					label={sprintf(
						/* translators: %s: block title. */
						__('Post type restrictions for %s', 'block-policy'),
						blockTitle
					)}
					showTooltip={false}
				>
					{summary}
				</Button>
			)}
			renderContent={() => (
				<fieldset className="block-policy-post-types__content">
					<legend>{__('Hide from the inserter in', 'block-policy')}</legend>
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
						<p className="block-policy-post-types__note">
							{sprintf(
								/* translators: %s: comma-separated post type slugs. */
								__('Also saved for unregistered post types: %s', 'block-policy'),
								unknown.join(', ')
							)}
						</p>
					)}
				</fieldset>
			)}
		/>
	);
}
