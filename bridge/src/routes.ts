import type { Router } from "express";
import express from "express";
import { z } from "zod";
import { requireBridgeAuth } from "./auth.js";
import { periodQuerySchema, siteRegistrationSchema } from "./schemas.js";
import { store } from "./store.js";

export function createRouter(): Router {
  const router = express.Router();

  router.get("/health", (_req, res) => {
    res.json({
      status: "ok",
      service: "analytics-chat-bridge",
      version: "0.1.0",
    });
  });

  router.post("/api/v1/internal/connections/complete", (req, res) => {
    const parsed = siteRegistrationSchema.safeParse(req.body);
    if (!parsed.success) {
      res.status(400).json(validationError(parsed.error));
      return;
    }

    if (!parsed.data.connection_code) {
      res.status(400).json({
        error: {
          code: "invalid_request",
          message: "connection_code is required.",
        },
      });
      return;
    }

    const result = store.completeConnectionByCode(parsed.data.connection_code, parsed.data);
    if (!result) {
      res.status(404).json({
        error: {
          code: "connection_not_found_or_unavailable",
          message: "Connection could not be completed.",
        },
      });
      return;
    }

    res.status(201).json({
      site_id: result.site.site_id,
      bridge_token: result.site.bridge_token,
      status: "connected",
    });
  });

  router.use("/api/v1", requireBridgeAuth);

  router.get("/api/v1/sites", (_req, res) => {
    res.json({ sites: store.listSites() });
  });

  router.post("/api/v1/sites/connect/start", (req, res) => {
    void req;
    const publicBaseUrl = process.env.PUBLIC_BASE_URL ?? "http://localhost:8787";
    res.status(201).json(store.startConnection(publicBaseUrl));
  });

  router.get("/api/v1/sites/connect/:connection_id/status", (req, res) => {
    const connection = store.getConnection(req.params.connection_id);
    if (!connection) {
      res.status(404).json({
        error: {
          code: "connection_not_found",
          message: "Connection could not be found.",
        },
      });
      return;
    }

    const site = connection.completed_site_id ? store.getSite(connection.completed_site_id) : undefined;

    res.json({
      connection_id: connection.connection_id,
      status: connection.status,
      site: site
        ? {
            site_id: site.site_id,
            name: site.name,
            url: site.url,
          }
        : null,
    });
  });

  router.post("/api/v1/internal/connections/:connection_id/complete", (req, res) => {
    const parsed = siteRegistrationSchema.safeParse(req.body);
    if (!parsed.success) {
      res.status(400).json(validationError(parsed.error));
      return;
    }

    const result = store.completeConnection(req.params.connection_id, parsed.data);
    if (!result) {
      res.status(404).json({
        error: {
          code: "connection_not_found_or_unavailable",
          message: "Connection could not be completed.",
        },
      });
      return;
    }

    res.status(201).json({
      site_id: result.site.site_id,
      bridge_token: result.site.bridge_token,
      status: "connected",
    });
  });

  router.delete("/api/v1/sites/:site_id", (req, res) => {
    const site = store.disconnectSite(req.params.site_id);
    if (!site) {
      res.status(404).json({
        error: {
          code: "site_not_found",
          message: "Site could not be found.",
        },
      });
      return;
    }

    res.json({
      site_id: site.site_id,
      status: site.status,
    });
  });

  router.get("/api/v1/sites/:site_id/site-summary", (req, res) => {
    const query = periodQuerySchema.safeParse(req.query);
    if (!query.success) {
      res.status(400).json(validationError(query.error));
      return;
    }

    const site = store.getSite(req.params.site_id);
    if (!site || site.status !== "connected") {
      res.status(404).json({
        error: {
          code: "site_not_found",
          message: "Site could not be found.",
        },
      });
      return;
    }

    // This is a bridge contract stub. The next step is to call the WordPress plugin
    // at `${site.url}/wp-json/acfw/v1/site-summary` with the site's bridge token.
    res.json({
      site: {
        site_id: site.site_id,
        name: site.name,
        url: site.url,
      },
      period: {
        requested_period: query.data.period,
        start: query.data.start_date ?? null,
        end: query.data.end_date ?? null,
        compare: query.data.compare,
      },
      metrics: {
        views: 0,
        visitors: 0,
        sessions: 0,
        bounce_rate: 0,
        average_session_duration: 0,
        conversion_count: 0,
        conversion_rate: 0,
      },
      available: false,
      reason: "WordPress proxying is not implemented yet. The bridge skeleton is ready for the site-side connection flow.",
    });
  });

  return router;
}

function validationError(error: z.ZodError): { error: { code: string; message: string; details: unknown } } {
  return {
    error: {
      code: "invalid_request",
      message: "Request validation failed.",
      details: error.flatten(),
    },
  };
}
