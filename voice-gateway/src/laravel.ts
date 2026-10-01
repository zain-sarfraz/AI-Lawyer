import { config } from './config.js';

async function laravelFetch(path: string, init: RequestInit = {}): Promise<Response> {
  const headers = new Headers(init.headers || {});
  headers.set('Accept', 'application/json');
  headers.set('Content-Type', 'application/json');
  if (config.voiceGatewaySecret) {
    headers.set('X-Voice-Gateway-Secret', config.voiceGatewaySecret);
  }

  return fetch(`${config.laravelApiUrl}${path}`, {
    ...init,
    headers,
  });
}

export type LaravelContext = {
  system_prompt: string;
  session_context: Record<string, unknown>;
  realtime_tools: unknown[];
  realtime_model?: string;
};

export async function fetchContext(): Promise<LaravelContext> {
  const res = await laravelFetch('/api/ai/context');
  if (!res.ok) {
    throw new Error(`Failed to load AI context: ${res.status} ${await res.text()}`);
  }
  return res.json() as Promise<LaravelContext>;
}

export async function invokeTool(
  tool: string,
  args: Record<string, unknown>,
  meta: { aiSessionId?: string; providerCallId?: string },
): Promise<unknown> {
  const res = await laravelFetch('/api/ai/tools/invoke', {
    method: 'POST',
    body: JSON.stringify({
      tool,
      arguments: args,
      ai_session_id: meta.aiSessionId ? Number(meta.aiSessionId) : undefined,
      provider_call_id: meta.providerCallId,
    }),
  });

  if (!res.ok) {
    throw new Error(`Tool invoke failed: ${res.status} ${await res.text()}`);
  }

  const json = (await res.json()) as { data: unknown };
  return json.data;
}

export async function completeCall(payload: {
  provider_call_id: string;
  transcript?: string;
  summary?: string;
  intent?: string;
  outcome?: string;
  duration_seconds?: number;
  provider_session_id?: string;
}): Promise<void> {
  const res = await laravelFetch('/api/ai/calls/complete', {
    method: 'POST',
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    console.error('[voice-gateway] completeCall failed', res.status, await res.text());
  }
}
