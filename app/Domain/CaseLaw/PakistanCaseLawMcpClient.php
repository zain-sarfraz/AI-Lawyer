<?php

namespace App\Domain\CaseLaw;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the free Pakistan Case Law MCP server.
 *
 * @see https://pakistancaselaw.com/for-ai
 */
class PakistanCaseLawMcpClient
{
    private ?string $sessionId = null;

    public function __construct(
        private readonly string $endpoint = '',
        private readonly string $userAgent = '',
    ) {
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $name, array $arguments = []): array
    {
        $this->ensureSession();

        $response = $this->rpc(2, 'tools/call', [
            'name' => $name,
            'arguments' => $arguments,
        ]);

        if (($response['result']['isError'] ?? false) === true) {
            $msg = (string) data_get($response, 'result.content.0.text', 'MCP tool error');
            throw new RuntimeException($msg);
        }

        $text = (string) data_get($response, 'result.content.0.text', '');
        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : ['raw' => $text];
    }

    private function ensureSession(): void
    {
        if ($this->sessionId) {
            return;
        }

        $init = $this->rpc(1, 'initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities' => new \stdClass,
            'clientInfo' => [
                'name' => 'ai-lawyer',
                'version' => '1.0',
            ],
        ]);

        if (! isset($init['result'])) {
            throw new RuntimeException('Pakistan Case Law MCP initialize failed.');
        }

        // Required by MCP after initialize.
        $this->post([
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     */
    private function rpc(int $id, string $method, ?array $params = null): array
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
        ];
        if ($params !== null) {
            $payload['params'] = $params;
        }

        $response = $this->post($payload);
        $decoded = $this->decodeBody($response['body']);

        if (isset($decoded['error'])) {
            throw new RuntimeException('MCP error: '.json_encode($decoded['error']));
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{body: string, session: ?string}
     */
    private function post(array $payload): array
    {
        $endpoint = $this->endpoint ?: (string) config('case_law.pakistan_case_law.mcp_url');
        $ua = $this->userAgent ?: (string) config('case_law.fetch.user_agent');

        $headers = [
            'User-Agent' => $ua,
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
        ];
        if ($this->sessionId) {
            $headers['Mcp-Session-Id'] = $this->sessionId;
        }

        $response = Http::withHeaders($headers)
            ->timeout(60)
            ->retry(
                (int) config('case_law.fetch.retry_times', 3),
                (int) config('case_law.fetch.retry_sleep_ms', 1500)
            )
            ->post($endpoint, $payload);

        if ($response->header('Mcp-Session-Id')) {
            $this->sessionId = $response->header('Mcp-Session-Id');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Pakistan Case Law MCP HTTP '.$response->status());
        }

        return ['body' => $response->body(), 'session' => $this->sessionId];
    }

    /** @return array<string, mixed> */
    private function decodeBody(string $body): array
    {
        $body = trim($body);
        if (preg_match('/data:\s*(\{.*\})\s*$/s', $body, $m)) {
            $decoded = json_decode($m[1], true);
        } else {
            $decoded = json_decode($body, true);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
