# Public GPT Bridge Architecture

This document defines the next product step for making Analytics Chat for WordPress usable as a public/multi-site GPT inside ChatGPT.

The current WordPress plugin is a single-site analytics adapter. A public GPT needs a hosted bridge between ChatGPT and each user's connected WordPress sites.

## Product Goal

Let a user open a public GPT and work naturally across one or more connected WordPress sites:

1. The GPT lists the user's connected websites.
2. The user chooses a website focus.
3. The GPT answers analytics questions for that focused website.
4. The user can connect a new site from the conversation.
5. The user can switch focus between sites.

The GPT should not require the user to paste WordPress API keys into ChatGPT.

## Core Conversation Flow

### First Run

User:

```text
Show me my websites.
```

GPT:

1. Calls `listWebsites`.
2. If sites exist, returns a numbered list.
3. If no sites exist, offers to connect a new site.

Example response:

```text
I found 4 connected websites:

1. Main Website - example.com
2. Campaign Microsite - campaign.example.com
3. Knowledge Base - help.example.com
4. Donation Site - donate.example.org

Which website should we focus on?
```

### Set Website Focus

User:

```text
Let's look at website 4.
```

GPT:

1. Maps `4` to the fourth site from the latest `listWebsites` response.
2. Treats that site as the active website for the rest of the conversation.
3. Uses that `site_id` in all analytics calls until the user switches focus.

Example response:

```text
Focused on Donation Site. What would you like to understand first?
```

### Natural-Language Analytics

User:

```text
What content should I improve first?
```

GPT:

1. Uses the active `site_id`.
2. Calls `getContentOpportunities`.
3. Interprets the response in plain English.

### Connect New Site

User:

```text
Connect a new site.
```

GPT:

1. Calls `startSiteConnection`.
2. Shows the returned connection URL or code.
3. Polls `getSiteConnectionStatus` if useful.
4. Calls `listWebsites` once the connection is complete.

Example response:

```text
Open this URL as a WordPress admin to connect the site:

https://app.analyticschat.example/connect/abc123

Or open Settings -> Analytics Chat in WordPress and enter this code:

ABC123
```

## Architecture

```text
ChatGPT public GPT
  -> GPT Action
  -> Analytics Chat hosted bridge
  -> Connected-site registry
  -> WordPress plugin on selected site
  -> Independent Analytics data
```

## Components

### Public GPT

Responsibilities:

- Explain analytics in plain English.
- Call bridge actions.
- Ask the user to choose a website before answering site-specific questions.
- Maintain conversation-level site focus.
- Never ask users to paste private WordPress API keys into chat.

### Hosted Bridge

Responsibilities:

- Authenticate GPT users.
- Store connected website records.
- Store per-site credentials securely.
- Start and complete site connection flows.
- Proxy analytics requests to the correct WordPress site.
- Normalize bridge-level errors for GPT interpretation.
- Enforce rate limits and access controls.

### WordPress Plugin

Responsibilities:

- Continue acting as the site-side Independent Analytics adapter.
- Add a connection UI for the hosted bridge.
- Register or disconnect the site with the bridge.
- Authenticate bridge requests.
- Return only aggregated analytics data.

## Bridge API MVP

Base URL example:

```text
https://app.analyticschat.example/api/v1
```

### `GET /sites`

Purpose:

Return websites connected to the current GPT user.

Response:

```json
{
  "sites": [
    {
      "site_id": "site_123",
      "name": "Main Website",
      "url": "https://example.com",
      "status": "connected",
      "last_seen_at": "2026-06-13T05:00:00Z"
    }
  ]
}
```

### `POST /sites/connect/start`

Purpose:

Start a new WordPress site connection.

Response:

```json
{
  "connection_id": "conn_123",
  "connection_code": "ABC123",
  "connection_url": "https://app.analyticschat.example/connect/conn_123",
  "expires_at": "2026-06-13T05:15:00Z"
}
```

### `GET /sites/connect/{connection_id}/status`

Purpose:

Check whether a site connection has completed.

Response:

```json
{
  "connection_id": "conn_123",
  "status": "completed",
  "site": {
    "site_id": "site_123",
    "name": "Main Website",
    "url": "https://example.com"
  }
}
```

Possible statuses:

- `pending`
- `completed`
- `expired`
- `failed`

### `DELETE /sites/{site_id}`

Purpose:

Disconnect a website from the current user.

Response:

```json
{
  "site_id": "site_123",
  "status": "disconnected"
}
```

