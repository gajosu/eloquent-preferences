<?php

namespace Gajosu\EloquentPreferences\Tests;

use CreateModelPreferencesTable;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Gajosu\EloquentPreferences\Preference;
use Gajosu\EloquentPreferences\CacheModule;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Gajosu\EloquentPreferences\Tests\Models\TestUser;
use Gajosu\EloquentPreferences\Tests\Support\ConnectionResolver;

/**
 * This test's structure is based off the Laravel Framework's SoftDeletes trait
 * test.
 *
 * @see https://github.com/laravel/framework/blob/5.3/tests/Database/DatabaseEloquentSoftDeletesIntegrationTest.php
 */
class HasPreferenceCacheTest extends TestCase
{
    /**
     * A test user model with preferences.
     *
     * @var TestUser
     */
    protected $testUser;

    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        Eloquent::setConnectionResolver(new ConnectionResolver());
        Eloquent::setEventDispatcher(new Dispatcher());
    }

    /**
     * Tear down Eloquent.
     */
    public static function tearDownAfterClass(): void
    {
        Eloquent::unsetEventDispatcher();
        Eloquent::unsetConnectionResolver();
    }

    /**
     * Set up the test database schema and data.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        (new CreateModelPreferencesTable())->up();

        $this->testUser = TestUser::create(['id' => 1, 'email' => 'johndoe@example.org']);

        config()->set('eloquent-preferences.cache.enabled', true);
    }

    /**
     * Tear down the database schema.
     */
    public function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop(Preference::DEFAULT_MODEL_PREFERENCE_TABLE);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    public function testSetPreferences()
    {
        $result = $this->testUser->setPreferences([
            'preference1' => 'value1',
            'preference2' => 'value2',
        ]);

        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $preference2 = CacheModule::getPreference($this->testUser, 'preference2');

        $this->assertEquals('value1', $preference1);
        $this->assertEquals('value2', $preference2);
    }

    public function testGetExistingPreference()
    {
        CacheModule::setPreference($this->testUser, 'preference1', 'value1');
        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $this->assertEquals('value1', $preference1);
    }

    public function testGetNonExistingPreference()
    {
        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $this->assertNull($preference1);
    }

    public function testGetExistingPreferenceStoredWhenTheCacheWasDisabled()
    {
        config()->set('eloquent-preferences.cache.enabled', false);
        // Set the preference in the database
        $this->testUser->setPreference('preference1', 'value1');
        // Get the preference from the cache must return null because the cache is disabled
        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $this->assertNull($preference1);

        // enable the cache again
        config()->set('eloquent-preferences.cache.enabled', true);
        // Get the preference from the cache must return the value
        $preference1 = $this->testUser->getPreference('preference1');
        $this->assertEquals('value1', $preference1);
        // and the preference must be save in cache
        $exists = CacheModule::existsPreference($this->testUser, 'preference1');
        $this->assertTrue($exists);
    }

    public function testOverridePreferences()
    {
        $this->testUser->setPreference('preference', 'value1');
        $firstValue = CacheModule::getPreference($this->testUser, 'preference');

        $this->testUser->setPreference('preference', 'value2');
        $secondValue = CacheModule::getPreference($this->testUser, 'preference');

        $this->assertEquals('value1', $firstValue);
        $this->assertEquals('value2', $secondValue);
    }

    public function testClearOnePreference()
    {
        $this->testUser->setPreferences([
            'preference1' => 'value1',
            'preference2' => 'value2',
        ]);

        $this->testUser->clearPreference('preference1');

        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $preference2 = CacheModule::getPreference($this->testUser, 'preference2');

        $this->assertNull($preference1);
        $this->assertEquals('value2', $preference2);
    }

    public function testClearManyPreferences()
    {
        $this->testUser->setPreferences([
            'preference1' => 'value1',
            'preference2' => 'value2',
            'preference3' => 'value3',
        ]);

        $this->testUser->clearPreferences(['preference1', 'preference2']);

        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $preference2 = CacheModule::getPreference($this->testUser, 'preference2');
        $preference3 = CacheModule::getPreference($this->testUser, 'preference3');

        $this->assertNull($preference1);
        $this->assertNull($preference2);
        $this->assertEquals('value3', $preference3);
    }

    public function testClearAllPreferences()
    {
        $this->testUser->setPreferences([
            'preference1' => 'value1',
            'preference2' => 'value2',
        ]);

        $result = $this->testUser->clearAllPreferences();

        $preference1 = CacheModule::getPreference($this->testUser, 'preference1');
        $preference2 = CacheModule::getPreference($this->testUser, 'preference2');

        $this->assertNull($preference1);
        $this->assertNull($preference2);
    }
}
