<?php

namespace Ometra\HelaSdk;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Ometra\HelaSdk\Clients\AusterClient;

class HelaSdk
{
    private ?AusterClient $auster = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function auster(): AusterClient
    {
        if ($this->auster instanceof AusterClient) {
            return $this->auster;
        }

        $config = is_array($this->config['auster'] ?? null) ? $this->config['auster'] : [];

        if (isset($this->config['base_url']) && ! isset($config['base_url'])) {
            $config['base_url'] = $this->config['base_url'];
        }

        if (isset($this->config['api_key']) && ! isset($config['token'])) {
            $config['token'] = $this->config['api_key'];
        }

        return $this->auster = new AusterClient('auster', $config, $this->config);
    }

    public function baseUrl(): string
    {
        return $this->auster()->baseUrl();
    }

    public function apiKey(): ?string
    {
        return $this->auster()->token();
    }

    public function http(): PendingRequest
    {
        return $this->auster()->http();
    }

    /**
     * @param array<string, mixed> $query
     */
    public function get(string $uri, array $query = []): Response
    {
        return $this->auster()->get($uri, $query);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function post(string $uri, array $data = []): Response
    {
        return $this->auster()->post($uri, $data);
    }
}
