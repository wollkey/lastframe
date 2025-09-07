<?php

declare(strict_types=1);

namespace App;

interface HttpClientInterface
{
    public function get(string $path): string;
    public function post(string $path, array $options): string;
}