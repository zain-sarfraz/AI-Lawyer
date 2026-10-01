import http from 'node:http';
import express from 'express';
import { WebSocketServer } from 'ws';

import { assertConfig, config } from './config.js';
import { CallSession } from './session.js';

assertConfig();

const app = express();
app.get('/health', (_req, res) => {
  res.json({
    ok: true,
    service: 'ai-receptionist-voice-gateway',
    laravelApiUrl: config.laravelApiUrl,
    openaiConfigured: Boolean(config.openaiApiKey),
  });
});

const server = http.createServer(app);
const wss = new WebSocketServer({ server, path: '/media' });

wss.on('connection', (socket) => {
  console.log('[voice-gateway] Twilio media stream connected');
  const session = new CallSession(socket);

  socket.on('message', (data) => {
    void session.handleTwilioMessage(data).catch((error) => {
      console.error('[voice-gateway] session message error', error);
      void session.close('error');
    });
  });

  socket.on('close', () => {
    void session.close('twilio_closed');
  });

  socket.on('error', (error) => {
    console.error('[voice-gateway] Twilio socket error', error);
    void session.close('twilio_error');
  });
});

server.listen(config.port, () => {
  console.log(`[voice-gateway] listening on :${config.port}`);
  console.log(`[voice-gateway] media ws path: /media`);
  console.log(`[voice-gateway] laravel api: ${config.laravelApiUrl}`);
});
