<?php

namespace Tests\Unit;

use App\Models\SystemError;
use App\Support\SensitiveDataRedactor;
use Tests\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    public function test_text_redacts_common_secret_values(): void
    {
        $text = 'password=super-secret token:abc123 Authorization: Bearer bearer-token username=admin';

        $redacted = SensitiveDataRedactor::text($text);

        $this->assertStringNotContainsString('super-secret', $redacted);
        $this->assertStringNotContainsString('abc123', $redacted);
        $this->assertStringNotContainsString('bearer-token', $redacted);
        $this->assertStringContainsString('username=admin', $redacted);
    }

    public function test_url_redacts_sensitive_query_values(): void
    {
        $url = SensitiveDataRedactor::url('https://example.test/login?username=admin&password=super-secret&token=abc123');
        $decodedUrl = urldecode((string) $url);

        $this->assertStringContainsString('username=admin', $decodedUrl);
        $this->assertStringContainsString('password=[redacted]', $decodedUrl);
        $this->assertStringContainsString('token=[redacted]', $decodedUrl);
        $this->assertStringNotContainsString('super-secret', $decodedUrl);
        $this->assertStringNotContainsString('abc123', $decodedUrl);
    }

    public function test_system_error_model_redacts_sensitive_attributes(): void
    {
        $error = new SystemError([
            'message' => 'Failed with password=super-secret',
            'trace' => 'Stack trace token=abc123',
            'url' => 'https://example.test/path?api_key=secret-key&kelas=7',
            'user_agent' => 'Browser token=ua-token',
        ]);

        $this->assertStringNotContainsString('super-secret', $error->message);
        $this->assertStringNotContainsString('abc123', $error->trace);
        $this->assertStringNotContainsString('secret-key', urldecode((string) $error->url));
        $this->assertStringNotContainsString('ua-token', $error->user_agent);
        $this->assertStringContainsString('kelas=7', urldecode((string) $error->url));
    }
}
