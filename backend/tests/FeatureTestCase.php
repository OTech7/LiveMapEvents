<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base class for feature (HTTP / integration) tests.
 *
 * Extends the shared TestCase which already sets up:
 *   - onNotSuccessfulTest() — dumps the HTTP response body on any assertion failure
 *   - Queue::fake() / Mail::fake()
 *
 * Additionally this class:
 *   - Refreshes the database before every test (RefreshDatabase)
 *   - Guards that the database is reachable before attempting a refresh
 *
 * Migration path for existing tests:
 *   Change:  use Tests\TestCase;
 *         + use Illuminate\Foundation\Testing\RefreshDatabase;
 *   To:      use Tests\FeatureTestCase;
 *   Remove:  use RefreshDatabase;   ← already included here
 */
abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Guard before parent::setUp() triggers the DB refresh.
        // Gives a clean SKIPPED notice instead of a confusing PDO error.
        $this->requireDatabase();

        parent::setUp();
    }
}
