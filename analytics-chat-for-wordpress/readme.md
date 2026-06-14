# Analytics Chat for WordPress

Public read-only WordPress plugin that lets a Custom GPT query aggregated Independent Analytics data through REST API endpoints.

Author: Techn  
Version: 0.1.8  
Status: MVP  

## Purpose

Analytics Chat for WordPress exposes aggregated Independent Analytics data through public, read-only REST endpoints so a Custom GPT can interpret WordPress content performance in plain English.

## Key Features

- Public REST endpoints for GPT Actions.
- Site summary, top content, content performance, content opportunities, referrers, campaigns, forms, and anonymised journey endpoints.
- Independent Analytics diagnostics.
- GitHub release update checks from version `0.1.1` onward.
- Aggregated analytics responses only; no raw visitor identifiers.

## Requirements

- WordPress 6.x+
- PHP 8.1+
- Independent Analytics active
- Independent Analytics Pro optional

Pro-only datasets such as campaigns, forms, and journey patterns degrade gracefully when unavailable.

## Folder Structure

```text
analytics-chat-for-wordpress.php
admin/
includes/
openapi/
readme.md
```

The original plugin specification called for the `includes/` class-based structure, so this plugin intentionally does not use the smaller `functions/` scaffold from the default Techn plugin standard.

## Setup

1. Install and activate Independent Analytics.
2. Install and activate this bridge plugin.
3. Go to Settings -> Analytics Chat and copy the REST base URL.
4. Open `openapi/analytics-chat-openapi.yaml`.
5. Replace `https://example.com` with your site URL.
6. Create a Custom GPT.
7. Add an Action.
8. Paste the OpenAPI schema.
9. Set authentication to none.
10. Test with: "Give me a site summary for the last 30 days."

## REST endpoints

All endpoints are under:

```text
/wp-json/acfw/v1
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

## GPT instructions

Use these instructions for the Custom GPT:

```text
You are Analytics Chat for WordPress.

You help users understand WordPress content and website performance using data from Independent Analytics.

Your role is to interpret analytics, not merely repeat metrics.

When answering:
- Separate facts from interpretation.
- State the period being analysed.
- Identify meaningful trends, risks, anomalies, and opportunities.
- Recommend practical next actions.
- Be clear when data is unavailable.
- Do not invent metrics.
- Do not claim certainty when the data is weak.
- Avoid exposing personal or visitor-level data.
- Prefer concise executive explanations.

When the user asks about:
- site performance, call getSiteSummary.
- top pages or posts, call getTopContent.
- a specific page/post/article, call getContentPerformance.
- what to improve, call getContentOpportunities.
- traffic sources, call getReferrers.
- campaigns, call getCampaigns.
- forms or submissions, call getForms.
- journeys or paths, call getUserJourney.

If the user asks for advice, use the analytics data first, then explain the recommendation.
```

## Privacy and security

- The plugin is read-only.
- REST endpoints are public.
- Raw IP addresses, visitor fingerprints, visitor-level browsing history, and WordPress user identities are not exposed.
- Requests are capped by date range and result count.

## Notes on Independent Analytics data

Independent Analytics internals can vary by version. This plugin keeps all IA-specific table detection and query logic inside `ACFW_Independent_Analytics`.

If a compatible aggregate table cannot be detected, the plugin returns a structured unavailable response instead of exposing raw data or making unsafe assumptions.

## Important Notes

- Author is declared as Techn.
- Author URL is https://techn.com.au.
- Plugin URL is https://github.com/cchatterton/iagpt.
- Prefix is `acfw`.
- REST namespace is `acfw/v1`.
- All REST routes use named permission callbacks.
- The plugin is read-only.

## Future Considerations

- Network-wide multisite dashboard support.
- OAuth or hosted bridge support for one public GPT serving many users.
- Broader Independent Analytics Pro schema support.
