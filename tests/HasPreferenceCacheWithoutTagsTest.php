<?php

namespace Gajosu\EloquentPreferences\Tests;

;

use Gajosu\EloquentPreferences\Facades\CacheModule;

/**
 * This test's structure is based off the Laravel Framework's SoftDeletes trait
 * test.
 *
 * @see https://github.com/laravel/framework/blob/5.3/tests/Database/DatabaseEloquentSoftDeletesIntegrationTest.php
 */
class HasPreferenceCacheWithoutTagsTest extends HasPreferenceCacheTest
{
    /**
     * Set up the test database schema and data.
     */
    public function setUp(): void
    {
        parent::setUp();
        CacheModule::fake();
    }
}
