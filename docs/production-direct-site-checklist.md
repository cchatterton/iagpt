# Production Direct-Site Checklist

Use this checklist before sharing a GPT that connects directly to a live WordPress production site.

## WordPress

- Independent Analytics is installed and collecting data.
- Analytics Chat for WordPress is installed and active.
- The site uses HTTPS.
- Pretty permalinks and REST API access are working.
- Settings -> Analytics Chat shows Independent Analytics as available.
- Maximum period and result limits are acceptable.
- Settings -> Analytics Chat shows the public REST base URL.

## REST API

- Public requests return `200` or a clear unavailable/error response.
- `/wp-json/acfw/v1/site-summary?period=30d` returns structured JSON.
- `/wp-json/acfw/v1/top-content?period=30d&limit=5` returns rows or a clear unavailable response.
- `/wp-json/acfw/v1/content-opportunities?period=30d&limit=5` returns prioritised recommendations or a clear unavailable response.
- Pro-only endpoints return graceful unavailable responses when Pro data is unavailable.

## GPT Builder

- The OpenAPI schema server URL has been changed from `https://example.com` to the production site URL.
- Authentication is set to none.
- The privacy policy URL is configured.
- The GPT instructions from `docs/gpt-instructions.md` are added.
- Preview tests succeed.

## Public Sharing

- The GPT name and description are clear.
- Conversation starters are analytics-specific.
- The privacy policy URL is public and accurate.
- The GPT has been tested with unavailable data cases.
- The GPT does not ask users for API keys in chat.
- The GPT is clear that a site must have the plugin installed for analytics calls to work.
