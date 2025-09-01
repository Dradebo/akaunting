<?php

namespace Tests;

use App\Traits\Jobs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, Jobs, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Optionally run the full migration set for a stable test schema.
        // Set RUN_FULL_MIGRATIONS=true in your test environment to enable.
        if (env('RUN_FULL_MIGRATIONS', false)) {
            // Fresh migrate to ensure the test DB matches production schema.
            // Don't use --seed here because the application does not provide a DatabaseSeeder class in this context.
            $this->artisan('migrate:fresh', ['--force' => true]);

            // Seed the minimal TestCompany used by tests.
            $this->artisan('db:seed', ['--class' => '\\Database\\Seeds\\TestCompany', '--force' => true]);
        } else {
            // Keep the fast path used during development: seed the minimal TestCompany.
            $this->artisan('db:seed', ['--class' => '\\Database\\Seeds\\TestCompany', '--force' => true]);
        }
    }

}
