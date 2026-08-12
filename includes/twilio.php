<?php
/**
 * Twilio SMS helper — no SDK, plain cURL against the REST API.
 * Credentials live in the settings table (Admin -> Settings -> Integrations).
 */

/**
 * Send an SMS through Twilio.
 *
 * @return array{0: bool, 1: string}  [success, sid-or-error-message]
 */
function twilio_send_sms(string $to, string $body): array
{
    $sid   = setting('twilio_sid');
    $token = setting('twilio_token');
    $from  = setting('twilio_from');

    if ($sid === '' || $token === '' || $from === '' || $to === '') {
        return [false, 'Twilio is not fully configured (check Settings > Integrations).'];
    }

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'From' => $from,
            'To'   => $to,
            'Body' => mb_substr($body, 0, 1500),
        ]),
        CURLOPT_USERPWD        => $sid . ':' . $token,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false) {
        return [false, 'Connection error: ' . $curlErr];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($data['sid'])) {
        return [true, $data['sid']];
    }

    $message = is_array($data) && isset($data['message'])
        ? $data['message']
        : 'HTTP ' . $httpCode . ' from Twilio';
    return [false, $message];
}

/**
 * Notify the owner about a new quote request. Never throws.
 *
 * @return array{0: bool, 1: string}
 */
function twilio_notify_quote(array $quote): array
{
    if (setting('sms_enabled', '0') !== '1') {
        return [false, 'SMS notifications are disabled in settings.'];
    }
    $body = "New quote request — " . setting('site_name', 'Worldwide Handyman') . "\n"
        . 'Name: '    . $quote['name'] . "\n"
        . 'Phone: '   . $quote['phone'] . "\n"
        . ($quote['email'] !== '' ? 'Email: ' . $quote['email'] . "\n" : '')
        . ($quote['service'] !== '' ? 'Service: ' . $quote['service'] . "\n" : '')
        . 'Details: ' . mb_substr($quote['message'], 0, 400);

    try {
        return twilio_send_sms(setting('twilio_to'), $body);
    } catch (Throwable $e) {
        return [false, $e->getMessage()];
    }
}
