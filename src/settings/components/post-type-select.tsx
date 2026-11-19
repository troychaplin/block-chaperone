import { __, _n, sprintf } from '@wordpress/i18n';

import type { SlugOption } from '../types';
import { CheckboxDropdown } from './checkbox-dropdown';

interface PostTypeSelectProps {
	/** Block title, used to give the control an accessible name. */
	blockTitle: string;
	/** Post types the block is restricted (hidden from the inserter) in. */
	value: string[];
	options: SlugOption[];
	disabled?: boolean;
	/** Shown instead of the summary while disabled. */
	disabledLabel?: string;
	onChange: (value: string[]) => void;
}

/**
 * Choose the post types a block is hidden from the inserter in.
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
				__('Post type restrictions for %s', 'block-policy'),
				blockTitle
			)}
			legend={__('Hide from the inserter in', 'block-policy')}
			emptyLabel={__('All post types', 'block-policy')}
			countLabel={count =>
				sprintf(
					/* translators: %d: number of post types. */
					_n('%d post type', '%d post types', count, 'block-policy'),
					count
				)
			}
			unknownNote={slugs =>
				sprintf(
					/* translators: %s: comma-separated post type slugs. */
					__('Also saved for unregistered post types: %s', 'block-policy'),
					slugs
				)
			}
		/>
	);
}
