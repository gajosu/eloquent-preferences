<?php

namespace Gajosu\EloquentPreferences;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Assign preferences to an Eloquent Model.
 *
 * Add `use HasPreferences;` to your model class to associate preferences with
 * that model.
 *
 * @property array $preference_defaults
 * @property array $preference_casts
 * @property \Illuminate\Database\Eloquent\Collection|\Gajosu\EloquentPreferences\Preference[] $preferences
 */
trait HasPreferences
{
    /**
     * A model can have many preferences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferable');
    }

    /**
     * Retrieve a single preference by name.
     *
     * @param string $preference
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getPreference(string $preference, mixed $defaultValue = null): mixed
    {
        // If the cache is not enabled, fetch the preference from the database.
        if (! CacheModule::cacheIsEnabled()) {
            return $this->getPreferenceFromDatabase($preference, $defaultValue);
        }

        // Check if the preference exists in cache.
        if (CacheModule::existsPreference($this, $preference)) {
            $value = CacheModule::getPreference($this, $preference);

            return $value === null ? $defaultValue : $value;
        }

        // if the preference does not exist in cache, fetch it from the database.
        $value = $this->getPreferenceFromDatabase($preference, $defaultValue);
        CacheModule::setPreference($this, $preference, $value);

        return $value;
    }

    /**
     * Get a preference from the database.
     *
     * @param string $preference
     * @param mixed $defaultValue
     * @return mixed
     */
    private function getPreferenceFromDatabase(string $preference, mixed $defaultValue = null): mixed
    {
        $savedPreference = $this->preferences()->where('preference', $preference)->first();

        $value = $savedPreference === null
            ? $this->getDefaultValue($preference, $defaultValue)
            : $savedPreference->value;

        return $this->castPreferenceValue($preference, $value);
    }

    /**
     * A possibly more human-readable way to retrieve a single preference by
     * name.
     *
     * @see getPreference()
     * @param string $preference
     * @param mixed $defaultValue
     * @return mixed
     */
    public function prefers(string $preference, mixed $defaultValue = null): mixed
    {
        return $this->getPreference($preference, $defaultValue);
    }

    /**
     * Set an individual preference value in the database.
     *
     * @param string $preference
     * @param mixed $value
     * @return self
     */
    public function setPreference(string $preference, mixed $value): self
    {
        $value = $this->savePreferenceInDatabase($preference, $value);

        // Update the cache if caching is enabled.
        if (CacheModule::cacheIsEnabled()) {
            CacheModule::setPreference($this, $preference, $value);
        }

        return $this;
    }

    /**
     * save preference value in the database.
     *
     * @param string $preference
     * @param mixed $value
     * @return mixed
     */
    private function savePreferenceInDatabase(string $preference, mixed $value): mixed
    {
        // Serialize date and JSON-like preference values.
        if ($this->isPreferenceDateCastable($preference)) {
            $value = $this->fromDateTime($value);
        } elseif ($this->isPreferenceJsonCastable($preference)) {
            $value = method_exists($this, 'asJson') ? $this->asJson($value) : json_encode($value);
        }

        /** @var Preference $savedPreference */
        $savedPreference = $this->preferences()->firstOrNew([
            'preference' => $preference,
        ]);

        $savedPreference->value = $value;
        $savedPreference->save();

        return $value;
    }

    /**
     * Set multiple preference values.
     *
     * @param array|\Illuminate\Support\Collection $preferences
     * @return self
     */
    public function setPreferences(array|Collection $preferences = []): self
    {
        foreach ($preferences as $preference => $value) {
            $this->setPreference($preference, $value);
        }

        return $this;
    }

    /**
     * Delete one preference.
     *
     * @param string $preference
     * @return self
     */
    public function clearPreference(string $preference): self
    {
        $this->preferences()->where('preference', $preference)->delete();

        if (CacheModule::cacheIsEnabled()) {
            CacheModule::deletePreference($this, $preference);
        }

        return $this;
    }

    /**
     * Delete many preferences.
     *
     * @param array|\Illuminate\Support\Collection $preferences $preferences
     * @return self
     */
    public function clearPreferences(array|Collection $preferences = []): self
    {
        $this->preferences()->whereIn('preference', $preferences)->delete();

        if (CacheModule::cacheIsEnabled()) {
            foreach ($preferences as $preference) {
                CacheModule::deletePreference($this, $preference);
            }
        }

        return $this;
    }

