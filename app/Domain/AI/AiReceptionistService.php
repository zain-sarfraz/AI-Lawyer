<?php

declare(strict_types=1);

namespace App\Domain\AI;

use App\Domain\Firm\FirmService;
use App\Domain\Scheduling\AvailabilityService;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\SpokenFormat;
use Carbon\Carbon;

class AiReceptionistService
{
    /**
     * Voice ID used specifically for Urdu unless the firm overrides ai_voice.
     * English uses the Synthflow agent's own configured voice ID.
     */
    public const URDU_VOICE_ID = 'wdXhcCmAYpPsryFWCtn2';

    /**
     * @deprecated Use URDU_VOICE_ID instead.
     */
    public const DEFAULT_VOICE_ID = self::URDU_VOICE_ID;

    /**
     * Isolated language/speech block — English only.
     * Swap by setting $firm->ai_language = 'en'.
     */
    private const LANGUAGE_SPEECH_BLOCK_EN = <<<'PROMPT'
=== LANGUAGE / SPEECH (CRITICAL) ===
- Speak ONLY clear, natural English for the whole call. Never output any non-English words.
- If the caller speaks another language, reply politely in English: "I'm sorry, I can only assist in English at the moment. Please feel free to continue."
- Never output ellipsis dots (two or three periods in a row), emojis, markdown, bullet symbols, or double spaces. They become silent gaps or bad audio on the phone.
- Maximum 1 to 2 short sentences per turn. Ask ONE question per turn, then WAIT.
- Stop speaking immediately when the caller starts speaking.
- Never say filler like "please hold", "one moment", "let me check" while a tool runs. Stay silent during tool calls and speak the result as soon as it returns.
- Names: capture the EXACT spoken name on the first try and repeat it exactly. Never convert, translate, or autocorrect names into English words (for example Tariq is not Terry, Akbar is not October, Bilal is not Ball).
- Read numbers and times in a way that is clear when spoken (for example "three thirty in the afternoon").
PROMPT;

    /**
     * Isolated language/speech block — Urdu only.
     * Swap by setting $firm->ai_language = 'ur'.
     */
    private const LANGUAGE_SPEECH_BLOCK_UR = <<<'PROMPT'
=== LANGUAGE / SPEECH (CRITICAL) ===
- Puri call mein SIRF saaf, qudrati Urdu bolein. Koi English ya doosri zubaan ka istemal na karein.
- Agar caller kisi doosri zubaan mein baat kare, toh shaaistagi se Urdu mein jawab dein: "Mujhe maafi chahiye, main sirf Urdu mein madad kar sakta hoon."
- Kabhi ellipsis (do ya teen periods), emojis, markdown, bullet symbols ya double spaces mat likhein — yeh phone audio mein khamoshi ya awaaz kharab karte hain.
- Har turn mein SIRF 1 ya 2 chhote jumle. EK sawaal par turn, phir RUKO.
- Jab caller bolna shuru kare, foran khamosh ho jaein.
- Jab tool chal raha ho, kuch mat kahein ("ek second", "dekh raha hoon" waghaira bilkul nahi). Tool ka jawab aane par foran bolein.
- Naam: pehli baar hi BILKUL WAHI naam pakroin jo caller ne bola. Kabhi convert, translate ya autocorrect mat karein.
- Numbers aur waqt aise bolein jo phone par saaf sunaai dein (maslan "teen bajke tees minute", "dopahar").
PROMPT;

    /**
     * Keep backward-compat — previously the single English block.
     * @deprecated Use languageSpeechSection($lang) instead.
     */
    private const LANGUAGE_SPEECH_BLOCK = self::LANGUAGE_SPEECH_BLOCK_EN;

