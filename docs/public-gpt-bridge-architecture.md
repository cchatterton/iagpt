# Public GPT Bridge Architecture

Status: future/optional architecture. This is not required for the current no-infrastructure MVP.

The current MVP release path is direct-site setup: a site owner installs the WordPress plugin, generates an API key, creates a Custom GPT Action, and points that Action at their own production WordPress site.

This document applies only if the product later needs one centrally hosted public GPT that can list, connect, and switch between many users' websites from inside ChatGPT.

## Why A Bridge Would Be Needed

A single public GPT Action has a configured API server URL and authentication setup. Without a hosted bridge, it cannot automatically discover and route requests to every user's separate WordPress site.

The no-infrastructure release therefore works as a setup kit:

```text
Custom GPT
  -> one production WordPress site
  -> Analytics Chat for WordPress plugin
  -> Independent Analytics data
```

The hosted bridge model would work like this:

```text
Public GPT
  -> hosted bridge API
  -> selected connected WordPress site
  -> Analytics Chat for WordPress plugin
  -> Independent Analytics data
```

## Future Product Goal

Let a user open one public GPT and work naturally across one or more connected WordPress sites:

1. The GPT lists the user's connected websites.
2. The user chooses a website focus.
3. The GPT answers analytics questions for that focused website.
4. The user can connect a new site from the conversation.
5. The user can switch focus between sites.

The GPT should not ask users to paste WordPress API keys into chat.

## Future Conversation Flow

User:

```text
Show me my websites.
```

GPT:

1. Calls `listWebsites`.
2. If sites exist, returns a numbered list.
3. If no sites exist, offers to connect a new site.

Example:

```text
I found 4 connected websites:

1. Main Website - example.com
2. Campaign Microsite - campaign.example.com
3. Knowledge Base - help.example.com
4. Donation Site - donate.example.org

Which website should we focus on?
```

User:

```text
Let's look at website 4.
```

GPT maps `4` to the fourth site from the latest `listWebsites` response and uses that `site_id` for later analytics calls.

## Future Bridge API MVP

Base URL example:

```text
https://app.analyticschat.example/api/v1
```

Endpoints:

```text
GET    /sites
POST   /sites/connect/start
GET    /sites/connect/{connection_id}/status
DELETE /sites/{site_id}
GET    /sites/{site_id}/site-summary
GET    /sites/{site_id}/top-content
GET    /sites/{site_id}/content-performance
GET    /sites/{site_id}/content-opportunities
GET    /sites/{site_id}/referrers
GET    /sites/{site_id}/campaigns
GET    /sites/{site_id}/forms
GET    /sites/{site_id}/user-journey
```

## Future Components

### Public GPT

- Explains analytics in plain English.
- Calls bridge actions.
- Asks the user to choose a website before site-specific questions.
- Maintains conversation-level site focus.
- Never asks users to paste private WordPress API keys into chat.

### Hosted Bridge

- Authenticates GPT users.
- Stores connected website records.
- Stores per-site credentials securely.
- Starts and completes site connection flows.
- Proxies analytics requests to the correct WordPress site.
- Enforces rate limits and access controls.

### WordPress Plugin

- Continues acting as the site-side Independent Analytics adapter.
- Adds a connection UI for the hosted bridge.
- Registers or disconnects the site with the bridge.
- Authenticates bridge requests.
- Returns only aggregated analytics data.

## Authentication Model

### GPT To Bridge

For a true public multi-user GPT, OAuth is the better long-term model because it lets the bridge associate each request with the current user.

A private beta could start with a shared bridge API key, but that would not provide proper per-user site ownership.

### Bridge To WordPress

The bridge calls the WordPress plugin using a per-site token generated during connection. The token should be stored encrypted by the bridge, stored hashed by WordPress where possible, and never exposed in ChatGPT conversation text.

## Future Data Model

Suggested tables:

- `users`
- `sites`
- `site_connections`
- `audit_events`

Store only the minimum data needed to route requests and support account/security operations.

## Privacy Requirements

- The GPT must not ask users to paste WordPress API keys into chat.
- The bridge must not expose raw visitor identifiers.
- The bridge must not log full bearer tokens.
- The bridge should log minimal request metadata for debugging and abuse prevention.
- Analytics responses must remain aggregated.
- Public GPT publishing requires a valid privacy policy URL for Actions.

## Implementation Trigger

Build this bridge only when the product requirement becomes: one central public GPT, many users, many connected WordPress sites, and in-chat site switching.

Until then, use the direct-site setup in `docs/create-your-custom-gpt.md`.
