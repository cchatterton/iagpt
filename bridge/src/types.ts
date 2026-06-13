export type SiteStatus = "connected" | "disconnected";

export interface Site {
  site_id: string;
  name: string;
  url: string;
  status: SiteStatus;
  wordpress_version?: string;
  php_version?: string;
  plugin_version?: string;
  independent_analytics_active?: boolean;
  independent_analytics_version?: string | null;
  bridge_token: string;
  last_seen_at: string | null;
  created_at: string;
  updated_at: string;
}

export type ConnectionStatus = "pending" | "completed" | "expired" | "failed";

export interface SiteConnection {
  connection_id: string;
  connection_code: string;
  status: ConnectionStatus;
  expires_at: string;
  completed_site_id: string | null;
  created_at: string;
  updated_at: string;
}

export interface SiteRegistrationPayload {
  connection_code?: string;
  site_name: string;
  site_url: string;
  wordpress_version?: string;
  php_version?: string;
  plugin_version?: string;
  independent_analytics_active?: boolean;
  independent_analytics_version?: string | null;
}

export interface PublicSite {
  site_id: string;
  name: string;
  url: string;
  status: SiteStatus;
  last_seen_at: string | null;
}
