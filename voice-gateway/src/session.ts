import WebSocket from 'ws';

import { config } from './config.js';
import { completeCall, fetchContext, invokeTool, type LaravelContext } from './laravel.js';

type TwilioStartMessage = {
  event: 'start';
  start: {
    streamSid: string;
    callSid: string;
    customParameters?: Record<string, string>;
  };
};

type TwilioMediaMessage = {
  event: 'media';
  media: { payload: string; track?: string };
};

type TwilioStopMessage = {
  event: 'stop';
};

type TwilioMessage = TwilioStartMessage | TwilioMediaMessage | TwilioStopMessage | { event: string };

export class CallSession {
  private openai?: WebSocket;
  private streamSid?: string;
  private callSid?: string;
  private aiSessionId?: string;
  private callerPhone?: string;
  private context?: LaravelContext;
  private transcriptParts: string[] = [];
  private closed = false;
  private startedAt = Date.now();

  constructor(private readonly twilioWs: WebSocket) {}

  async handleTwilioMessage(raw: WebSocket.RawData): Promise<void> {
    const message = JSON.parse(raw.toString()) as TwilioMessage;

    switch (message.event) {
      case 'start':
        await this.onStart(message as TwilioStartMessage);
        break;
      case 'media':
        this.onMedia(message as TwilioMediaMessage);
        break;
      case 'stop':
        await this.onStop();
        break;
      default:
        break;
    }
  }

  async close(reason = 'closed'): Promise<void> {
    if (this.closed) return;
    this.closed = true;

    try {
      if (this.callSid) {
        await completeCall({
          provider_call_id: this.callSid,
          transcript: this.transcriptParts.join('\n').trim() || undefined,
          outcome: reason,
          duration_seconds: Math.max(1, Math.round((Date.now() - this.startedAt) / 1000)),
        });
      }
    } catch (error) {
      console.error('[voice-gateway] failed to complete call', error);
    }

    try {
      this.openai?.close();
    } catch {
      // ignore
    }
    try {
      this.twilioWs.close();
    } catch {
      // ignore
    }
  }

  private async onStart(message: TwilioStartMessage): Promise<void> {
    this.streamSid = message.start.streamSid;
    this.callSid = message.start.callSid;
    const params = message.start.customParameters || {};
    this.aiSessionId = params.ai_session_id;
    this.callerPhone = params.caller_phone;

    console.log('[voice-gateway] call started', {
      callSid: this.callSid,
      aiSessionId: this.aiSessionId,
      callerPhone: this.callerPhone,
    });

    this.context = await fetchContext();
    await this.connectOpenAI();
  }

  private async connectOpenAI(): Promise<void> {
    if (!config.openaiApiKey) {
      throw new Error('OPENAI_API_KEY is required');
    }

    const model = this.context?.realtime_model || config.openaiRealtimeModel;
    const url = `wss://api.openai.com/v1/realtime?model=${encodeURIComponent(model)}`;

    this.openai = new WebSocket(url, {
      headers: {
        Authorization: `Bearer ${config.openaiApiKey}`,
        'OpenAI-Beta': 'realtime=v1',
      },
    });

    this.openai.on('open', () => {
      const instructions = this.buildInstructions();
      const tools = this.context?.realtime_tools || [];

      this.sendOpenAI({
        type: 'session.update',
        session: {
          instructions,
          modalities: ['text', 'audio'],
          voice: 'alloy',
          input_audio_format: 'g711_ulaw',
          output_audio_format: 'g711_ulaw',
          input_audio_transcription: { model: 'whisper-1' },
          turn_detection: {
            type: 'server_vad',
            threshold: 0.5,
            prefix_padding_ms: 300,
            silence_duration_ms: 500,
          },
          tools,
          tool_choice: 'auto',
        },
      });

      // Kick off greeting.
      this.sendOpenAI({
        type: 'response.create',
        response: {
          modalities: ['text', 'audio'],
          instructions: 'Greet the caller briefly using the configured greeting and ask how you can help. Do not invent firm facts.',
        },
      });
    });

    this.openai.on('message', (data) => {
      void this.onOpenAIMessage(data.toString());
    });

    this.openai.on('error', (error) => {
      console.error('[voice-gateway] OpenAI socket error', error);
    });

    this.openai.on('close', () => {
      void this.close('openai_closed');
    });
  }

