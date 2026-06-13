import crypto from "node:crypto";
import type { PublicSite, Site, SiteConnection, SiteRegistrationPayload } from "./types.js";

const FIFTEEN_MINUTES_MS = 15 * 60 * 1000;

export class InMemoryStore {
  private readonly sites = new Map<string, Site>();
  private readonly connections = new Map<string, SiteConnection>();

  listSites(): PublicSite[] {
    return [...this.sites.values()]
      .filter((site) => site.status === "connected")
      .map(({ site_id, name, url, status, last_seen_at }) => ({
        site_id,
        name,
        url,
        status,
        last_seen_at,
      }))
      .sort((a, b) => a.name.localeCompare(b.name));
  }

  getSite(siteId: string): Site | undefined {
    return this.sites.get(siteId);
  }

  disconnectSite(siteId: string): Site | undefined {
    const site = this.sites.get(siteId);
    if (!site) {
      return undefined;
    }

    const now = new Date().toISOString();
    const updated: Site = {
      ...site,
      status: "disconnected",
      updated_at: now,
    };
    this.sites.set(siteId, updated);
    return updated;
  }

  startConnection(publicBaseUrl: string): SiteConnection & { connection_url: string } {
    const now = new Date();
    const connection: SiteConnection = {
      connection_id: `conn_${crypto.randomUUID()}`,
      connection_code: this.randomCode(),
      status: "pending",
      expires_at: new Date(now.getTime() + FIFTEEN_MINUTES_MS).toISOString(),
      completed_site_id: null,
      created_at: now.toISOString(),
      updated_at: now.toISOString(),
    };

    this.connections.set(connection.connection_id, connection);

    return {
      ...connection,
      connection_url: `${publicBaseUrl.replace(/\/$/, "")}/connect/${connection.connection_id}`,
    };
  }

  getConnection(connectionId: string): SiteConnection | undefined {
    const connection = this.connections.get(connectionId);
    if (!connection) {
      return undefined;
    }

    if (connection.status === "pending" && Date.parse(connection.expires_at) < Date.now()) {
      const expired: SiteConnection = {
        ...connection,
        status: "expired",
        updated_at: new Date().toISOString(),
      };
      this.connections.set(connectionId, expired);
      return expired;
    }

    return connection;
  }

  getConnectionByCode(connectionCode: string): SiteConnection | undefined {
    const normalizedCode = connectionCode.trim().toUpperCase();
    const connection = [...this.connections.values()].find(
      (candidate) => candidate.connection_code === normalizedCode,
    );

    return connection ? this.getConnection(connection.connection_id) : undefined;
  }

  completeConnectionByCode(connectionCode: string, payload: SiteRegistrationPayload): { site: Site; connection: SiteConnection } | undefined {
    const connection = this.getConnectionByCode(connectionCode);
    if (!connection) {
      return undefined;
    }

    return this.completeConnection(connection.connection_id, payload);
  }

  completeConnection(connectionId: string, payload: SiteRegistrationPayload): { site: Site; connection: SiteConnection } | undefined {
    const connection = this.getConnection(connectionId);
    if (!connection || connection.status !== "pending") {
      return undefined;
    }

    const now = new Date().toISOString();
    const site: Site = {
      site_id: `site_${crypto.randomUUID()}`,
      name: payload.site_name,
      url: payload.site_url,
      status: "connected",
      wordpress_version: payload.wordpress_version,
      php_version: payload.php_version,
      plugin_version: payload.plugin_version,
      independent_analytics_active: payload.independent_analytics_active,
      independent_analytics_version: payload.independent_analytics_version,
      bridge_token: `bt_${crypto.randomBytes(32).toString("hex")}`,
      last_seen_at: now,
      created_at: now,
      updated_at: now,
    };

    const completed: SiteConnection = {
      ...connection,
      status: "completed",
      completed_site_id: site.site_id,
      updated_at: now,
    };

    this.sites.set(site.site_id, site);
    this.connections.set(connectionId, completed);

    return { site, connection: completed };
  }

  private randomCode(): string {
    return crypto.randomBytes(6).toString("hex").toUpperCase();
  }
}

export const store = new InMemoryStore();