    /**
     * Identical prompt template for every tenant/account. Only placeholders and
     * the appended dynamic data block differ per logged-in account.
     */
    private const STATIC_TEMPLATE = <<<'PROMPT'
=== ROLE ===
You are the polite, professional phone assistant for {firm_name}. You explain services, fees, duration, lawyer names, and availability, and you book consultations. Nothing else.

=== TONE ===
- Warm, calm, patient, and professional. Never rude, curt, rushed, or robotic.
- Sound like a real receptionist on a phone call, not a script.
- Acknowledge briefly and naturally ("Of course", "Certainly", "Thank you", "I understand"), and do not repeat the same acknowledgment every turn.

{language_speech_block}

=== IDENTITY ===
If asked whether you are human or a bot: "I'm an AI assistant, and I can help you book a consultation." Then return to the task. Never claim to be human or a lawyer.

=== LEGAL BOUNDARY (CRITICAL) ===
- You are NOT a lawyer. NEVER give legal advice, interpret laws, predict outcomes, promise results, suggest strategy, or comment on chances (bail, guilt, custody, compensation, etc.).
- You CAN: explain services, fees, duration, lawyer names, and availability, and book consultations.
- If the caller asks a legal question: "I'm sorry, that's a question best answered by the lawyer during your consultation. I can book that for you now." Do not answer it even partially.
- Never ask for detailed case facts. Collect only: service, name, phone.

=== SENSITIVE CALLERS ===
- Legal matters can be stressful. Be extra gentle, patient, and unhurried.
- If the caller says it is urgent (an arrest, police involvement, a deadline today): acknowledge calmly ("I'm sorry you're going through this, let's get you booked right away") and take the fastest booking path.
- Confidentiality: never share any caller's details or mention other callers.
- If the caller seems at risk of harming themselves or others: stay calm, gently encourage them to contact local emergency services right away, and still offer to book.

=== MONEY RULES (CRITICAL) ===
- Currency for this office is {currency_code}, spoken as {currency_spoken_unit}.
- Speak fees as English number words followed by {currency_spoken_unit}, whole numbers only. Example: 45.00 is spoken "forty five {currency_spoken_unit}".
- Never say decimals, currency symbols, or currency codes.
- Only quote fees that appear in the data block. Never invent, estimate, convert, or add taxes or discounts.

=== PHONE RULE ===
- Ask once: "Could you share your phone number, please?"
- If the caller says "same number", "this number", "the one I'm calling from", or similar, reply "Perfect, thank you" once and pass customer_phone as exactly "same".
- Never ask for the phone number a second time. Repeat it back only if the caller gave digits, and confirm once.

=== TURN 1 ===
"Hello, thank you for calling {firm_name}. How can I help you today?"

=== FLOW (ONE question per turn) ===
1. Greet with Turn 1 and wait.
2. get_services -> read the "speak" field. The caller names the service. Never use option numbers.
3. get_service_details -> state the fee and duration (only if a duration is listed for that service), then ask: "Would you like to book a consultation for [Service]?" WAIT for yes.
4. Name: "May I have your name for the booking?" (accept exactly what they say on the first try)
5. Phone: follow the PHONE RULE.
6. list_available_days -> say day names only. Ask which day works for them.
7. find_available_slots -> say ONLY the available_from to available_until window. The caller chooses a time. Never list every slot.
8. Map the chosen time to slots[].start_at.
9. Summary in one short sentence: "That's a consultation for [Service] with [Lawyer] under the name [EXACT name], on [day] at [time], and the fee is [English words] {currency_spoken_unit}. Shall I confirm this booking?" WAIT for yes.
10. After yes: stay SILENT and call book_legal_consultation ONCE immediately.
11. On success: read the tool's "speak" field EXACTLY once. The booking is instantly confirmed, with no verification codes. Close: "Thank you for calling, take care."
- Name and phone are asked only once each.

=== TOOL RULES / ERRORS (CRITICAL) ===
- Office timezone is {timezone}.
- After the caller confirms, never say "there was a problem", "let me try again", or "please stay on the line".
- If the tool returns success or a "speak" field, read it exactly. The booking IS saved. Never paraphrase it, and never invent a booking ID.
- If success is false: fix the missing field and retry ONCE. Never invent days, hours, prices, or lawyer names.
- Offer only services and lawyers listed in the data block. For anything else: "I'm sorry, we don't offer that service at the moment," then offer the available ones.
- If the caller asks for a firm phone, email, website, or address that is marked "not available": "I'm sorry, I don't have that detail right now, but I can book your consultation." Never invent one. Do not offer to transfer the call if the transfer number is "not available".
- After hours: follow {after_hours_behavior}. For "callback": take the name and phone once, say the team will call back, and close politely.
- Cancel or reschedule: the policy window is {cancellation_window_hours} hours. If no tool exists: "I'm sorry, I can't cancel or reschedule by phone right now, but I can make a new booking for you." Do not invent other policies.
- No services or lawyers configured: "I'm sorry, I can't take bookings right now, but I can have the team call you back." Collect name and phone once.

=== OFF-TOPIC CALLERS ===
Wrong number, spam, prank, abusive, or non-legal questions: apologize politely, state what you can help with, and close the call politely if abuse continues.
PROMPT;

    public function __construct(
        private readonly FirmService $firmService,
        private readonly AvailabilityService $availability,
    ) {}

    public function systemPrompt(bool $withCatalog = false, ?Firm $firm = null): string
    {
        $firm ??= $this->firmService->current();
        $custom = trim((string) ($firm->ai_system_prompt ?? ''));
        $base = $custom !== '' ? $custom : $this->defaultSystemPrompt($firm);

        if (! $withCatalog) {
            return $base;
        }

        return rtrim($base)."\n\n".$this->catalogKnowledge($firm);
    }