### Analytics Proxy Endpoints

All analytics endpoints include `site_id` in the path:

```text
GET /sites/{site_id}/site-summary
GET /sites/{site_id}/top-content
GET /sites/{site_id}/content-performance
GET /sites/{site_id}/content-opportunities
GET /sites/{site_id}/referrers
GET /sites/{site_id}/campaigns
GET /sites/{site_id}/forms
GET /sites/{site_id}/user-journey
```

The request and response shapes should mirror the current WordPress plugin endpoints as much as possible.

## WordPress Plugin Changes

Add a connection section to Settings -> Analytics Chat:

- Connection status.
- "Connect this site to Analytics Chat" button.
- Connection code input.
- Disconnect button.
- Last successful bridge request time.
- Site identifier assigned by bridge.

### Site Registration Payload

When completing a connection, the plugin sends:

```json
{
  "site_name": "Example Site",
  "site_url": "https://example.com",
  "wordpress_version": "6.8",
  "php_version": "8.2",
  "plugin_version": "0.1.0",
  "independent_analytics_active": true,
  "independent_analytics_version": "x.y.z"
}
```

The bridge returns:

```json
{
  "site_id": "site_123",
  "bridge_token": "secret-token-shown-once",
  "status": "connected"
}
```

The plugin stores the bridge token locally and uses it to authenticate bridge-originated analytics requests.

## Authentication Model

### GPT to Bridge

MVP options:

1. API key configured in the GPT Action.
2. OAuth when public user-specific accounts are required.

For a public GPT where each user has their own websites, OAuth is the better long-term model because it lets the bridge associate requests with the current user.

### Bridge to WordPress

The bridge calls the WordPress plugin using a per-site token.

The token should:

- Be generated during connection.
- Be stored encrypted by the bridge.
- Be stored hashed or securely by WordPress where possible.
- Be revocable from both sides.
- Never be exposed to ChatGPT conversation text.

## Data Model

### `users`

- `id`
- `external_auth_subject`
- `email`
- `created_at`
- `updated_at`

### `sites`

- `id`
- `user_id`
- `name`
- `url`
- `status`
- `encrypted_bridge_token`
- `last_seen_at`
- `created_at`
- `updated_at`

### `site_connections`

- `id`
- `user_id`
- `connection_code_hash`
- `status`
- `expires_at`
- `completed_site_id`
- `created_at`
- `updated_at`

### `audit_events`

- `id`
- `user_id`
- `site_id`
- `event_type`
- `metadata`
- `created_at`

## GPT Instructions Addendum

Add this behavior to the Custom GPT instructions:

```text
Before answering any website-specific analytics question, make sure a website is selected.

If no website is selected:
- Call listWebsites.
- If websites are available, show a numbered list and ask which one to focus on.
- If no websites are available, offer to start a new site connection.

When the user chooses a website by number, map the number to the most recent listWebsites result and treat that site as the active website for the rest of the conversation.

If the user asks to connect a new site, call startSiteConnection and explain the returned connection URL or code.

If the user asks to switch websites, call listWebsites again unless the existing numbered list is still clear from the conversation.

For analytics questions, always use the active site_id.
```

## Privacy Requirements

- The GPT must not ask users to paste WordPress API keys into chat.
- The bridge must not expose raw visitor identifiers.
- The bridge must not log full bearer tokens.
- The bridge should log minimal request metadata for debugging and abuse prevention.
- Analytics responses must remain aggregated.
- Public GPT publishing requires a valid Privacy Policy URL for Actions.

## Immediate Implementation Plan

1. Add this architecture document.
2. Create a bridge service skeleton.
3. Implement `GET /sites`.
4. Implement `POST /sites/connect/start`.
5. Add bridge connection settings to the WordPress plugin.
6. Implement site registration from WordPress to bridge.
7. Implement one proxied analytics endpoint: `/sites/{site_id}/site-summary`.
8. Update the OpenAPI schema for the public GPT bridge.
9. Create a private GPT and test:
   - list websites
   - connect new site
   - set website focus
   - request site summary

## Open Questions

- Which hosted stack should the bridge use: Node/Express, Laravel, Rails, or another stack?
- Where should production data live: Postgres, Supabase, PlanetScale, or managed MySQL?
- Should v1 public GPT use OAuth immediately, or start with a private beta API key model?
- Should the WordPress plugin support both single-site direct GPT mode and hosted bridge mode?
- What privacy policy URL/domain should be used for public GPT publishing?
