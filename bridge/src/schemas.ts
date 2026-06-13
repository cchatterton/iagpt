import { z } from "zod";

export const siteRegistrationSchema = z.object({
  connection_code: z.string().min(4).max(64).optional(),
  site_name: z.string().min(1).max(120),
  site_url: z.string().url(),
  wordpress_version: z.string().max(40).optional(),
  php_version: z.string().max(40).optional(),
  plugin_version: z.string().max(40).optional(),
  independent_analytics_active: z.boolean().optional(),
  independent_analytics_version: z.string().max(40).nullable().optional(),
});

export const periodQuerySchema = z.object({
  period: z.string().regex(/^\d+d$/).optional().default("30d"),
  start_date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  end_date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  compare: z.enum(["previous_period", "none"]).optional().default("previous_period"),
});