    public function defaultSystemPrompt(?Firm $firm = null): string
    {
        $firm ??= $this->firmService->current();
        $currencyCode = $this->resolvedCurrencyCode($firm);

        $langBlock = $this->languageSpeechSection($firm->ai_language ?? 'en');
        $template = str_replace('{language_speech_block}', $langBlock, self::STATIC_TEMPLATE);

        return strtr($template, [
            '{firm_name}' => $this->resolvedFirmName($firm),
            '{currency_code}' => $currencyCode,
            '{currency_spoken_unit}' => SpokenFormat::currencyUnit($currencyCode),
            '{timezone}' => $this->resolvedTimezone($firm),
            '{cancellation_window_hours}' => (string) ((int) ($firm->cancellation_window_hours ?? 24)),
            '{after_hours_behavior}' => $this->cleanOrDefault($firm->after_hours_behavior, 'callback'),
        ]);
    }

    public function isUrduLanguage(?string $language): bool
    {
        return in_array(strtolower(trim((string) ($language ?? 'en'))), ['ur', 'urdu', 'ur-pk'], true);
    }

    /**
     * Return the isolated language/speech block for the given language code.
     * Pass $firm->ai_language (e.g. 'en', 'ur').
     */
    public function languageSpeechSection(?string $language = 'en'): string
    {
        return $this->isUrduLanguage($language)
            ? self::LANGUAGE_SPEECH_BLOCK_UR
            : self::LANGUAGE_SPEECH_BLOCK_EN;
    }

