=== BlockPolicy – Restrict Blocks by Role & Post Type ===

Contributors: areziaal
Tags: blocks, gutenberg, block editor, allowed blocks, inserter
Requires at least: 6.6
Requires PHP: 8.0
Tested up to: 7.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Control which blocks are available in the editor, block by block: hide them, unregister them, or restrict them by post type and user role.

== Description ==

BlockPolicy adds a settings screen that lists every top-level block on your site — core blocks and blocks from plugins and themes — with these options per block:

* **Hide from inserter** — the block can't be added, but existing content keeps working.
* **Unregister** — the block is removed from the editor entirely.
* **Post types** — choose where those restrictions apply. By default they apply to all post types; pick post types to restrict the block only there, for example hide List in Posts but keep it in Pages.
* **Roles** — choose who those restrictions apply to. By default they apply to everyone; pick roles to restrict only those users, for example hide Custom HTML from Editors and Authors while Administrators keep it.

Search the list, filter it by status, category or source plugin, and change many blocks at once with bulk actions.

= How it behaves =

* Child blocks (like List items or Columns) follow their parent automatically, so they don't clutter the list.
* New blocks from newly activated plugins are allowed by default. Only the blocks you change are stored.
* The Paragraph block is protected, because the editor depends on it.
* If your theme or another plugin already limits the available blocks, BlockPolicy only removes from that list. It never adds blocks back.

= For developers =

Use the `blockpolicy_settings` filter to add or override rules in code. A REST API at `blockpolicy/v1` powers the settings screen. See the README on GitHub for details.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install it from the Plugins screen.
2. Activate it.
3. Go to Settings → BlockPolicy.

== Frequently Asked Questions ==

= What happens to content that uses a hidden block? =

Nothing. Hidden blocks keep rendering on the site and can still be edited. They just can't be inserted again.

= What happens to content that uses an unregistered block? =

On the site, it keeps rendering. In the editor, it shows as an unsupported block placeholder.

= Do post type restrictions apply in the Site Editor? =

Yes. When you edit a page or post from the Site Editor, restrictions limited to its post type apply. Templates and template parts only get restrictions set to all post types. A block unregistered only for certain post types is hidden instead of unregistered while you edit those posts in the Site Editor.

= Why isn't a block listed? =

Only top-level blocks that can be inserted are listed. Blocks registered only in JavaScript, without a PHP or block.json registration, can't be detected.

== Changelog ==

= 0.1.0 =
* Initial release: settings screen with per-block hide, unregister, post type and role restrictions; category, source and status filters; bulk actions; `blockpolicy_settings` filter.
