/**
 * Shapes shared by the settings app. These mirror the REST responses from
 * `BlockPolicy\Rest_Controller`.
 */

export type SourceType = 'core' | 'plugin' | 'theme' | 'unknown';

/** One top-level block, from GET /blockpolicy/v1/blocks. */
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

/** A selectable slug with a display label (post type or role). */
export interface SlugOption {
	value: string;
	label: string;
}

/** GET /blockpolicy/v1/blocks. */
export interface Catalog {
	blocks: CatalogBlock[];
	postTypes: SlugOption[];
	roles: SlugOption[];
}

/** A stored rule. Missing keys mean the default (allowed). */
export interface BlockRule {
	hidden?: boolean;
	unregistered?: boolean;
	postTypes?: string[];
	/** Roles the rule applies to; missing or empty means everyone. */
	roles?: string[];
}

/** GET/POST /blockpolicy/v1/settings. */
export interface PolicySettings {
	blocks: Record<string, BlockRule>;
}

export type PolicyStatus = 'allowed' | 'restricted' | 'hidden' | 'unregistered';

/** The editable part of a row. */
export interface RulePatch {
	hidden?: boolean;
	unregistered?: boolean;
	postTypes?: string[];
	roles?: string[];
}

/** A row in the DataViews table. */
export interface BlockRow extends CatalogBlock {
	id: string;
	hidden: boolean;
	unregistered: boolean;
	postTypes: string[];
	roles: string[];
	status: PolicyStatus;
}
