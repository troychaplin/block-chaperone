<img src="assets/icon-256x256.png" alt="Representation of content types with icons in square buttons" style="float: left; margin-right: 1.5em; height: auto; width: 128px;">

# Block Chaperone – Restrict Blocks by Role & Post Type

Control which blocks are available in the WordPress block editor, block by block, from one settings screen.

Go to **Settings → Block Chaperone** to see every top-level block registered on the site. For each one you can:

| Option | What it does |
|--------|--------------|
| **Hide from inserter** | The block can't be inserted. Existing instances keep rendering and can still be edited. |
| **Unregister** | The block can't be used in post content: it's hidden from the inserter, and existing instances show a removable “isn’t available” placeholder. Templates and previews still render it. |
| **Post types** | Where those restrictions apply. **All post types** by default; pick post types to restrict the block only there. |
| **Roles** | Who those restrictions apply to. **All roles** by default; pick roles to restrict only those users, and everyone else can use the block normally. |

Post types and roles read **No restrictions** until Hide or Unregister is turned on for that block.

The table can be searched and filtered by **status**, **category** and **source** (WordPress, or the plugin or theme that registered the block). Bulk actions let you hide, show, unregister, re-register or reset many blocks at once.

## How it works

- **Only top-level blocks are listed.** Blocks that declare a `parent` or `ancestor` (such as `core/list-item` or `core/column`) follow their parent: they stay available while the parent is allowed, and are removed when every parent is hidden.
- **Restrictions apply in the content editor only**: the post editor, where posts, pages and other post types are written. The Site Editor (templates, template parts, and pages opened from it), the widgets editor and the Customizer are left alone, so theme building isn't affected.
- **Hiding** uses the `allowed_block_types_all` filter. It runs late and only removes blocks, so a list already narrowed by a theme or another plugin is respected.
- **Unregister keeps the block registered.** Truly unregistering a block would remove it from the whole editor page, breaking templates that use it in the sidebar Template preview and with **Show template**. Instead, the post editor script replaces the block's editor with a placeholder wherever post content is edited: the whole canvas normally, or only inside the Post Content block with Show template on. Template parts and block previews render normally. The script only loads when the post type being edited has such blocks.
- **Post types** scope a block's restrictions: PHP applies them for the post type open in the post editor, both for hiding and for the blocks the editor script shows as unavailable.
- **Roles** scope a block's restrictions: they apply to users with at least one of the chosen roles. With no roles chosen they apply to everyone, including logged-out requests. Users with no role on the site, such as a multisite super admin who isn't a member, only get restrictions set to all roles.
- **`core/paragraph` is protected.** It's the default block, so it can't be hidden, unregistered or restricted.
- **The policy is a deny-list.** Only changed blocks are stored, so blocks from newly activated plugins are allowed by default. Rules for blocks or post types that aren't currently registered are kept, so deactivating a plugin doesn't erase its rules.

## Developer reference

### Filter: `block_chaperone_settings`

Add to or override the enforced policy in code. The result is sanitized with the same rules as saved settings.

```php
add_filter( 'block_chaperone_settings', function ( array $policy ): array {
	// Always unregister the Verse block.
	$policy['blocks']['core/verse'] = array( 'unregistered' => true );

	// Hide Cover, but only in posts.
	$policy['blocks']['core/cover'] = array(
		'hidden'    => true,
		'postTypes' => array( 'post' ),
	);

	// Hide the Custom HTML block for everyone except administrators.
	$policy['blocks']['core/html'] = array(
		'hidden' => true,
		'roles'  => array( 'editor', 'author', 'contributor' ),
	);

	return $policy;
} );
```

The settings page shows and saves only the stored policy. Rules added by this filter are enforced, but they don't appear on the settings page.

### Stored option

`block_chaperone_settings` (autoloaded):

```json
{
	"blocks": {
		"core/quote": { "hidden": true },
		"core/verse": { "hidden": true, "unregistered": true },
		"core/cover": { "hidden": true, "postTypes": ["post"] },
		"core/html": { "hidden": true, "roles": ["editor", "author"] }
	}
}
```

### REST API

All routes require the `manage_options` capability.

| Route | Method | Returns |
|-------|--------|---------|
| `/wp-json/block-chaperone/v1/blocks` | GET | `{ blocks, postTypes, roles }`: the top-level block catalog, and the post types and user roles a block's restrictions can be limited to. |
| `/wp-json/block-chaperone/v1/settings` | GET | The stored policy. |
| `/wp-json/block-chaperone/v1/settings` | POST | Replaces the stored policy and returns the sanitized result. |

## Known limitations

- **JavaScript-only blocks.** Blocks registered only in JavaScript (no PHP or `block.json` registration) aren't listed. While any block is hidden, they're also left out of the inserter. Core blocks and almost all modern plugins register on the server.
- **Unregistered blocks in existing content** show a placeholder in the editor and can't be edited, but their content is kept as-is and saved unchanged.
- **The front end is unaffected.** Hide and Unregister only affect the editor; saved and dynamic blocks still render on the site.
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
| `plugin.php` | Plugin header and bootstrap |
| `classes/class-plugin.php` | Creates and wires up the modules |
| `classes/class-block-catalog.php` | Top-level block list, sources, categories, post types |
| `classes/class-policy-store.php` | Reads, sanitizes and saves the option; `block_chaperone_settings` filter |
| `classes/class-rest-controller.php` | REST routes for the settings page |
| `classes/class-allowed-blocks.php` | `allowed_block_types_all` enforcement |
| `classes/class-enqueues.php` | Editor and settings scripts |
| `classes/class-settings-page.php` | Settings → Block Chaperone screen |
| `src/editor/` | Post editor script that shows unregistered blocks as a placeholder in post content |
| `src/settings/` | DataViews settings app (TypeScript) |
