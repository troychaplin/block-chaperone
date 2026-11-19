/**
 * Data printed by `Block_Policy\Enqueues` as `window.blockPolicy`.
 */
export interface EditorPolicyData {
	/** Blocks to unregister. */
	unregistered?: string[];
	/** Site Editor only: post type => blocks hidden from the inserter there. */
	restricted?: Record<string, string[]>;
	/** Site Editor only: its server-side allowed list, sent with `restricted`. */
	siteEditorAllowed?: boolean | string[];
}

declare global {
	interface Window {
		blockPolicy?: EditorPolicyData;
	}
}

/** Value of the editor's `allowedBlockTypes` setting. */
export type AllowedBlockTypes = boolean | string[] | undefined;
