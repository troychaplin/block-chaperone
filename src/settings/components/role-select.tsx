import { __, _n, sprintf } from '@wordpress/i18n';

import type { SlugOption } from '../types';
import { CheckboxDropdown } from './checkbox-dropdown';

interface RoleSelectProps {
	/** Block title, used to give the control an accessible name. */
	blockTitle: string;
	/** Roles the block's restrictions apply to; empty means everyone. */
	value: string[];
	options: SlugOption[];
	disabled?: boolean;
	/** Shown instead of the summary while disabled. */
	disabledLabel?: string;
	onChange: (value: string[]) => void;
}

/**
 * Choose which user roles a block's restrictions apply to.
 *
 * @param props            Component props.
 * @param props.blockTitle Block title for the accessible name.
 */
export function RoleSelect({ blockTitle, ...props }: RoleSelectProps) {
	return (
		<CheckboxDropdown
			{...props}
			label={sprintf(
				/* translators: %s: block title. */
				__('Roles restricted from %s', 'blockpolicy'),
				blockTitle
			)}
			legend={__('Apply restrictions to', 'blockpolicy')}
			emptyLabel={__('All roles', 'blockpolicy')}
			// Role names are long; list one, then switch to a count.
			maxNames={1}
			countLabel={count =>
				sprintf(
					/* translators: %d: number of roles. */
					_n('%d role', '%d roles', count, 'blockpolicy'),
					count
				)
			}
			unknownNote={slugs =>
				sprintf(
					/* translators: %s: comma-separated role slugs. */
					__('Also saved for roles that no longer exist: %s', 'blockpolicy'),
					slugs
				)
			}
		/>
	);
}
