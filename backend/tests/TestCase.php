<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Prevent tests from touching external services by default.
        Queue::fake();
        Mail::fake();

        // Flush Redis between tests so OTP rate-limit keys (TTL 3600 s) set
        // during one run do not bleed into the next run and cause 422 "Too many
        // OTP requests" failures.  We swallow any connection error so that
        // tests which do not need Redis are never blocked by it being offline.
        try {
            Redis::flushdb();
        } catch (Throwable) {
            // Redis is not running — tests that need it will skip via requireRedis()
        }
    }

    // ─── Better failure output ─────────────────────────────────────────────────

    /**
     * When any assertion fails, dump the last HTTP response body alongside
     * the normal failure message so you can see what the API actually returned
     * without having to add dd() calls to your tests.
     */
    protected function onNotSuccessfulTest(Throwable $t): never
    {
        // $this->response is set by Laravel's MakesHttpRequests trait
        // whenever you call getJson(), postJson(), etc.
        if (isset($this->response)) {
            $status = $this->response->getStatusCode();
            $body = $this->response->getContent();

            // Pretty-print if it's JSON, otherwise show raw.
            $decoded = json_decode($body, true);
            $pretty = $decoded !== null
                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $body;

            // Prepend the HTTP context to the failure message so it appears
            // right above the assertion that failed in IntelliJ's test output.
            $extra = "\n── Last HTTP response ──────────────────────────────────\n"
                . "Status : {$status}\n"
                . "Body   :\n{$pretty}\n"
                . "────────────────────────────────────────────────────────\n";

            throw new AssertionFailedError($extra . "\n" . $t->getMessage(), 0, $t);
        }

        throw $t;
    }

    // ─── Infrastructure guard helpers ─────────────────────────────────────────
    // Call these at the top of setUp() in tests that need a specific
    // extension or service. They emit a clean SKIPPED notice instead of a
    // cryptic vendor stack trace when the resource is unavailable.

    /**
     * Skip this test if a required PHP extension is not loaded.
     *
     * Usage:  $this->requireExtension('gd');
     */
    protected function requireExtension(string $extension): void
    {
        if (!extension_loaded($extension)) {
            $this->markTestSkipped(
                "PHP extension \"{$extension}\" is not loaded. "
                . 'Enable it in your php.ini and restart the test runner.'
            );
        }
    }

    /**
     * Skip this test if the configured Redis server is unreachable.
     *
     * Usage:  $this->requireRedis();
     */
    protected function requireRedis(): void
    {
        $this->loadTestEnvIfNeeded();

        $host = env('REDIS_HOST', '127.0.0.1');
        $port = (int)env('REDIS_PORT', 6379);

        $socket = @fsockopen($host, $port, $errno, $errstr, timeout: 1.0);

        if ($socket === false) {
            $this->markTestSkipped(
                "Redis is not reachable at {$host}:{$port} ({$errstr}). "
                . 'Start your Docker stack (docker compose -f docker-compose.local.yml up -d) before running this test.'
            );
        }

        fclose($socket);
    }

    /**
     * Skip this test if the configured database is unreachable.
     *
     * Usage:  $this->requireDatabase();
     */
    protected function requireDatabase(): void
    {
        $this->loadTestEnvIfNeeded();

        $host = env('DB_HOST', '127.0.0.1');
        $port = (int)env('DB_PORT', 5432);

        $socket = @fsockopen($host, $port, $errno, $errstr, timeout: 1.0);

        if ($socket === false) {
            $this->markTestSkipped(
                "Database is not reachable at {$host}:{$port} ({$errstr}). "
                . 'Start your Docker stack (docker compose -f docker-compose.local.yml up -d) before running this test.'
            );
        }

        fclose($socket);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Load .env.testing into $_ENV / $_SERVER using Dotenv so that env() calls
     * inside requireDatabase() and requireRedis() return the correct values even
     * when those guards are called *before* parent::setUp() boots the application
     * (which is the normal pattern in FeatureTestCase / OTPServiceTest setUp).
     *
     * createImmutable() means already-set variables are never overwritten, so
     * calling this after the app has booted is perfectly safe.
     */
    private static bool $testEnvLoaded = false;

    private function loadTestEnvIfNeeded(): void
    {
        if (self::$testEnvLoaded) {
            return;
        }

        self::$testEnvLoaded = true;

        // __DIR__ is the tests/ directory; the backend root is one level up.
        $root = dirname(__DIR__);
        $envFile = '.env.testing';

        if (file_exists("{$root}/{$envFile}")) {
            \Dotenv\Dotenv::createImmutable($root, $envFile)->safeLoad();
        }
    }
}
