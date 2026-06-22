<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function statefulHeaders(): array
    {
        return [
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173',
        ];
    }

    protected function statefulJson(string $method, string $uri, array $data = [], array $headers = [])
    {
        return $this->withHeaders(array_merge($this->statefulHeaders(), $headers))
            ->json($method, $uri, $data);
    }
}
