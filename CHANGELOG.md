# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Prefix the change with one of these keywords:

-   _Added_: for new features.
-   _Changed_: for changes in existing functionality.
-   _Deprecated_: for soon-to-be removed features.
-   _Removed_: for now removed features.
-   _Fixed_: for any bug fixes.
-   _Security_: in case of vulnerabilities.

## [Unreleased]

### Added

-   Settings → Block Policy screen: a DataViews table of every top-level block, with search, status / category / source filters and bulk actions.
-   Per-block "Hide from inserter", "Unregister" and post type restriction options.
-   Per-block Roles option to limit a block's restrictions to selected user roles (all roles by default).
-   `allowed_block_types_all` enforcement that keeps children of allowed parents and respects lists set by themes or other plugins.
-   Editor script that unregisters blocks, including blocks registered after page load.
-   Post type restrictions in the Site Editor when it edits a page or post.
-   `block_policy_settings` filter for adding or overriding rules in code.
-   REST routes `block-policy/v1/blocks` and `block-policy/v1/settings`.
-   Uninstall removes the `block_policy_settings` option (all sites on multisite).
