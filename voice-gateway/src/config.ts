import 'dotenv/config';

export const config = {
  port: Number(process.env.PORT || 3100),
  laravelApiUrl: (process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000').replace(/\/$/, ''),
  voiceGatewaySecret: process.env.VOICE_GATEWAY_SECRET || '',
  openaiApiKey: process.env.OPENAI_API_KEY || '',
  openaiRealtimeModel: process.env.OPENAI_REALTIME_MODEL || 'gpt-4o-realtime-preview',
  logLevel: process.env.LOG_LEVEL || 'info',
};

export function assertConfig(): void {
  if (!config.openaiApiKey) {
    console.warn('[voice-gateway] OPENAI_API_KEY is missing — Realtime sessions will fail until configured.');
  }
  if (!config.voiceGatewaySecret) {
    console.warn('[voice-gateway] VOICE_GATEWAY_SECRET is empty — Laravel local mode may still accept requests.');
  }
}
