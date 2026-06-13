# Analytics Chat for WordPress

Analytics Chat for WordPress is a read-only WordPress plugin that lets a Custom GPT query Independent Analytics data from a live production WordPress site.

The MVP does not require external infrastructure. Each site owner installs the plugin on their own WordPress site, generates an API key, and configures a Custom GPT Action that calls that site directly.

## What It Does

- Gives a GPT structured analytics data from WordPress.
- Uses Independent Analytics / Independent Analytics Pro where available.
- Returns compact JSON for site summaries, content performance, opportunities, referrers, campaigns, forms, and anonymised journeys.
- Keeps the GPT focused on interpretation and recommendations rather than dashboard recreation.
- Exposes only aggregated analytics data.

## Requirements

- WordPress 6.x+
- PHP 8.1+
- Independent Analytics active
- Independent Analytics Pro optional
- A ChatGPT account that can create GPTs with Actions

Pro-only datasets such as campaigns, forms, and journey patterns degrade gracefully when unavailable.

## Repository Layout

```text
analytics-chat-for-wordpress/  WordPress plugin
openapi/                       GPT Action schema for direct-site setup
docs/                          GPT setup, launch, privacy, and future architecture docs
bridge/                        Experimental future hosted bridge, not required for MVP
```

## Quick Setup

1. Install and activate Independent Analytics on the WordPress site.
2. Install and activate the plugin in `analytics-chat-for-wordpress/`.
3. In WordPress, go to Settings -> Analytics Chat.
4. Generate an API key and copy it immediately.
5. Open `openapi/analytics-chat-openapi.yaml`.
6. Replace `https://example.com` with the production site URL.
7. Create a Custom GPT in ChatGPT.
8. Add an Action.
9. Paste the OpenAPI schema.
10. Configure API key authentication as Bearer token.
11. Add the GPT instructions from `docs/gpt-instructions.md`.
12. Test with: "Give me a site summary for the last 30 days."

OpenAI's current help for GPT Actions states that actions are configured with authentication and an OpenAPI schema, and public GPTs with actions require a valid privacy policy URL:

- https://help.openai.com/en/articles/9442513-gpt-actions-authentication
- https://help.openai.com/en/articles/8554397-creating-a-gpt

## Direct-Site GPT Model

This release is a setup kit, not a central SaaS.

```text
Custom GPT
  -> GPT Action
  -> https://your-site.com/wp-json/acfw/v1
  -> Analytics Chat for WordPress plugin
  -> Independent Analytics data
```

This means:

- No hosted bridge is required.
- The GPT Action server URL is the site owner's own WordPress URL.
- The site owner controls the API key.
- A single GPT cannot automatically connect to every user's separate WordPress site unless a hosted bridge or OAuth service is added later.

## REST Endpoints

All endpoints are under:

```text
/wp-json/acfw/v1
```

Send:

```text
Authorization: Bearer {api_key}
```

Endpoints:

- `GET /site-summary`
- `GET /top-content`
- `GET /content-performance`
- `GET /content-opportunities`
- `GET /referrers`
- `GET /campaigns`
- `GET /forms`
- `GET /user-journey`

## Privacy and Security

- The plugin is read-only.
- API keys are stored hashed.
- The full API key is displayed only once when generated.
- REST requests without a bearer token return `401`.
- REST requests with an invalid key return `403`.
- Raw IP addresses, visitor fingerprints, visitor-level browsing history, and WordPress user identities are not exposed.
- Requests are capped by date range and result count.
- Responses are aggregated for GPT interpretation.

## Distribution Notes

For public use in ChatGPT without extra infrastructure, distribute this as:

1. A WordPress plugin.
2. A GPT instruction template.
3. An OpenAPI Action schema template.
4. A setup guide for replacing the server URL and configuring the site's API key.
5. A privacy policy URL for the GPT Action.

The future hosted bridge architecture is documented in `docs/public-gpt-bridge-architecture.md`, but it is not required for the current no-infrastructure release.

## GitHub Updates

The plugin includes a GitHub release updater from version `0.1.1` onward.

To publish an update that WordPress can install:

1. Bump the plugin version in `analytics-chat-for-wordpress.php`.
2. Build a ZIP named `analytics-chat-for-wordpress.zip`:

```bash
bash scripts/build-plugin-zip.sh
```

3. The ZIP must contain the plugin folder as its top-level directory:

```text
analytics-chat-for-wordpress/
  analytics-chat-for-wordpress.php
  includes/
  admin/
  openapi/
```

4. Create a GitHub Release using a tag such as `v0.1.2`.
5. Attach `analytics-chat-for-wordpress.zip` to the release.

WordPress checks the latest GitHub release and only offers an update when the release tag is newer than the installed plugin version and the release includes the ZIP asset.
