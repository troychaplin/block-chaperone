/**
 * BlockPolicy settings app.
 */
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, Spinner } from '@wordpress/components';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews';
import type { Action, View } from '@wordpress/dataviews';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

import { getFields } from './fields';
import type { BlockRow, BlockRule, Catalog, PolicySettings, SlugOption, RulePatch } from './types';
import {
	applyPatch,
	catalogToRows,
	collectOrphanRules,
	reapplySettings,
	rowsToSettings,
} from './utils/transform';

const BLOCKS_PATH = '/blockpolicy/v1/blocks';
const SETTINGS_PATH = '/blockpolicy/v1/settings';

const DEFAULT_VIEW: View = {
	type: 'table',
	search: '',
	filters: [],
	page: 1,
	perPage: 25,
	sort: { field: 'name', direction: 'asc' },
	titleField: 'name',
	descriptionField: 'description',
	fields: ['status', 'category', 'source', 'hidden', 'unregistered', 'postTypes', 'roles'],
};

interface NoticeState {
	status: 'success' | 'error';
	message: string;
}

/**
 * Message from a failed apiFetch call.
 *
 * @param error    Thrown value.
 * @param fallback Message to use when none is available.
 * @return Message.
 */
function errorMessage(error: unknown, fallback: string): string {
	if (error && typeof error === 'object' && 'message' in error) {
		const { message } = error as { message: unknown };
		if (typeof message === 'string' && message) {
			return message;
		}
	}
	return fallback;
}

