# Create Your Custom GPT

This guide configures a Custom GPT that connects directly to one live production WordPress site running Analytics Chat for WordPress.

## Before You Start

You need:

- A production WordPress site with HTTPS.
- Independent Analytics installed and active.
- Analytics Chat for WordPress installed and active.
- A public privacy policy URL for the GPT Action.

## 1. Confirm The Public REST Endpoint

In WordPress:

1. Go to Settings -> Analytics Chat.
2. Confirm Independent Analytics is detected.
3. Copy the REST base URL.

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
4. Set authentication to none.
5. Add the privacy policy URL.
6. Save and test.

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

This no-infrastructure setup connects one GPT Action to one public WordPress analytics endpoint.

For a public GPT where each user can choose a different site from inside ChatGPT, either create a separate Action/server URL for that site or add a hosted bridge later.
