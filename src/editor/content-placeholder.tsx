/**
 * Makes "unregistered" blocks unavailable in post content without removing
 * them from the block registry.
 *
 * Truly unregistering a block (`unregisterBlockType`) affects the whole
 * editor page, which breaks templates that use it: the sidebar Template
 * preview and "Show template" render through the same registry. Instead, the
 * block stays registered and, only where post content is edited, its
 * instances are replaced by a removable placeholder. Template parts and block
 * previews render normally. Insertion is already prevented in PHP, because
 * unregistered blocks are also hidden.
 */
import { Warning, store as blockEditorStore, useBlockProps } from '@wordpress/block-editor';
import { getBlockType } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import type { ComponentType } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

// Referenced by name so the script doesn't add a `wp-editor` dependency. It
// only loads in the post editor, where this store always exists.
const EDITOR_STORE = 'core/editor';

interface BlockEditProps {
	name: string;
	clientId: string;
	[key: string]: unknown;
}

interface PlaceholderGuardProps extends BlockEditProps {
	BlockEdit: ComponentType<BlockEditProps>;
}

/**
 * Whether a block sits in the area where post content is edited.
 *
 * - Block previews (the sidebar Template preview, pattern previews) never are.
 * - In "post-only" rendering the whole canvas is the post.
 * - With "Show template" ("template-locked"), only blocks inside the Post
 *   Content block are; template parts are not.
 *
 * @param clientId Block client ID.
 * @return Whether the block is in post content.
 */
function useIsInPostContent(clientId: string): boolean {
	return useSelect(
		select => {
			const blockEditor = select(blockEditorStore);

			if ((blockEditor.getSettings() as { isPreviewMode?: boolean }).isPreviewMode) {
				return false;
			}

			const editor = select(EDITOR_STORE) as { getRenderingMode?: () => string } | undefined;
			const mode = editor?.getRenderingMode?.() ?? 'post-only';

			if (mode === 'post-only') {
				return true;
			}

			return blockEditor.getBlockParentsByBlockName(clientId, 'core/post-content').length > 0;
		},
		[clientId]
	);
}

/**
 * The placeholder itself. Kept separate so `useBlockProps()` only runs when
 * it replaces the block's own editor, never alongside it.
 *
 * @param props          Component props.
 * @param props.name     Block name.
 * @param props.clientId Block client ID.
 */
function ContentPlaceholder({ name, clientId }: { name: string; clientId: string }) {
	const { removeBlock } = useDispatch(blockEditorStore);
	const blockProps = useBlockProps();
	const title = getBlockType(name)?.title ?? name;

	return (
		<div {...blockProps}>
			<Warning
				actions={[
					<Button
						key="remove"
						__next40pxDefaultSize
						variant="primary"
						onClick={() => removeBlock(clientId)}
					>
						{__('Remove block', 'blockpolicy')}
					</Button>,
				]}
			>
				{sprintf(
					/* translators: %s: block title. */
					__(
						'The %s block isn’t available here. It will keep displaying on your site, but it can’t be edited.',
						'blockpolicy'
					),
					title
				)}
			</Warning>
		</div>
	);
}

/**
 * Shows the placeholder inside post content, and the block's own editor
 * everywhere else (template parts, previews).
 *
 * @param props           Block edit props plus the original BlockEdit.
 * @param props.BlockEdit The block's original edit component.
 */
function PlaceholderGuard({ BlockEdit, ...props }: PlaceholderGuardProps) {
	const inPostContent = useIsInPostContent(props.clientId);

	return inPostContent ? (
		<ContentPlaceholder name={props.name} clientId={props.clientId} />
	) : (
		<BlockEdit {...props} />
	);
}

/**
 * Register the placeholder for the given blocks.
 *
 * @param names Block names that are unavailable in post content.
 */
export function registerContentPlaceholder(names: string[]) {
	const listed = new Set(names);

	addFilter(
		'editor.BlockEdit',
		'blockpolicy/content-placeholder',
		createHigherOrderComponent(
			(BlockEdit: ComponentType<BlockEditProps>) => (props: BlockEditProps) =>
				listed.has(props.name) ? (
					<PlaceholderGuard {...props} BlockEdit={BlockEdit} />
				) : (
					<BlockEdit {...props} />
				),
			'withBlockPolicyContentPlaceholder'
		)
	);
}
