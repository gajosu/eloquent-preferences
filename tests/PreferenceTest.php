<?php

namespace Gajosu\EloquentPreferences\Tests;

use Gajosu\EloquentPreferences\Preference;
use Gajosu\EloquentPreferences\Tests\TestCase;

class PreferenceTest extends TestCase
{
    public function testSetTheDefaultTableName()
    {
        $this->assertEquals(Preference::DEFAULT_MODEL_PREFERENCE_TABLE, (new Preference())->getTable());
    }

    public function testSetTheTableNameByLaravelConfig()
    {
        config()->set('eloquent-preferences.table', 'foo-function');
        $this->assertEquals('foo-function', (new Preference())->getTable());
    }

    public function testPreferencesHaveNoHiddenAttributesByDefault()
    {
        $this->assertEquals([], (new Preference())->getHidden());
    }

    public function testSetHiddenAttributesByLaravelConfig()
    {
        config()->set('eloquent-preferences.hidden-attributes', ['foo', 'function']);
        $this->assertEquals(['foo', 'function'], (new Preference())->getHidden());
    }
}
