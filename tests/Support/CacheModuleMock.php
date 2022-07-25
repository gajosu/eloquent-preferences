<?php

namespace Gajosu\EloquentPreferences\Tests\Support;

use Gajosu\EloquentPreferences\CacheModule;

class CacheModuleMock extends CacheModule
{
    /**
     * Returns true if the cache driver supports tags.
     *
     * @return bool
     */
    public function cacheSupportsTags(): bool
    {
        return false;
    }
}
