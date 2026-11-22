/**
 * Conversions between the REST shapes and the flat rows DataViews renders.
 */
import type {
	BlockRow,
	BlockRule,
	Catalog,
	CatalogBlock,
	PolicySettings,
	PolicyStatus,
	RulePatch,
} from '../types';

/**
 * Most restrictive state wins: unregistered > hidden > allowed. Post types and
 * roles only scope these, so they don't change the status.
 *
 * @param rule Editable state of a row.
 * @return The row's status.
 */
export function deriveStatus(
	rule: Pick<Required<RulePatch>, 'hidden' | 'unregistered'>
): PolicyStatus {
	if (rule.unregistered) {
		return 'unregistered';
	}
	if (rule.hidden) {
		return 'hidden';
	}
	return 'allowed';
}

/**
 * Build a row from a catalog block and its stored rule.
 *
 * @param block Catalog block.
 * @param rule  Stored rule, if any.
 * @return Table row.
 */
function toRow(block: CatalogBlock, rule: BlockRule = {}): BlockRow {
	const state = {
		unregistered: !!rule.unregistered,
		// Unregistering a block always hides it too, so PHP and JS agree.
		hidden: !!rule.unregistered || !!rule.hidden,
		postTypes: rule.postTypes ?? [],
		roles: rule.roles ?? [],
	};

	return {
		...block,
		...state,
		id: block.name,
		status: deriveStatus(state),
	};
}

/**
 * One row per catalog block, with its saved rule applied.
 *
 * @param catalog  Catalog response.
 * @param settings Settings response.
 * @return Table rows.
 */
export function catalogToRows(catalog: Catalog, settings: PolicySettings): BlockRow[] {
	return catalog.blocks.map(block => toRow(block, settings.blocks?.[block.name]));
}

/**
 * Apply an edit to a row, keeping derived state consistent.
 *
 * @param row   Row to update.
 * @param patch Changed fields.
 * @return Updated row (the same object if the row is protected).
 */
export function applyPatch(row: BlockRow, patch: RulePatch): BlockRow {
	if (row.protected) {
		return row;
	}

	const state = {
		hidden: patch.hidden ?? row.hidden,
		unregistered: patch.unregistered ?? row.unregistered,
		postTypes: patch.postTypes ?? row.postTypes,
		roles: patch.roles ?? row.roles,
	};

	if (state.unregistered) {
		// Unregistering always hides the block too.
		state.hidden = true;
	} else if (row.unregistered && patch.unregistered === false) {
		// Turning Unregister off clears the Hide it implied, returning the block
		// to fully allowed (unless this same patch sets `hidden` explicitly).
		state.hidden = patch.hidden ?? false;
	}

	return { ...row, ...state, status: deriveStatus(state) };
}

/**
 * Re-derive every row's editable state from a settings object. Used after
 * saving so anything PHP rejected or normalised shows up straight away.
 *
 * @param rows     Current rows.
 * @param settings Settings returned by the server.
 * @return Updated rows.
 */
export function reapplySettings(rows: BlockRow[], settings: PolicySettings): BlockRow[] {
	return rows.map(row => toRow(row, settings.blocks?.[row.name]));
}

/**
 * Stored rules for blocks that aren't in the catalog (e.g. from a deactivated
 * plugin). POST replaces the whole option, so these must be sent back
 * untouched or they'd be erased on the next save.
 *
 * @param catalog  Catalog response.
 * @param settings Settings response.
 * @return Rules keyed by block name.
 */
export function collectOrphanRules(
	catalog: Catalog,
	settings: PolicySettings
): Record<string, BlockRule> {
	const listed = new Set(catalog.blocks.map(block => block.name));

	return Object.fromEntries(
		Object.entries(settings.blocks ?? {}).filter(([name]) => !listed.has(name))
	);
}

/**
 * Convert rows back into the stored shape. Only non-default values are kept.
 *
 * @param rows    Table rows.
 * @param orphans Rules for blocks not in the table, from collectOrphanRules().
 * @return Settings for POST /blockpolicy/v1/settings.
 */
export function rowsToSettings(
	rows: BlockRow[],
	orphans: Record<string, BlockRule> = {}
): PolicySettings {
	const blocks: Record<string, BlockRule> = { ...orphans };

	for (const row of rows) {
		if (row.protected) {
			continue;
		}

		// Post types and roles only scope a restriction, so a block with
		// neither toggle on has nothing to store.
		if (!row.hidden && !row.unregistered) {
			continue;
		}

		const rule: BlockRule = { hidden: true };

		if (row.unregistered) {
			rule.unregistered = true;
		}
		if (row.postTypes.length > 0) {
			rule.postTypes = row.postTypes;
		}
		if (row.roles.length > 0) {
			rule.roles = row.roles;
		}

		blocks[row.name] = rule;
	}

	return { blocks };
}
