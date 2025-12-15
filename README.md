# NotiBoost PHP SDK

Official PHP SDK for NotiBoost - Notification Orchestration Platform.

## Installation

```bash
composer require notiboost/php-sdk
```

## Requirements

- PHP 7.4 or higher
- cURL extension

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use NotiBoost\NotiBoostClient;

$client = new NotiBoostClient([
    'api_key' => 'YOUR_API_KEY'
]);

// Send an event
$result = $client->events->ingest([
    'event_name' => 'order_created',
    'event_id' => 'evt_001',
    'occurred_at' => date('c'),
    'user_id' => 'u_123',
    'properties' => [
        'order_id' => 'A001',
        'amount' => 350000
    ]
]);

echo 'Trace ID: ' . $result['trace_id'];
```

## API Reference

### Constructor

```php
new NotiBoostClient($options)
```

**Options:**
- `api_key` (string, required) - Your NotiBoost API key
- `base_url` (string, optional) - Custom API base URL (default: `https://api.notiboost.com`)
- `timeout` (int, optional) - Request timeout in seconds (default: `30`)
- `retries` (int, optional) - Number of retry attempts (default: `3`)

### Events

#### `events->ingest($event)`

Ingest a single event.

```php
$result = $client->events->ingest([
    'event_name' => 'order_created',
    'event_id' => 'evt_001',
    'occurred_at' => date('c'),
    'user_id' => 'u_123',
    'properties' => [
        'order_id' => 'A001',
        'amount' => 350000
    ]
]);
```

#### `events->ingestBatch($events)`

Ingest multiple events in a single request.

```php
$result = $client->events->ingestBatch([
    [
        'event_name' => 'order_created',
        'event_id' => 'evt_001',
        'user_id' => 'u_123',
        'properties' => ['order_id' => 'A001']
    ],
    [
        'event_name' => 'payment_success',
        'event_id' => 'evt_002',
        'user_id' => 'u_123',
        'properties' => ['order_id' => 'A001']
    ]
]);
```

### Users

#### `users->create($user)`

Create a new user.

```php
$client->users->create([
    'user_id' => 'u_123',
    'name' => 'Nguyễn Văn A',
    'email' => 'user@example.com',
    'phone' => '+84901234567',
    'properties' => [
        'segment' => 'vip',
        'preferred_channel' => 'zns'
    ]
]);
```

#### `users->get($userId)`

Get user by ID.

```php
$user = $client->users->get('u_123');
```

#### `users->update($userId, $data)`

Update user.

```php
$client->users->update('u_123', [
    'name' => 'Nguyễn Văn B'
]);
```

#### `users->delete($userId)`

Delete user.

```php
$client->users->delete('u_123');
```

#### `users->setChannelData($userId, $channelData)`

Set channel data for user.

```php
$client->users->setChannelData('u_123', [
    'email' => 'user@example.com',
    'phone' => '+84901234567',
    'push_token' => 'fcm_token_abc123',
    'push_platform' => 'android',
    'zns_oa_id' => '123456789'
]);
```

#### `users->setPreferences($userId, $preferences)`

Set user notification preferences.

```php
$client->users->setPreferences('u_123', [
    'channels' => [
        'zns' => ['enabled' => true],
        'email' => ['enabled' => true],
        'sms' => ['enabled' => true],
        'push' => ['enabled' => true]
    ],
    'categories' => [
        'order' => ['enabled' => true],
        'marketing' => ['enabled' => false]
    ]
]);
```

#### `users->createBatch($users)`

Create multiple users in a single request.

```php
$client->users->createBatch([
    [
        'user_id' => 'u_123',
        'name' => 'Nguyễn Văn A',
        'email' => 'user1@example.com',
        'phone' => '+84901234567'
    ],
    [
        'user_id' => 'u_124',
        'name' => 'Trần Thị B',
        'email' => 'user2@example.com',
        'phone' => '+84901234568',
        'push_token' => 'fcm_token_xyz789',
        'push_platform' => 'ios'
    ]
]);
```

### Flows

#### `flows->create($flow)`

Create a notification flow.

```php
$client->flows->create([
    'name' => 'order_confirmation',
    'description' => 'Send order confirmation via ZNS',
    'rules' => [
        [
            'condition' => "event_name == 'order_created'",
            'action' => 'send_zns'
        ]
    ],
    'channels' => ['zns'],
    'template_id' => 'tpl_order_confirm'
]);
```

### Templates

#### `templates->create($template)`

Create a template.

```php
$client->templates->create([
    'name' => 'order_confirmation_zns',
    'channel' => 'zns',
    'content' => [
        'header' => 'Xác nhận đơn hàng',
        'body' => 'Đơn hàng {{order_id}} đã được xác nhận. Tổng tiền: {{amount}} VNĐ',
        'footer' => 'Cảm ơn bạn đã mua sắm'
    ],
    'variables' => ['order_id', 'amount']
]);
```

#### `templates->list($options)`

List templates.

```php
$templates = $client->templates->list(['channel' => 'zns']);
```

#### `templates->get($templateId)`

Get template by ID.

```php
$template = $client->templates->get('tpl_order_confirm');
```

#### `templates->update($templateId, $data)`

Update template.

```php
$client->templates->update('tpl_order_confirm', [
    'content' => [
        'body' => 'Updated body content'
    ]
]);
```

### Webhooks

#### `webhooks->create($webhook)`

Create a webhook.

```php
$client->webhooks->create([
    'url' => 'https://your-app.com/webhooks/notiboost',
    'events' => ['message.sent', 'message.delivered', 'message.failed'],
    'secret' => 'your_webhook_secret'
]);
```

## Error Handling

```php
try {
    $client->events->ingest($event);
} catch (NotiBoostException $e) {
    if ($e->getStatusCode() === 429) {
        // Rate limit exceeded
        echo 'Rate limit exceeded, retrying...';
    } else if ($e->getStatusCode() === 401) {
        // Invalid API key
        echo 'Invalid API key';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
```

## Idempotency

Use `Idempotency-Key` header for idempotent requests:

```php
$client->events->ingest($event, [
    'headers' => [
        'Idempotency-Key' => 'unique-key-12345'
    ]
]);
```

## Best Practices

1. Use singleton pattern for client instance
2. Cache API key in environment variables
3. Handle exceptions properly
4. Use idempotency keys for critical operations

