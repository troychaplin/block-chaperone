<img src="assets/icon-256x256.png" alt="Representation of content types with icons in square buttons" style="float: left; margin-right: 1.5em; height: auto; width: 128px;">

# Block Policy

Control which blocks are available in the WordPress block editor, block by block, from one settings screen.

Go to **Settings → Block Policy** to see every top-level block registered on the site. For each one you can:

| Option | What it does |
|--------|--------------|
| **Hide from inserter** | The block can't be inserted anywhere. Existing instances keep rendering and can still be edited. |
| **Unregister** | The block is removed from the editor entirely. This also hides it. |
| **Post types** | The block is hidden from the inserter only in the post types you pick. |

The table can be searched and filtered by **status**, **category** and **source** (WordPress, or the plugin or theme that registered the block). Bulk actions let you hide, show, unregister, re-register or reset many blocks at once.

## How it works

- **Only top-level blocks are listed.** Blocks that declare a `parent` or `ancestor` (such as `core/list-item` or `core/column`) follow their parent: they stay available while the parent is allowed, and are removed when every parent is hidden.
- **Hiding** uses the `allowed_block_types_all` filter. It runs late and only removes blocks, so a list already narrowed by a theme or another plugin is respected.
- **Unregistering** happens in the editor with `unregisterBlockType()`. The editor script only loads when there's something for it to do, and it also catches blocks registered after the page loads.
- **Post type rules** apply in the post editor through the same filter. The Site Editor doesn't tell the filter which post is being edited, so there the editor script narrows the allowed list whenever a restricted post type (such as a page) is open, and restores it for templates.
- **`core/paragraph` is protected.** It's the default block, so it can't be hidden, unregistered or restricted.
- **The policy is a deny-list.** Only changed blocks are stored, so blocks from newly activated plugins are allowed by default. Rules for blocks or post types that aren't currently registered are kept, so deactivating a plugin doesn't erase its rules.

## Developer reference

### Filter: `block_policy_settings`

Add to or override the enforced policy in code. The result is sanitized with the same rules as saved settings.

```php
add_filter( 'block_policy_settings', function ( array $policy ): array {
	// Always unregister the Verse block.
	$policy['blocks']['core/verse'] = array( 'unregistered' => true );

	// Hide Cover in posts, keeping any post types set in the UI.
	$post_types = $policy['blocks']['core/cover']['postTypes'] ?? array();
	$policy['blocks']['core/cover']['postTypes'] = array_unique( array_merge( $post_types, array( 'post' ) ) );

	return $policy;
} );
```

The settings page shows and saves only the stored policy. Rules added by this filter are enforced, but they don't appear on the settings page.

### Stored option

`block_policy_settings` (autoloaded):

```json
{
	"blocks": {
		"core/quote": { "hidden": true },
		"core/verse": { "hidden": true, "unregistered": true },
		"core/cover": { "postTypes": ["post"] }
	}
}
```

### REST API

All routes require the `manage_options` capability.

| Route | Method | Returns |
|-------|--------|---------|
| `/wp-json/block-policy/v1/blocks` | GET | `{ blocks, postTypes }`: the top-level block catalog and the post types a block can be restricted in. |
| `/wp-json/block-policy/v1/settings` | GET | The stored policy. |
| `/wp-json/block-policy/v1/settings` | POST | Replaces the stored policy and returns the sanitized result. |

## Known limitations

- **JavaScript-only blocks.** Blocks registered only in JavaScript (no PHP or `block.json` registration) aren't listed. While any block is hidden, they're also left out of the inserter. Core blocks and almost all modern plugins register on the server.
- **Unregistered blocks in existing content** appear in the editor as "unsupported block" placeholders.
- **The front end is unaffected.** Unregistering only affects the editor; saved and dynamic blocks still render on the site.
- **Missing categories.** Some plugins set a block's category only in JavaScript, so it shows as "Uncategorized" (e.g. Gravity Forms).

## Development

```bash
npm install
composer install
npm run start   # watch and rebuild
npm run build   # production build
npm run lint    # JS + PHP + CSS lint
```

Local environment via [@wordpress/env](https://github.com/WordPress/gutenberg/tree/HEAD/packages/env#readme): `wp-env start` (admin / password).

### Structure

| Path | Purpose |
|------|---------|
| `block-policy.php` | Plugin header and bootstrap |
| `classes/class-plugin.php` | Creates and wires up the modules |
| `classes/class-block-catalog.php` | Top-level block list, sources, categories, post types |
| `classes/class-policy-store.php` | Reads, sanitizes and saves the option; `block_policy_settings` filter |
| `classes/class-rest-controller.php` | REST routes for the settings page |
| `classes/class-allowed-blocks.php` | `allowed_block_types_all` enforcement |
| `classes/class-enqueues.php` | Editor and settings scripts |
| `classes/class-settings-page.php` | Settings → Block Policy screen |
| `src/editor/` | Editor script: unregisters blocks, and applies post type rules in the Site Editor |
| `src/settings/` | DataViews settings app (TypeScript) |
