import { __, _n, sprintf } from '@wordpress/i18n';

import type { SlugOption } from '../types';
import { CheckboxDropdown } from './checkbox-dropdown';

interface PostTypeSelectProps {
	/** Block title, used to give the control an accessible name. */
	blockTitle: string;
	/** Post types the block's restrictions apply in; empty means all. */
	value: string[];
	options: SlugOption[];
	disabled?: boolean;
	/** Shown instead of the summary while disabled. */
	disabledLabel?: string;
	onChange: (value: string[]) => void;
}

/**
 * Choose which post types a block's restrictions apply in.
 *
 * @param props            Component props.
 * @param props.blockTitle Block title for the accessible name.
 */
export function PostTypeSelect({ blockTitle, ...props }: PostTypeSelectProps) {
	return (
		<CheckboxDropdown
			{...props}
			label={sprintf(
				/* translators: %s: block title. */
				__('Post types restricted for %s', 'block-chaperone'),
				blockTitle
			)}
			legend={__('Apply restrictions in', 'block-chaperone')}
			emptyLabel={__('All post types', 'block-chaperone')}
			countLabel={count =>
				sprintf(
					/* translators: %d: number of post types. */
					_n('%d post type', '%d post types', count, 'block-chaperone'),
					count
				)
			}
			unknownNote={slugs =>
				sprintf(
					/* translators: %s: comma-separated post type slugs. */
					__('Also saved for unregistered post types: %s', 'block-chaperone'),
					slugs
				)
			}
		/>
	);
}
