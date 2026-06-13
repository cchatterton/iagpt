# Changelog

All notable changes to Analytics Chat for WordPress are recorded here.

## 0.1.4 - 2026-06-13

- Simplified API key management so admins see either generate or revoke, never both.
- Removed GitHub update checking from the Analytics Chat settings page.
- Added plugin header links for AlphaSys and the GitHub repository.
- Added a GitHub link to the WordPress Plugins page row metadata.

## 0.1.3 - 2026-06-13

- Added a Plugins page action link to manually check GitHub for updates.
- Added a throttled update check when the WordPress Plugins page loads.
- Added an admin notice after manual GitHub update checks.
- Kept version and release notes in sync with the WordPress Plugin Build Standard.

## 0.1.2 - 2026-06-13

- Applied the WordPress Plugin Build Standard where it fits the existing plugin specification.
- Changed plugin author declaration to AlphaSys.
- Replaced REST `__return_true` route permissions with a named authentication permission callback.
- Added structured release notes in this changelog.
- Updated plugin readme metadata, folder notes, important notes, and future considerations.

## 0.1.1 - 2026-06-13

- Added GitHub release update checks.
- Added an Updates section to the WordPress settings page.
- Added a release ZIP build script.
- Fixed settings redirects after API key actions.

## 0.1.0 - 2026-06-13

- Initial MVP plugin scaffold.
- Added read-only REST endpoints for Independent Analytics data.
- Added API key generation, revocation, and bearer-token authentication.
- Added settings page and diagnostics.
- Added GPT Action OpenAPI schema and setup documentation.
