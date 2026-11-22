/**
 * Data printed by `BlockPolicy\Enqueues` as `window.blockPolicy`.
 */
export interface EditorPolicyData {
	/** Blocks unavailable in post content on this post editor screen. */
	unregistered?: string[];
}

declare global {
	interface Window {
		blockPolicy?: EditorPolicyData;
	}
}
