<?php

namespace App\Domain\AI;

class AiToolDefinitions
{
    /**
     * OpenAI Realtime-compatible tool definitions.
     *
     * @return list<array<string, mixed>>
     */
    public static function realtimeTools(): array
    {
        return [
            self::tool('get_firm_profile', 'Get approved firm profile information.', [
                'type' => 'object',
                'properties' => (object) [],
            ]),
            self::tool('get_services', 'List active legal services by name. Read speak/menu aloud. Do not use option numbers.', [
                'type' => 'object',
                'properties' => [
                    'category' => ['type' => 'string', 'description' => 'Optional category filter'],
                ],
            ]),
            self::tool('get_service_details', 'Get details for one service including exact price and duration.', [
                'type' => 'object',
                'properties' => [
                    'service_id' => ['type' => 'integer'],
                ],
                'required' => ['service_id'],
            ]),
            self::tool('get_lawyers', 'List eligible active lawyers with their weekly availability windows, optionally filtered by service.', [
                'type' => 'object',
                'properties' => [
                    'service_id' => ['type' => 'integer'],
                ],
            ]),
            self::tool('list_available_days', 'List available days this week by name. Read speak/menu. Ask which day. No option numbers.', [
                'type' => 'object',
                'properties' => [
                    'service_id' => ['type' => 'integer'],
                    'lawyer_id' => ['type' => 'integer'],
                ],
                'required' => ['service_id', 'lawyer_id'],
            ]),
            self::tool('find_available_slots', 'Return availability window (start–end) for one date. Read speak/menu only — tell start and end times, let caller pick a time. Do not list every slot or use option numbers.', [
                'type' => 'object',
                'properties' => [
                    'service_id' => ['type' => 'integer'],
                    'lawyer_id' => ['type' => 'integer'],
                    'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD from list_available_days'],
                ],
                'required' => ['service_id', 'lawyer_id', 'date'],
            ]),
            self::tool('find_caller', 'Find existing lead/client by phone number.', [
                'type' => 'object',
                'properties' => [
                    'phone' => ['type' => 'string'],
                ],
                'required' => ['phone'],
            ]),
            self::tool('create_or_update_lead', 'Create or update a CRM lead from caller details.', [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'service_id' => ['type' => 'integer'],
                    'notes' => ['type' => 'string'],
                ],
            ]),
            self::tool('book_appointment', 'Book ONE appointment after the caller confirms the final slot. Call at most once per conversation. Never claim success unless this returns success.', [
                'type' => 'object',
                'properties' => [
                    'service_id' => ['type' => 'integer'],
                    'lawyer_id' => ['type' => 'integer'],
                    'start_at' => ['type' => 'string', 'description' => 'ISO 8601 start from verified slot'],
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'notes' => ['type' => 'string'],
                    'idempotency_key' => ['type' => 'string'],
                ],
                'required' => ['service_id', 'lawyer_id', 'start_at', 'phone'],
            ]),
            self::tool('reschedule_appointment', 'Reschedule an existing appointment to a new verified start time.', [
                'type' => 'object',
                'properties' => [
                    'appointment_id' => ['type' => 'integer'],
                    'new_start_at' => ['type' => 'string'],
                ],
                'required' => ['appointment_id', 'new_start_at'],
            ]),
            self::tool('cancel_appointment', 'Cancel an existing appointment.', [
                'type' => 'object',
                'properties' => [
                    'appointment_id' => ['type' => 'integer'],
                    'reason' => ['type' => 'string'],
                ],
                'required' => ['appointment_id'],
            ]),
            self::tool('send_confirmation', 'Send booking confirmation SMS/email.', [
                'type' => 'object',
                'properties' => [
                    'appointment_id' => ['type' => 'integer'],
                    'channel' => ['type' => 'string', 'enum' => ['sms', 'email', 'both']],
                ],
                'required' => ['appointment_id'],
            ]),
            self::tool('transfer_to_human', 'Transfer the caller to a human according to firm policy.', [
                'type' => 'object',
                'properties' => [
                    'reason' => ['type' => 'string'],
                ],
                'required' => ['reason'],
            ]),
            self::tool('create_callback_request', 'Create a callback request for staff follow-up.', [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'reason' => ['type' => 'string'],
                ],
                'required' => ['phone', 'reason'],
            ]),
            self::tool('get_knowledge_base', 'Fetch firm-approved FAQs and policies.', [
                'type' => 'object',
                'properties' => (object) [],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private static function tool(string $name, string $description, array $parameters): array
    {
        return [
            'type' => 'function',
            'name' => $name,
            'description' => $description,
            'parameters' => $parameters,
        ];
    }
}
