/**
 * Unregisters blocks the policy marks as unregistered.
 */
import {
	getBlockType,
	getDefaultBlockName,
	store as blocksStore,
	unregisterBlockType,
} from '@wordpress/blocks';
import { subscribe } from '@wordpress/data';

/**
 * Unregister the given blocks now, and keep watching for any of them that a
 * plugin registers later.
 *
 * @param names Block names to unregister.
 */
export function unregisterBlocks(names: string[]) {
	const pending = new Set(names);

	const unregisterPending = () => {
		for (const name of pending) {
			if (!getBlockType(name)) {
				continue;
			}

			// Never remove the default block; typing and the appender depend on it.
			if (name !== getDefaultBlockName()) {
				unregisterBlockType(name);
			}
			pending.delete(name);
		}
	};

	unregisterPending();

	// Blocks that never register (e.g. their plugin is inactive) keep the
	// listener alive, but the check is a cheap lookup per listed name.
	if (pending.size > 0) {
		const unsubscribe = subscribe(() => {
			unregisterPending();
			if (pending.size === 0) {
				unsubscribe();
			}
		}, blocksStore);
	}
}