  private buildInstructions(): string {
    const base = this.context?.system_prompt || 'You are a virtual law firm receptionist.';
    const caller = this.callerPhone ? `Caller phone: ${this.callerPhone}.` : '';
    return `${base}\n${caller}\nAlways use tools for prices, availability, booking, and firm facts. Never invent legal advice.`;
  }

  private onMedia(message: TwilioMediaMessage): void {
    if (!this.openai || this.openai.readyState !== WebSocket.OPEN) return;
    if (!message.media?.payload) return;

    this.sendOpenAI({
      type: 'input_audio_buffer.append',
      audio: message.media.payload,
    });
  }

  private async onOpenAIMessage(raw: string): Promise<void> {
    let event: any;
    try {
      event = JSON.parse(raw);
    } catch {
      return;
    }

    switch (event.type) {
      case 'response.audio.delta':
      case 'response.output_audio.delta': {
        const audio = event.delta || event.audio;
        if (audio && this.streamSid && this.twilioWs.readyState === WebSocket.OPEN) {
          this.twilioWs.send(
            JSON.stringify({
              event: 'media',
              streamSid: this.streamSid,
              media: { payload: audio },
            }),
          );
        }
        break;
      }
      case 'conversation.item.input_audio_transcription.completed': {
        if (event.transcript) {
          this.transcriptParts.push(`Caller: ${event.transcript}`);
        }
        break;
      }
      case 'response.audio_transcript.done':
      case 'response.output_audio_transcript.done': {
        if (event.transcript) {
          this.transcriptParts.push(`Receptionist: ${event.transcript}`);
        }
        break;
      }
      case 'response.function_call_arguments.done': {
        await this.executeFunctionCall(event.name, event.call_id, event.arguments || '{}');
        break;
      }
      case 'error': {
        console.error('[voice-gateway] OpenAI error event', event.error || event);
        break;
      }
      default:
        break;
    }
  }

  private async executeFunctionCall(name: string, callId: string, argsRaw: string): Promise<void> {
    let args: Record<string, unknown> = {};
    try {
      args = JSON.parse(argsRaw || '{}');
    } catch {
      args = {};
    }

    // Prefer known caller phone when tool omits it.
    if (!args.phone && this.callerPhone) {
      args.phone = this.callerPhone;
    }
    if (name === 'book_appointment' && !args.idempotency_key) {
      args.idempotency_key = `${this.callSid || 'call'}-${callId}`;
    }

    console.log('[voice-gateway] tool call', name, args);

    let result: unknown;
    try {
      result = await invokeTool(name, args, {
        aiSessionId: this.aiSessionId,
        providerCallId: this.callSid,
      });
    } catch (error) {
      result = {
        error: 'TOOL_INVOKE_FAILED',
        message: error instanceof Error ? error.message : 'Unknown tool error',
      };
    }

    this.sendOpenAI({
      type: 'conversation.item.create',
      item: {
        type: 'function_call_output',
        call_id: callId,
        output: JSON.stringify(result),
      },
    });

    this.sendOpenAI({ type: 'response.create' });

    if (name === 'transfer_to_human') {
      // Laravel initiates Twilio call update; end local media bridge shortly after.
      setTimeout(() => void this.close('transferred'), 1500);
    }
  }

  private async onStop(): Promise<void> {
    await this.close('completed');
  }

  private sendOpenAI(payload: Record<string, unknown>): void {
    if (!this.openai || this.openai.readyState !== WebSocket.OPEN) return;
    this.openai.send(JSON.stringify(payload));
  }
}
