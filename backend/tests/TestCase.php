<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    use  RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake queues and mail for fast, safe tests
        Queue::fake();
        Mail::fake();
    }
}