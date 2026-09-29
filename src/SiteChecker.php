<?php

namespace App;

class SiteChecker
{
    public function __construct(private int $timeout = 10) {}

    public function check(string $url): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => 'SentinelaQ/1.0',
        ]);

        curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);

        return [
            'url' => $url,
            'status' => $status,
            'ok' => $status >= 200 && $status < 400,
            'time_ms' => (int) ($time * 1000),
            'error' => $error ?: null,
            'checked_at' => date('Y-m-d H:i:s'),
        ];
    }
}
