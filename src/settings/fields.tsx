/**
 * DataViews field definitions for the block policy table.
 */
import type { Field } from '@wordpress/dataviews';
import { __, sprintf } from '@wordpress/i18n';

import { PolicyToggle } from './components/policy-toggle';
import { PostTypeSelect } from './components/post-type-select';
import { RoleSelect } from './components/role-select';
import type { BlockRow, PolicyStatus, RulePatch, SlugOption } from './types';

export const STATUS_LABELS: Record<PolicyStatus, string> = {
	allowed: __('Allowed', 'blockpolicy'),
	restricted: __('Restricted', 'blockpolicy'),
	hidden: __('Hidden', 'blockpolicy'),
	unregistered: __('Unregistered', 'blockpolicy'),
};

interface FieldArgs {
	rows: BlockRow[];
	postTypes: SlugOption[];
	roles: SlugOption[];
	onChange: (id: string, patch: RulePatch) => void;
}

/**
 * Distinct, sorted filter options for a string field.
 *
 * @param rows  Table rows.
 * @param value Picks the value from a row.
 * @param label Picks the label from a row.
 * @return Filter options.
 */
function uniqueElements(
	rows: BlockRow[],
	value: (row: BlockRow) => string,
	label: (row: BlockRow) => string
) {
	const seen = new Map<string, string>();
	rows.forEach(row => seen.set(value(row), label(row)));

	return [...seen.entries()]
		.map(([elementValue, elementLabel]) => ({ value: elementValue, label: elementLabel }))
		.sort((a, b) => a.label.localeCompare(b.label));
}

/**
 * Build the field list.
 *
 * @param args           Field inputs.
 * @param args.rows      Table rows (for filter options).
 * @param args.postTypes Post types a block can be restricted in.
 * @param args.roles     User roles a block's restrictions can be limited to.
 * @param args.onChange  Called when a row's policy is edited.
 * @return Field definitions.
 */
export function getFields({ rows, postTypes, roles, onChange }: FieldArgs): Field<BlockRow>[] {
	return [
		{
			// The row's title (the view's `titleField`): the block name, e.g. core/quote.
			id: 'name',
			label: __('Block', 'blockpolicy'),
			enableGlobalSearch: true,
			enableSorting: true,
			// The name carries the row's identity; hiding it would leave nothing to identify the block.
			enableHiding: false,
			filterBy: false,
		},
		{
			// Shown under the name (the view's `descriptionField`).
			id: 'description',
			label: __('Description', 'blockpolicy'),
			enableGlobalSearch: true,
			enableSorting: false,
			filterBy: false,
			render: ({ item }) => (
				<span className="blockpolicy-description-cell">{item.description}</span>
			),
		},
		{
			// Not shown by default, but searchable, so "Quote" still finds core/quote.
			id: 'title',
			label: __('Title', 'blockpolicy'),
			enableGlobalSearch: true,
			enableSorting: true,
			filterBy: false,
		},
		{
			id: 'status',
			label: __('Status', 'blockpolicy'),
			enableSorting: true,
			elements: (Object.keys(STATUS_LABELS) as PolicyStatus[]).map(status => ({
				value: status,
				label: STATUS_LABELS[status],
			})),
			filterBy: { isPrimary: true, operators: ['isAny', 'isNone'] },
			render: ({ item }) => (
				<span className={`blockpolicy-status is-${item.status}`}>
					{STATUS_LABELS[item.status]}
				</span>
			),
		},
		{
			id: 'category',
			label: __('Category', 'blockpolicy'),
			enableSorting: true,
			elements: uniqueElements(
				rows,
				row => row.category,
				row => row.categoryLabel
			),
			filterBy: { isPrimary: true, operators: ['isAny', 'isNone'] },
			render: ({ item }) => item.categoryLabel,
		},
		{
			id: 'source',
			label: __('Source', 'blockpolicy'),
			enableSorting: true,
			enableGlobalSearch: true,
			elements: uniqueElements(
				rows,
				row => row.source,
				row => row.source
			),
			filterBy: { isPrimary: true, operators: ['isAny', 'isNone'] },
		},
		{
			id: 'hidden',
			label: __('Hide from inserter', 'blockpolicy'),
			enableSorting: false,
			filterBy: false,
			render: ({ item }) => (
				<PolicyToggle
					label={sprintf(
						/* translators: %s: block title. */
						__('Hide %s from the inserter', 'blockpolicy'),
						item.title
					)}
					checked={item.hidden}
					// Unregistering implies hiding, so the toggle is locked on.
					disabled={item.protected || item.unregistered}
					onChange={hidden => onChange(item.id, { hidden })}
				/>
			),
		},
		{
			id: 'unregistered',
			label: __('Unregister', 'blockpolicy'),
			enableSorting: false,
			filterBy: false,
			render: ({ item }) => (
				<PolicyToggle
					label={sprintf(
						/* translators: %s: block title. */
						__('Unregister %s', 'blockpolicy'),
						item.title
					)}
					checked={item.unregistered}
					disabled={item.protected}
					onChange={unregistered => onChange(item.id, { unregistered })}
				/>
			),
		},
		{
			id: 'postTypes',
			label: __('Post types', 'blockpolicy'),
			enableSorting: false,
			filterBy: false,
			render: ({ item }) => (
				<PostTypeSelect
					blockTitle={item.title}
					value={item.postTypes}
					options={postTypes}
					// Post type rules don't matter while the block is hidden everywhere.
					disabled={item.protected || item.hidden}
					disabledLabel={
						item.protected
							? __('Always allowed', 'blockpolicy')
							: __('Hidden everywhere', 'blockpolicy')
					}
					onChange={value => onChange(item.id, { postTypes: value })}
				/>
			),
		},
		{
			id: 'roles',
			label: __('Roles', 'blockpolicy'),
			enableSorting: false,
			filterBy: false,
			render: ({ item }) => (
				<RoleSelect
					blockTitle={item.title}
					value={item.roles}
					options={roles}
					// Roles only scope restrictions, so there's nothing to scope until one is set.
					disabled={item.protected || item.status === 'allowed'}
					disabledLabel={
						item.protected
							? __('Always allowed', 'blockpolicy')
							: __('No restrictions', 'blockpolicy')
					}
					onChange={value => onChange(item.id, { roles: value })}
				/>
			),
		},
	];
}
