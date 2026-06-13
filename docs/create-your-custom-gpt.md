# Create Your Custom GPT

This guide configures a Custom GPT that connects directly to one live production WordPress site running Analytics Chat for WordPress.

## Before You Start

You need:

- A production WordPress site with HTTPS.
- Independent Analytics installed and active.
- Analytics Chat for WordPress installed and active.
- A generated API key from Settings -> Analytics Chat.
- A public privacy policy URL for the GPT Action.

## 1. Generate The WordPress API Key

In WordPress:

1. Go to Settings -> Analytics Chat.
2. Confirm Independent Analytics is detected.
3. Click Generate new API key.
4. Copy the key immediately.

The full key is shown only once. If it is lost, rotate the key and update the GPT Action authentication.

## 2. Prepare The OpenAPI Schema

Open:

```text
openapi/analytics-chat-openapi.yaml
```

Replace:

```text
https://example.com/wp-json/acfw/v1
```

With the live site endpoint:

```text
https://your-site.com/wp-json/acfw/v1
```

The server URL must be the exact production WordPress site that has the plugin active.

## 3. Create The GPT

In ChatGPT:

1. Open Explore GPTs.
2. Select Create.
3. Use the configuration view.
4. Set the name to `Analytics Chat for WordPress` or a site-specific name.
5. Add the instructions from `docs/gpt-instructions.md`.
6. Add conversation starters from this guide.

## 4. Add The Action

In the GPT editor:

1. Go to Actions.
2. Create a new action.
3. Paste the edited OpenAPI schema.
4. Set authentication to API key.
5. Choose Bearer token.
6. Paste the WordPress API key.
7. Add the privacy policy URL.
8. Save and test.

## 5. Test Prompts

Use these prompts in Preview:

```text
Give me a site summary for the last 30 days.
```

```text
What content should I improve first?
```

```text
Which pages have traffic but weak conversion?
```

```text
How is this page performing?
```

For a specific page, include the page URL or post ID:

```text
How is https://your-site.com/example-page/ performing over the last 30 days?
```

## Recommended Conversation Starters

- Give me a site summary for the last 30 days.
- What content should I improve first?
- Which pages have traffic but weak conversion?
- What changed this month?

## Important Limitation

This no-infrastructure setup connects one GPT Action to one WordPress site.

For a public GPT where each user connects their own separate site from inside ChatGPT, you need the hosted bridge architecture described in `docs/public-gpt-bridge-architecture.md`.
