/**
 * Shapes shared by the settings app. These mirror the REST responses from
 * `Block_Policy\Rest_Controller`.
 */

export type SourceType = 'core' | 'plugin' | 'theme' | 'unknown';

/** One top-level block, from GET /block-policy/v1/blocks. */
export interface CatalogBlock {
	name: string;
	title: string;
	description: string;
	category: string;
	categoryLabel: string;
	source: string;
	sourceType: SourceType;
	icon: string | null;
	protected: boolean;
}

export interface PostTypeOption {
	value: string;
	label: string;
}

/** GET /block-policy/v1/blocks. */
export interface Catalog {
	blocks: CatalogBlock[];
	postTypes: PostTypeOption[];
}

/** A stored rule. Missing keys mean the default (allowed). */
export interface BlockRule {
	hidden?: boolean;
	unregistered?: boolean;
	postTypes?: string[];
}

/** GET/POST /block-policy/v1/settings. */
export interface PolicySettings {
	blocks: Record<string, BlockRule>;
}

export type PolicyStatus = 'allowed' | 'restricted' | 'hidden' | 'unregistered';

/** The editable part of a row. */
export interface RulePatch {
	hidden?: boolean;
	unregistered?: boolean;
	postTypes?: string[];
}

/** A row in the DataViews table. */
export interface BlockRow extends CatalogBlock {
	id: string;
	hidden: boolean;
	unregistered: boolean;
	postTypes: string[];
	status: PolicyStatus;
}
