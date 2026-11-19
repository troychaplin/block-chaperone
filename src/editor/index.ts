/**
 * Block editor script.
 *
 * Only enqueued when there is something to do. Data comes from
 * `window.blockPolicy`, printed by `Block_Policy\Enqueues`. Hiding blocks from
 * the inserter is otherwise handled in PHP via `allowed_block_types_all`.
 */
import domReady from '@wordpress/dom-ready';

import { applySiteEditorRestrictions } from './site-editor-restrictions';
import './types';
import { unregisterBlocks } from './unregister';

const { unregistered = [], restricted = {}, siteEditorAllowed = true } = window.blockPolicy ?? {};

domReady(() => {
	if (unregistered.length > 0) {
		unregisterBlocks(unregistered);
	}

	if (Object.keys(restricted).length > 0) {
		applySiteEditorRestrictions(restricted, siteEditorAllowed);
	}
});