    public function greetingMessage(?Firm $firm = null): string
    {
        $firm ??= $this->firmService->current();
        $custom = trim((string) ($firm->ai_greeting ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return 'Hello, thank you for calling '.$this->resolvedFirmName($firm).'. How can I help you today?';
    }

    public function sessionContext(?Firm $firm = null): array
    {
        $firm ??= $this->firmService->current();

        return [
            'firm_name' => $this->resolvedFirmName($firm),
            'legal_name' => $firm->legal_name,
            'phone' => $this->contactValue($firm->phone),
            'email' => $this->contactValue($firm->email),
            'website' => $this->contactValue($firm->website),
            'address' => $this->contactValue($firm->address),
            'timezone' => $this->resolvedTimezone($firm),
            'currency' => $this->resolvedCurrencyCode($firm),
            'currency_spoken_unit' => SpokenFormat::currencyUnit($this->resolvedCurrencyCode($firm)),
            'receptionist_name' => $firm->receptionist_name,
            'greeting' => $this->greetingMessage($firm),
            'tone' => $firm->ai_tone,
            'language' => $firm->ai_language,
            'voice' => $firm->ai_voice,
            'after_hours_behavior' => $this->cleanOrDefault($firm->after_hours_behavior, 'callback'),
            'transfer_number_configured' => $this->contactValue($firm->transfer_number) !== 'not available',
            'recording_enabled' => (bool) $firm->recording_enabled,
            'booking_horizon_days' => max(1, (int) ($firm->booking_horizon_days ?: 14)),
            'cancellation_window_hours' => (int) ($firm->cancellation_window_hours ?? 24),
            'custom_prompt' => filled($firm->ai_system_prompt),
        ];
    }

    /**
     * Per-account DYNAMIC_DATA_BLOCK appended after STATIC_TEMPLATE.
     * Every query is explicitly scoped by $firm->id to prevent cross-tenant leakage.
     */
    public function catalogKnowledge(?Firm $firm = null): string
    {
        $firm ??= $this->firmService->current();
        $firmId = (int) $firm->id;

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->with([
                'lawyers' => fn ($q) => $q->withoutGlobalScopes()
                    ->where('lawyers.firm_id', $firmId)
                    ->where('lawyers.status', 'active')
                    ->with(['user' => fn ($uq) => $uq->withoutGlobalScopes()->where('firm_id', $firmId)]),
                'category',
            ])
            ->orderBy('name')
            ->get();

        $lawyers = Lawyer::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->with([
                'user' => fn ($uq) => $uq->withoutGlobalScopes()->where('firm_id', $firmId),
                'services' => fn ($q) => $q->withoutGlobalScopes()
                    ->where('services.firm_id', $firmId)
                    ->where('services.status', 'active')
                    ->orderBy('name'),
            ])
            ->orderBy('id')
            ->get();

        $firmName = $this->resolvedFirmName($firm);
        $timezone = $this->resolvedTimezone($firm);
        $now = Carbon::now($timezone);
        $currencyCode = $this->resolvedCurrencyCode($firm);
        $currencyUnit = SpokenFormat::currencyUnit($currencyCode);
        $hasServices = $services->isNotEmpty();
        $hasLawyers = $lawyers->isNotEmpty();

        $lines = [
            '=== ACCOUNT DATA ===',
            'Office name: '.$firmName,
            'Office phone: '.$this->contactValue($firm->phone),
            'Office email: '.$this->contactValue($firm->email),
            'Website: '.$this->contactValue($firm->website),
            'Address: '.$this->contactValue($firm->address),
            'Transfer number: '.$this->contactValue($firm->transfer_number),
            'Timezone: '.$timezone,
            'Currency: '.$currencyCode.' (spoken as '.$currencyUnit.')',
            'Cancellation window: '.((int) ($firm->cancellation_window_hours ?? 24)).' hours',
            'Booking horizon: '.max(1, (int) ($firm->booking_horizon_days ?: 14)).' days',
            'After-hours behavior: '.$this->cleanOrDefault($firm->after_hours_behavior, 'callback'),
            'Call recording: '.($firm->recording_enabled ? 'enabled' : 'disabled'),
            'Current local date and time: '.$now->format('l, F j, Y g:i A').' ('.$timezone.')',
            'Today date: '.$now->format('Y-m-d'),
            'Booking status: '.($hasServices && $hasLawyers
                ? 'open (offer only services and lawyers listed below)'
                : 'unavailable (no services or lawyers configured; apologize and offer a callback after collecting name and phone once)'),
            '',
            '=== SERVICES ===',
        ];

        if (! $hasServices) {
            $lines[] = '- No services configured.';
        } else {
            foreach ($services as $service) {
                $svcCurrency = trim((string) ($service->currency ?: $currencyCode));
                $spokenFee = SpokenFormat::money($service->price, $svcCurrency);
                $duration = (int) ($service->duration_minutes ?? 0);
                $lawyerNames = $service->lawyers
                    ->map(fn (Lawyer $lawyer) => $lawyer->displayName())
                    ->filter()
                    ->implode(', ');

                $parts = [
                    $service->name.' (id '.$service->id.')',
                    'spoken fee: '.$spokenFee,
                ];
                if ($duration > 0) {
                    $parts[] = 'duration: '.$duration.' minutes';
                }
                if ($service->category?->name) {
                    $parts[] = 'category: '.$service->category->name;
                }
                $parts[] = 'lawyers: '.($lawyerNames !== '' ? $lawyerNames : 'none assigned');

                $lines[] = '- '.implode(' | ', $parts);
                if (filled($service->description)) {
                    $lines[] = '  description: '.trim((string) $service->description);
                }
            }
        }

        $lines[] = '';
        $lines[] = '=== LAWYERS ===';

        if (! $hasLawyers) {
            $lines[] = '- No lawyers configured.';
        } else {
            foreach ($lawyers as $lawyer) {
                $serviceNames = $lawyer->services->pluck('name')->implode(', ');
                $weeklyHours = $this->availability->weeklyHoursSummary($lawyer);
                $hoursText = $weeklyHours === []
                    ? 'no weekly hours configured'
                    : implode('; ', array_map(
                        fn (array $h) => $h['weekday'].' '.$h['start_label'].' to '.$h['end_label'],
                        $weeklyHours,
                    ));

                $parts = [$lawyer->displayName().' (id '.$lawyer->id.')'];
                if (filled($lawyer->title)) {
                    $parts[] = 'title: '.trim((string) $lawyer->title);
                }
                $parts[] = 'services: '.($serviceNames !== '' ? $serviceNames : 'none assigned');
                $parts[] = 'hours: '.$hoursText;

                $lines[] = '- '.implode(' | ', $parts);
            }
        }

        return implode("\n", $lines);
    }

    private function resolvedFirmName(Firm $firm): string
    {
        $name = trim((string) ($firm->legal_name ?: $firm->firm_name));

        return $name !== '' ? $name : 'our law office';
    }

    private function resolvedCurrencyCode(Firm $firm): string
    {
        $code = strtoupper(trim((string) ($firm->currency ?? '')));

        return $code !== '' ? $code : 'USD';
    }

    private function resolvedTimezone(Firm $firm): string
    {
        $tz = trim((string) ($firm->timezone ?? ''));

        return $tz !== '' ? $tz : $firm->resolvedTimezone();
    }

    private function contactValue(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '' || strtolower($trimmed) === 'n/a') {
            return 'not available';
        }

        return $trimmed;
    }

    private function cleanOrDefault(?string $value, string $fallback): string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $fallback;
    }
}
