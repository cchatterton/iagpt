import type { NextFunction, Request, Response } from "express";

export function requireBridgeAuth(req: Request, res: Response, next: NextFunction): void {
  const configuredKey = process.env.BRIDGE_API_KEY;
  if (!configuredKey) {
    res.status(500).json({
      error: {
        code: "bridge_auth_not_configured",
        message: "BRIDGE_API_KEY is not configured.",
      },
    });
    return;
  }

  const header = req.header("authorization") ?? "";
  const match = header.match(/^Bearer\s+(.+)$/i);

  if (!match) {
    res.status(401).json({
      error: {
        code: "unauthorized",
        message: "Missing Authorization bearer token.",
      },
    });
    return;
  }

  if (match[1].trim() !== configuredKey) {
    res.status(403).json({
      error: {
        code: "forbidden",
        message: "Invalid bridge API key.",
      },
    });
    return;
  }

  next();
}