export function App() {
	const [rows, setRows] = useState<BlockRow[]>([]);
	// Rows as first loaded. Only used to build filter options (categories and
	// sources never change after load), so the field definitions stay stable
	// while rows are edited. Rebuilding them on every edit would remount every
	// cell and close any open post type dropdown after a single click.
	const [initialRows, setInitialRows] = useState<BlockRow[]>([]);
	const [postTypes, setPostTypes] = useState<SlugOption[]>([]);
	const [roles, setRoles] = useState<SlugOption[]>([]);
	// Saved rules for blocks the table doesn't list (e.g. from a deactivated
	// plugin). Sent back on every save so the full-replace POST keeps them.
	const [orphanRules, setOrphanRules] = useState<Record<string, BlockRule>>({});
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [isDirty, setIsDirty] = useState(false);
	const [notice, setNotice] = useState<NoticeState | null>(null);
	const [view, setView] = useState<View>(DEFAULT_VIEW);

	useEffect(() => {
		async function fetchData() {
			try {
				const [catalog, settings] = await Promise.all([
					apiFetch<Catalog>({ path: BLOCKS_PATH }),
					apiFetch<PolicySettings>({ path: SETTINGS_PATH }),
				]);
				const loadedRows = catalogToRows(catalog, settings);
				setRows(loadedRows);
				setInitialRows(loadedRows);
				setPostTypes(catalog.postTypes);
				setRoles(catalog.roles ?? []);
				setOrphanRules(collectOrphanRules(catalog, settings));
			} catch (error) {
				setNotice({
					status: 'error',
					message: errorMessage(error, __('Failed to load blocks.', 'blockpolicy')),
				});
			} finally {
				setIsLoading(false);
			}
		}
		fetchData();
	}, []);

	// Warn before leaving with unsaved changes.
	useEffect(() => {
		const handler = (event: BeforeUnloadEvent) => {
			if (isDirty) {
				event.preventDefault();
			}
		};
		window.addEventListener('beforeunload', handler);
		return () => window.removeEventListener('beforeunload', handler);
	}, [isDirty]);

	const updateRows = useCallback((ids: string[], patch: RulePatch) => {
		const targets = new Set(ids);
		setRows(prevRows =>
			prevRows.map(row => (targets.has(row.id) ? applyPatch(row, patch) : row))
		);
		setIsDirty(true);
	}, []);

	const handleChange = useCallback(
		(id: string, patch: RulePatch) => updateRows([id], patch),
		[updateRows]
	);

	const handleSave = useCallback(async () => {
		setIsSaving(true);
		setNotice(null);
		try {
			const saved = await apiFetch<PolicySettings>({
				path: SETTINGS_PATH,
				method: 'POST',
				data: rowsToSettings(rows, orphanRules),
			});

			// Re-derive from the server's sanitized echo rather than trusting
			// local state, so anything PHP rejected shows up immediately.
			setRows(prevRows => reapplySettings(prevRows, saved));
			setIsDirty(false);
			setNotice({
				status: 'success',
				message: __('Settings saved.', 'blockpolicy'),
			});
		} catch (error) {
			setNotice({
				status: 'error',
				message: errorMessage(error, __('Failed to save settings.', 'blockpolicy')),
			});
		} finally {
			setIsSaving(false);
		}
	}, [rows, orphanRules]);

	const fields = useMemo(
		() => getFields({ rows: initialRows, postTypes, roles, onChange: handleChange }),
		[initialRows, postTypes, roles, handleChange]
	);

	const actions = useMemo<Action<BlockRow>[]>(() => {
		const ids = (items: BlockRow[]) => items.map(item => item.id);

		return [
			{
				id: 'hide',
				label: __('Hide from inserter', 'blockpolicy'),
				supportsBulk: true,
				isEligible: item => !item.protected && !item.hidden,
				callback: items => updateRows(ids(items), { hidden: true }),
			},
			{
				id: 'show',
				label: __('Show in inserter', 'blockpolicy'),
				supportsBulk: true,
				isEligible: item => !item.protected && item.hidden && !item.unregistered,
				callback: items => updateRows(ids(items), { hidden: false }),
			},
			{
				id: 'unregister',
				label: __('Unregister', 'blockpolicy'),
				supportsBulk: true,
				isEligible: item => !item.protected && !item.unregistered,
				callback: items => updateRows(ids(items), { unregistered: true }),
			},
			{
				id: 'register',
				label: __('Re-register', 'blockpolicy'),
				supportsBulk: true,
				isEligible: item => !item.protected && item.unregistered,
				callback: items => updateRows(ids(items), { unregistered: false }),
			},
			{
				id: 'reset',
				label: __('Reset to default', 'blockpolicy'),
				supportsBulk: true,
				isEligible: item => !item.protected && item.status !== 'allowed',
				callback: items =>
					updateRows(ids(items), {
						hidden: false,
						unregistered: false,
						postTypes: [],
						roles: [],
					}),
			},
		];
	}, [updateRows]);

	const { data: shownRows, paginationInfo } = useMemo(
		() => filterSortAndPaginate(rows, view, fields),
		[rows, view, fields]
	);

	const changedCount = useMemo(() => rows.filter(row => row.status !== 'allowed').length, [rows]);

	if (isLoading) {
		return (
			<div className="blockpolicy-page blockpolicy-page--loading">
				<Spinner />
				<span className="screen-reader-text">{__('Loading blocks…', 'blockpolicy')}</span>
			</div>
		);
	}

	return (
		<div className="blockpolicy-page">
			<div className="blockpolicy-page__header">
				<div>
					<h1>
						{__('BlockPolicy — Restrict Blocks by Role & Post Type', 'blockpolicy')}
					</h1>
					<p className="blockpolicy-page__summary">
						{sprintf(
							/* translators: 1: number of blocks with a policy, 2: total blocks. */
							_n(
								'%1$d of %2$d block has a policy.',
								'%1$d of %2$d blocks have a policy.',
								rows.length,
								'blockpolicy'
							),
							changedCount,
							rows.length
						)}
					</p>
				</div>
				<Button
					__next40pxDefaultSize
					variant="primary"
					onClick={handleSave}
					isBusy={isSaving}
					disabled={!isDirty || isSaving}
					accessibleWhenDisabled
				>
					{__('Save Changes', 'blockpolicy')}
				</Button>
			</div>

			{notice && (
				<Notice status={notice.status} isDismissible onDismiss={() => setNotice(null)}>
					{notice.message}
				</Notice>
			)}

			{rows.length === 0 ? (
				<Notice status="warning" isDismissible={false}>
					{__('No blocks found.', 'blockpolicy')}
				</Notice>
			) : (
				<DataViews
					data={shownRows}
					fields={fields}
					view={view}
					onChangeView={setView}
					paginationInfo={paginationInfo}
					actions={actions}
					defaultLayouts={{ table: {} }}
					getItemId={item => item.id}
				/>
			)}
		</div>
	);
}
