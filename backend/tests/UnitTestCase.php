<?php

namespace Tests;

/**
 * Base class for unit tests — pure PHP logic, no database, no HTTP.
 *
 * Extends the shared TestCase which already sets up:
 *   - withoutExceptionHandling()
 *   - Queue::fake() / Mail::fake()
 *
 * Intentionally does NOT include RefreshDatabase. Unit tests should never
 * need a real database connection; if one is needed the test probably belongs
 * in FeatureTestCase instead.
 *
 * Migration path for existing tests that don't use the DB:
 *   Change:  use Tests\TestCase;
 *   To:      use Tests\UnitTestCase;
 *
 * For unit tests that still need Redis (e.g. OTPServiceTest), call:
 *   $this->requireRedis();
 * at the top of setUp() to get a clean SKIPPED message when Docker is off.
 */
abstract class UnitTestCase extends TestCase
{
    // No extra setup needed — everything is in the parent TestCase.
}
