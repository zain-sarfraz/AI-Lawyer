# AI Legal Receptionist + CRM

Single-organization AI receptionist and CRM for a law firm.

**Stack:** Laravel 13 · PHP 8.3 · MySQL 8 · Blade + Bootstrap · Twilio Voice · OpenAI Realtime · TypeScript voice gateway

## Documentation

See [`docs/AI_Legal_Receptionist_CRM_Complete_Implementation_Specification.md`](docs/AI_Legal_Receptionist_CRM_Complete_Implementation_Specification.md).

## What’s implemented

- Modular Laravel domain services (Firm, CRM, Scheduling, Appointments, AI tools, Calls, Notifications, Audit, Integrations)
- Full MySQL schema from the specification
- RBAC roles: `admin`, `lawyer`, `staff`
- Admin CRM UI including AI Receptionist controls
- Transactional appointment booking with MySQL `GET_LOCK` + `BOOKING_CONFLICT`
- Availability engine (hours, breaks, holidays, exceptions, buffers, timezones)
- AI tool registry + OpenAI Realtime tool definitions
- TypeScript **voice gateway** bridging Twilio Media Streams ↔ OpenAI Realtime
- Twilio webhook signature verification middleware
- SMS/email appointment confirmation job via Twilio Messaging
- Post-call transcript summary job via OpenAI

## Setup

```bash
cp .env.example .env
# set DB_*, OPENAI_*, TWILIO_*, VOICE_GATEWAY_*
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work
```

### Voice gateway

```bash
cd voice-gateway
cp .env.example .env
# set OPENAI_API_KEY and matching VOICE_GATEWAY_SECRET
npm install
npm run dev
```

Expose the gateway with a public WSS URL (ngrok/cloudflare tunnel) and set:

```env
VOICE_GATEWAY_PUBLIC_WS_URL=wss://xxxx.ngrok-free.app/media
```

Point Twilio voice webhook to:

`https://your-laravel-host/api/webhooks/twilio/voice/incoming`

Details: [`voice-gateway/README.md`](voice-gateway/README.md)

### Default logins (seeded)

| Email | Password | Role |
| --- | --- | --- |
| `admin@lawfirm.test` | `password` | Admin |
| `staff@lawfirm.test` | `password` | Staff |
| `lawyer@lawfirm.test` | `password` | Lawyer |

## Key principles

1. AI is the conversational interface — never the authority for prices, availability, or booking state.
2. Laravel owns business rules; MySQL is the system of record.
3. Booking always re-validates availability inside a transaction.

## Next phases

- Google Calendar adapter
- Production hardening / monitoring
- Deeper AI evaluation suite
- WhatsApp / web chat channels
