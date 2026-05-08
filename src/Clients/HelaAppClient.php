<?php

namespace Ometra\HelaSdk\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Exceptions\MissingAppConfigurationException;

class HelaAppClient
{
    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $defaults
     */
    public function __construct(
        private readonly string $name,
        private readonly array $config,
        private readonly array $defaults = [],
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function baseUrl(): string
    {
        $baseUrl = $this->config['base_url'] ?? null;

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw MissingAppConfigurationException::missingBaseUrl($this->name);
        }

        return rtrim($baseUrl, '/');
    }

    public function token(): ?string
    {
        $token = $this->config['token'] ?? $this->config['api_key'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function http(): PendingRequest
    {
        return $this->makeHttpRequest(withToken: true);
    }

    public function httpWithoutToken(): PendingRequest
    {
        return $this->makeHttpRequest(withToken: false);
    }

    private function makeHttpRequest(bool $withToken): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout());

        $token = $this->token();
        if ($withToken && $token !== null) {
            $request = $request->withToken($token);
        }

        $source = $this->sourceApp();
        if ($source !== null) {
            $request = $request->withHeader('X-Hela-App', $source);
        }

        $retry = $this->retry();
        if ($retry['times'] > 0) {
            $request = $request->retry($retry['times'], $retry['sleep']);
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $query
     */
    public function get(string $uri, array $query = []): Response
    {
        return $this->http()->get($this->uri($uri), $query);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function post(string $uri, array $data = []): Response
    {
        return $this->http()->post($this->uri($uri), $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function postWithoutToken(string $uri, array $data = []): Response
    {
        return $this->httpWithoutToken()->post($this->uri($uri), $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function put(string $uri, array $data = []): Response
    {
        return $this->http()->put($this->uri($uri), $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function patch(string $uri, array $data = []): Response
    {
        return $this->http()->patch($this->uri($uri), $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function delete(string $uri, array $data = []): Response
    {
        return $this->http()->delete($this->uri($uri), $data);
    }

    private function uri(string $uri): string
    {
        return '/' . ltrim($uri, '/');
    }

    private function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? $this->defaults['timeout'] ?? 30);
    }

    /**
     * @return array{times: int, sleep: int}
     */
    private function retry(): array
    {
        $retry = is_array($this->config['retry'] ?? null)
            ? $this->config['retry']
            : (is_array($this->defaults['retry'] ?? null) ? $this->defaults['retry'] : []);

        return [
            'times' => (int) ($retry['times'] ?? 0),
            'sleep' => (int) ($retry['sleep'] ?? 100),
        ];
    }

    private function sourceApp(): ?string
    {
        $source = $this->config['source'] ?? $this->defaults['source'] ?? null;

        return is_string($source) && $source !== '' ? $source : null;
    }
}
