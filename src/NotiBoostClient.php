<?php

namespace NotiBoost;

use NotiBoost\Exception\NotiBoostException;

class NotiBoostClient
{
    private $apiKey;
    private $baseURL;
    private $timeout;
    private $retries;

    public $events;
    public $users;
    public $flows;
    public $templates;
    public $webhooks;

    public function __construct(array $options = [])
    {
        if (empty($options['api_key'])) {
            throw new \InvalidArgumentException('API key is required');
        }

        $this->apiKey = $options['api_key'];
        $this->baseURL = $options['base_url'] ?? 'https://api.notiboost.com';
        $this->timeout = $options['timeout'] ?? 30;
        $this->retries = $options['retries'] ?? 3;

        // Initialize resource clients
        $this->events = new EventsClient($this);
        $this->users = new UsersClient($this);
        $this->flows = new FlowsClient($this);
        $this->templates = new TemplatesClient($this);
        $this->webhooks = new WebhooksClient($this);
    }

    public function request(string $method, string $path, array $data = null, array $options = []): array
    {
        $url = rtrim($this->baseURL, '/') . $path;
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        if (!empty($options['headers'])) {
            foreach ($options['headers'] as $key => $value) {
                $headers[] = $key . ': ' . $value;
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $lastError = null;
        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            try {
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);

                if ($error) {
                    throw new \RuntimeException('cURL error: ' . $error);
                }

                $responseData = json_decode($response, true) ?? [];

                if ($statusCode >= 200 && $statusCode < 300) {
                    curl_close($ch);
                    return $responseData;
                } elseif ($statusCode === 429 && $attempt < $this->retries) {
                    // Rate limit - wait and retry
                    $retryAfter = isset($responseData['retry_after']) ? $responseData['retry_after'] : 1;
                    sleep($retryAfter);
                    continue;
                } else {
                    curl_close($ch);
                    throw new NotiBoostException(
                        $responseData['message'] ?? "HTTP $statusCode",
                        $statusCode,
                        $responseData
                    );
                }
            } catch (\Exception $e) {
                $lastError = $e;
                if ($attempt < $this->retries && !($e instanceof NotiBoostException)) {
                    sleep(pow(2, $attempt)); // Exponential backoff
                    continue;
                }
                curl_close($ch);
                throw $e;
            }
        }

        curl_close($ch);
        throw $lastError;
    }
}

class EventsClient
{
    private $client;

    public function __construct(NotiBoostClient $client)
    {
        $this->client = $client;
    }

    public function ingest(array $event): array
    {
        if (empty($event['occurred_at'])) {
            $event['occurred_at'] = date('c');
        }
        return $this->client->request('POST', '/api/v1/events', $event);
    }

    public function ingestBatch(array $events): array
    {
        return $this->client->request('POST', '/api/v1/events/batch', ['events' => $events]);
    }
}

class UsersClient
{
    private $client;

    public function __construct(NotiBoostClient $client)
    {
        $this->client = $client;
    }

    public function create(array $user): array
    {
        return $this->client->request('POST', '/api/v1/users', $user);
    }

    public function get(string $userId): array
    {
        return $this->client->request('GET', "/api/v1/users/$userId");
    }

    public function update(string $userId, array $data): array
    {
        return $this->client->request('PUT', "/api/v1/users/$userId", $data);
    }

    public function delete(string $userId): array
    {
        return $this->client->request('DELETE', "/api/v1/users/$userId");
    }

    public function setChannelData(string $userId, array $channelData): array
    {
        return $this->client->request('PUT', "/api/v1/users/$userId/channel_data", $channelData);
    }

    public function setPreferences(string $userId, array $preferences): array
    {
        return $this->client->request('PUT', "/api/v1/users/$userId/preferences", $preferences);
    }

    public function createBatch(array $users): array
    {
        return $this->client->request('POST', '/api/v1/users/batch', ['users' => $users]);
    }
}

class FlowsClient
{
    private $client;

    public function __construct(NotiBoostClient $client)
    {
        $this->client = $client;
    }

    public function create(array $flow): array
    {
        return $this->client->request('POST', '/api/v1/flows', $flow);
    }
}

class TemplatesClient
{
    private $client;

    public function __construct(NotiBoostClient $client)
    {
        $this->client = $client;
    }

    public function create(array $template): array
    {
        return $this->client->request('POST', '/api/v1/templates', $template);
    }

    public function list(array $options = []): array
    {
        $query = http_build_query($options);
        $path = $query ? "/api/v1/templates?$query" : '/api/v1/templates';
        return $this->client->request('GET', $path);
    }

    public function get(string $templateId): array
    {
        return $this->client->request('GET', "/api/v1/templates/$templateId");
    }

    public function update(string $templateId, array $data): array
    {
        return $this->client->request('PUT', "/api/v1/templates/$templateId", $data);
    }
}

class WebhooksClient
{
    private $client;

    public function __construct(NotiBoostClient $client)
    {
        $this->client = $client;
    }

    public function create(array $webhook): array
    {
        return $this->client->request('POST', '/api/v1/webhooks', $webhook);
    }
}

