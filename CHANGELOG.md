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

-   Settings → BlockPolicy screen: a DataViews table of every top-level block, with search, status / category / source filters and bulk actions.
-   Per-block "Hide from inserter" and "Unregister" options.
-   Per-block Post types and Roles options to limit those restrictions to selected post types and user roles (all by default).
-   `allowed_block_types_all` enforcement that keeps children of allowed parents and respects lists set by themes or other plugins.
-   Editor script that unregisters blocks, including blocks registered after page load.
-   Post type scopes in the Site Editor when it edits a page or post.
-   `blockpolicy_settings` filter for adding or overriding rules in code.
-   REST routes `blockpolicy/v1/blocks` and `blockpolicy/v1/settings`.
-   Uninstall removes the `blockpolicy_settings` option (all sites on multisite).
