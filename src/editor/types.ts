/**
 * Data printed by `BlockChaperone\Enqueues` as `window.blockChaperone`.
 */
export interface EditorPolicyData {
	/** Blocks unavailable in post content on this post editor screen. */
	unregistered?: string[];
}

declare global {
	interface Window {
		blockChaperone?: EditorPolicyData;
	}
}