    /**
     * Delete all preferences.
     *
     * @return self
     */
    public function clearAllPreferences(): self
    {
        $this->preferences()->delete();

        if (CacheModule::cacheIsEnabled()) {
            CacheModule::deleteAllPreferences($this);
        }

        return $this;
    }

    /**
     * Retrieve a preference's default value.
     *
     * Look in the model first, otherwise return the user specified default
     * value.
     *
     * @param string $preference
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getDefaultValue(string $preference, mixed $defaultValue = null): mixed
    {
        if ($this->hasPreferenceDefault($preference)) {
            return $this->preference_defaults[$preference];
        }

        return $defaultValue;
    }

    /**
     * Determine if a model has preference defaults defined.
     *
     * @return bool
     */
    protected function hasPreferenceDefaults(): bool
    {
        return property_exists($this, 'preference_defaults') && is_array($this->preference_defaults);
    }

    /**
     * Determine if a model has a default preference value defined.
     *
     * @param string $preference
     * @return bool
     */
    protected function hasPreferenceDefault(string $preference): bool
    {
        return $this->hasPreferenceDefaults() && array_key_exists($preference, $this->preference_defaults);
    }

    /**
     * Determine if a model has preference casts defined.
     *
     * @return bool
     */
    protected function hasPreferenceCasts(): bool
    {
        return property_exists($this, 'preference_casts') && is_array($this->preference_casts);
    }

    /**
     * Determine if a model has a preference's type cast defined.
     *
     * @param string $preference
     * @param array $types
     * @return bool
     */
    protected function hasPreferenceCast(string $preference, array $types = null): bool
    {
        if ($this->hasPreferenceCasts() && array_key_exists($preference, $this->preference_casts)) {
            return $types
                ? in_array($this->getPreferenceCastType($preference), $types, true)
                : true;
        }

        return false;
    }

    /**
     * Determine whether a preference value is Date / DateTime castable for
     * inbound manipulation.
     *
     * @param string $preference
     * @return bool
     */
    protected function isPreferenceDateCastable(string $preference): bool
    {
        return $this->hasPreferenceCast($preference, ['date', 'datetime']);
    }

    /**
     * Determine whether a preference value is JSON castable for inbound
     * manipulation.
     *
     * @param string $preference
     * @return bool
     */
    protected function isPreferenceJsonCastable(string $preference): bool
    {
        return $this->hasPreferenceCast($preference, ['array', 'json', 'object', 'collection']);
    }

    /**
     * Retrieve the type of variable to cast a preference value to.
     *
     * @param string $preference
     * @return string|null
     */
    protected function getPreferenceCastType(string $preference): ?string
    {
        return $this->hasPreferenceCast($preference)
            ? strtolower(trim($this->preference_casts[$preference]))
            : null;
    }

    /**
     * Cast a preference value's type.
     *
     * @param string $preference
     * @param mixed $value
     * @return mixed
     */
    protected function castPreferenceValue(string $preference, mixed $value): mixed
    {
        $castTo = $this->getPreferenceCastType($preference);

        // Cast Eloquent >= 5.0 compatible types.
        switch ($castTo) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return method_exists($this, 'fromJson')
                    ? $this->fromJson($value, true)
                    : json_decode($value, false);
            case 'array':
            case 'json':
                return method_exists($this, 'fromJson')
                    ? $this->fromJson($value)
                    : json_decode($value, true);
            case 'collection':
                return new BaseCollection(
                    method_exists($this, 'fromJson')
                        ? $this->fromJson($value)
                        : json_decode($value, true)
                );
        }

        // Cast Eloquent >= 5.1 compatible types.
        if (method_exists($this, 'asDateTime')) {
            switch ($castTo) {
                case 'date':
                case 'datetime':
                    return $this->asDateTime($value);
            }
        }

        // Cast Eloquent >= 5.2 compatible types.
        if ($castTo === 'timestamp' && method_exists($this, 'asTimeStamp')) {
            return $this->asTimeStamp($value);
        }

        // Case Eloquent >= 5.7 compatible types
        if (method_exists($this, 'asDecimal') && strpos($castTo, 'decimal:') === 0) {
            return $this->asDecimal($value, explode(':', $castTo, 2)[1]);
        }

        return $value;
    }
}
