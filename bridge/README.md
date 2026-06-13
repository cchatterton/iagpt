# Analytics Chat Bridge

Hosted bridge API for the public/multi-site Analytics Chat GPT.

This service sits between ChatGPT and connected WordPress sites:

```text
ChatGPT GPT Action
  -> bridge API
  -> selected WordPress site plugin
  -> Independent Analytics data
```

## Current Scope

This is a functional skeleton for testing the GPT conversation flow:

- list connected websites
- start a new site connection
- check connection status
- simulate completing a WordPress connection
- proxy analytics requests to the connected WordPress plugin

The current store is in-memory and resets when the process restarts. Replace it with Postgres/Supabase before production.

## Setup

```bash
cd bridge
npm install
cp .env.example .env
npm run dev
```

Set `BRIDGE_API_KEY` in `.env`. GPT Actions should send:

```text
Authorization: Bearer {BRIDGE_API_KEY}
```

## Docker

```bash
docker build -t analytics-chat-bridge ./bridge
docker run --rm -p 8787:8787 --env-file ./bridge/.env analytics-chat-bridge
```

## Endpoints

```text
GET  /health
GET  /api/v1/sites
POST /api/v1/sites/connect/start
GET  /api/v1/sites/connect/:connection_id/status
POST /api/v1/internal/connections/complete
POST /api/v1/internal/connections/:connection_id/complete
DELETE /api/v1/sites/:site_id
GET  /api/v1/sites/:site_id/site-summary
GET  /api/v1/sites/:site_id/top-content
GET  /api/v1/sites/:site_id/content-performance
GET  /api/v1/sites/:site_id/content-opportunities
GET  /api/v1/sites/:site_id/referrers
GET  /api/v1/sites/:site_id/campaigns
GET  /api/v1/sites/:site_id/forms
GET  /api/v1/sites/:site_id/user-journey
```

`POST /api/v1/internal/connections/complete` is the temporary WordPress plugin callback. It accepts the one-time `connection_code` returned by `POST /sites/connect/start`.

`POST /api/v1/internal/connections/:connection_id/complete` is retained for local development and test fixtures.

## Next Production Tasks

- Replace in-memory storage with Postgres/Supabase.
- Add user auth/OAuth for public GPT users.
- Encrypt per-site WordPress tokens.
- Add WordPress plugin connection UI.
- Add production-grade response schemas to the bridge OpenAPI file.
