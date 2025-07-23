<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RabbitMQManager
{
    private HttpClientInterface $client;
    private string $baseUri;
    private string $username;
    private string $password;

    public function __construct(HttpClientInterface $client, string $baseUri, string $username, string $password)
    {
        $this->client = $client;
        $this->baseUri = rtrim($baseUri, '/');
        $this->username = $username;
        $this->password = $password;
    }

    public function setPermissions(string $vhost, string $user, string $configure, string $write, string $read): void
    {
        $url = sprintf('%s/api/permissions/%s/%s', $this->baseUri, urlencode($vhost), $user);

        $this->client->request('PUT', $url, [
            'auth_basic' => [$this->username, $this->password],
            'json' => [
                'configure' => $configure,
                'write' => $write,
                'read' => $read,
            ],
        ]);
    }

    public function createVhost(string $vhost): void
    {
        $url = sprintf('%s/api/vhosts/%s', $this->baseUri, urlencode($vhost));

        try {
            $this->client->request('PUT', $url, [
                'auth_basic' => [$this->username, $this->password],
            ]);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException(sprintf('Failed to create vhost "%s": %s', $vhost, $e->getMessage()));
        }
    }
}