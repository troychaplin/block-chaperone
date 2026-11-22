/**
 * Block editor script for the post editor.
 *
 * Blocks listed in `window.blockPolicy.unregistered` stay registered (so
 * templates and previews render normally) but show a removable placeholder
 * wherever post content is edited. Hiding them from the inserter is handled
 * in PHP via `allowed_block_types_all`. Only enqueued when the post type being
 * edited has such blocks (see `BlockPolicy\Enqueues`).
 */
import { registerContentPlaceholder } from './content-placeholder';
import './types';

const { unregistered = [] } = window.blockPolicy ?? {};

if (unregistered.length > 0) {
	registerContentPlaceholder(unregistered);
}
