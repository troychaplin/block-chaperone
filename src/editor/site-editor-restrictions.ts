/**
 * Applies post type restrictions inside the Site Editor.
 *
 * In the post editor, PHP removes restricted blocks via
 * `allowed_block_types_all`. The Site Editor gives that filter no post, so
 * when it switches to editing a post or page, this narrows the editor's
 * `allowedBlockTypes` setting for that post type, and restores the original
 * value when it switches back to a template.
 */
import { getBlockTypes } from '@wordpress/blocks';
import { dispatch, select, subscribe } from '@wordpress/data';

import type { AllowedBlockTypes } from './types';

// Referenced by name so this script doesn't add a `wp-editor` dependency to
// every block editor screen (the widgets editor doesn't load it).
const EDITOR_STORE = 'core/editor';

interface EditorSelectors {
	getCurrentPostType: () => string | null;
	getEditorSettings: () => { allowedBlockTypes?: AllowedBlockTypes };
}

interface EditorActions {
	updateEditorSettings: (settings: { allowedBlockTypes: AllowedBlockTypes }) => void;
}

/**
 * Whether two `allowedBlockTypes` values allow the same blocks.
 *
 * @param a First value.
 * @param b Second value.
 * @return Whether they match.
 */
function sameAllowed(a: AllowedBlockTypes, b: AllowedBlockTypes): boolean {
	if (Array.isArray(a) && Array.isArray(b)) {
		if (a.length !== b.length) {
			return false;
		}
		const set = new Set(a);
		return b.every(name => set.has(name));
	}
	return a === b;
}

/**
 * Add child blocks whose every parent (or, failing that, every ancestor) is
 * denied, repeating so grandchildren follow. Mirrors the PHP side.
 *
 * @param denied Denied block names.
 * @return Denied names including orphaned children.
 */
function withOrphanedChildren(denied: Set<string>): Set<string> {
	const blockTypes = getBlockTypes();
	let added = true;

	while (added) {
		added = false;
		for (const blockType of blockTypes) {
			if (denied.has(blockType.name)) {
				continue;
			}
			const containers: string[] = blockType.parent?.length
				? blockType.parent
				: (blockType.ancestor ?? []);
			if (containers.length > 0 && containers.every(name => denied.has(name))) {
				denied.add(blockType.name);
				added = true;
			}
		}
	}

	return denied;
}

/**
 * Watch the Site Editor and keep `allowedBlockTypes` in line with the post
 * type being edited.
 *
 * Only intervenes while a post type with restrictions is being edited, and
 * only restores values it set itself, so templates and unrestricted post
 * types keep whatever the Site Editor provides.
 *
 * @param restricted Post type => blocks hidden from the inserter there.
 * @param base       The Site Editor's server-side allowed list (hidden blocks
 *                   already removed).
 */
export function applySiteEditorRestrictions(
	restricted: Record<string, string[]>,
	base: boolean | string[]
) {
	// Narrowed lists, cached per post type and number of registered blocks.
	const cache = new Map<string, string[]>();
	// The last list this script applied, so it knows what to undo.
	let applied: string[] | null = null;

	const narrowedFor = (postType: string | null): string[] | null => {
		const rules = postType ? restricted[postType] : undefined;

		if (!rules?.length || base === false) {
			return null;
		}

		const blockNames = getBlockTypes().map(blockType => blockType.name);
		const key = `${postType}:${blockNames.length}`;
		const cached = cache.get(key);
		if (cached) {
			return cached;
		}

		const denied = withOrphanedChildren(new Set(rules));
		const names = Array.isArray(base) ? base : blockNames;
		const narrowed = names.filter(name => !denied.has(name));

		cache.set(key, narrowed);
		return narrowed;
	};

	const update = (allowedBlockTypes: AllowedBlockTypes) =>
		(dispatch(EDITOR_STORE) as unknown as EditorActions).updateEditorSettings({
			allowedBlockTypes,
		});

	subscribe(() => {
		const editor = select(EDITOR_STORE) as unknown as EditorSelectors | undefined;
		if (!editor) {
			return;
		}

		const current = editor.getEditorSettings().allowedBlockTypes;
		const narrowed = narrowedFor(editor.getCurrentPostType());

		if (narrowed) {
			// Also re-applies if the Site Editor resets its settings on navigation.
			if (!sameAllowed(current, narrowed)) {
				applied = narrowed;
				update(narrowed);
			}
			return;
		}

		// Left a restricted post type: put back the Site Editor's own list, but
		// only if the setting still holds what this script applied.
		if (applied) {
			if (sameAllowed(current, applied)) {
				update(base);
			}
			applied = null;
		}
	}, EDITOR_STORE);
}
