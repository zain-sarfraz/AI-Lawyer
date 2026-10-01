# Voice Gateway (Twilio Media Streams ↔ OpenAI Realtime)

TypeScript service that bridges inbound Twilio phone audio to OpenAI Realtime and executes receptionist tools against the Laravel CRM API.

## Flow

```text
Caller
  → Twilio Voice
  → Laravel webhook (/api/webhooks/twilio/voice/incoming)
  → TwiML <Connect><Stream>
  → voice-gateway ws://.../media
  → OpenAI Realtime (g711_ulaw)
  → tool calls → Laravel /api/ai/tools/invoke
```

## Setup

```bash
cd voice-gateway
cp .env.example .env
# set OPENAI_API_KEY and VOICE_GATEWAY_SECRET
npm install
npm run dev
```

## Required env

| Key | Purpose |
| --- | --- |
| `PORT` | Gateway HTTP/WS port (default `3100`) |
| `LARAVEL_API_URL` | Laravel base URL, e.g. `http://127.0.0.1:8000` |
| `VOICE_GATEWAY_SECRET` | Shared secret header for Laravel AI endpoints |
| `OPENAI_API_KEY` | OpenAI API key with Realtime access |
| `OPENAI_REALTIME_MODEL` | Default `gpt-4o-realtime-preview` |

## Laravel pairing

In the Laravel `.env`:

```env
VOICE_GATEWAY_SECRET=change-me-local-secret
VOICE_GATEWAY_PUBLIC_WS_URL=wss://your-public-host/media
# For local Twilio testing use a tunnel, e.g. ngrok:
# VOICE_GATEWAY_PUBLIC_WS_URL=wss://xxxx.ngrok-free.app/media
TWILIO_ACCOUNT_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_PHONE_NUMBER=...
TWILIO_VALIDATE_SIGNATURE=false
OPENAI_API_KEY=...
```

Point the Twilio voice webhook to:

`https://your-laravel-host/api/webhooks/twilio/voice/incoming`

## Health

`GET /health`
