# Preferences for Laravel Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gajosu/eloquent-preferences.svg?style=flat-square)](https://packagist.org/packages/gajosu/eloquent-preferences)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/gajosu/eloquent-preferences/run-tests?label=tests)](https://github.com/gajosu/eloquent-preferences/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/gajosu/eloquent-preferences/Check%20&%20fix%20styling?label=code%20style)](https://github.com/gajosu/eloquent-preferences/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/gajosu/eloquent-preferences.svg?style=flat-square)](https://packagist.org/packages/gajosu/eloquent-preferences)

Use this library to bind multiple key/value pair preferences to your application's Eloquent models. Preferences are stored in your application's database so they can be easily stored and queried for. This library supports Eloquent 5 through 8 installed either standalone or as a part of the full Laravel framework. Issues and pull requests are welcome! See [CONTRIBUTING.md](https://github.com/gajosu/eloquent-preferences/blob/master/CONTRIBUTING.md) for more information.

* [Installation](#installation)
  * [Configuring In Laravel](#configuring-in-laravel)
  * [Configuring Without Laravel](#configuring-without-laravel)
  * [Enable Cache](#enable-cache)
* [Usage](#usage)
  * [Helper Methods](#helper-methods)
    * [Retrieving Preferences](#retrieving-preferences)
    * [Setting Preferences](#setting-preferences)
    * [Removing Preferences](#removing-preferences)
  * [Default Preference Values](#default-preference-values)
  * [Casting Preference Values](#casting-preference-values)
  * [Hidden Preference Attributes](#hidden-preference-attributes)

<a name="installation"></a>
## Installation

Run `composer require gajosu/eloquent-preferences` to download and install the library.

<a name="configuring-in-laravel"></a>
### Configuring In Laravel

1) Add `EloquentPreferencesServiceProvider` to `config/app.php`:

```php
// ...

return [

    // ...

    'providers' => [

        // ...

        Gajosu\EloquentPreferences\EloquentPreferencesServiceProvider::class,
    ],

    // ...
];
```

2) Install the configuration and database migration files:

```
$ php artisan vendor:publish
```

3) Model preferences are stored in the "model_preferences" database table by default. If you would like to use a different table then edit the "table" entry in `config/eloquent-preferences.php`.

4) Install the model preferences database:

```
$ php artisan migrate
```

<a name="configuring-without-laravel"></a>
### Configuring Without Laravel

1) Model preferences are stored in the "model_preferences" database table by default. If you would like to use a different table then define the `MODEL_PREFERENCE_TABLE` constant at your project's point of entry with your preferred table name.

2) Install the model preferences database. There are a number of ways to do this outside of Laravel. Here's the schema blueprint to apply:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Gajosu\EloquentPreferences\Preference;

// ...

Model::getConnectionResolver()
    ->connection()
    ->getSchemaBuilder()
    ->create((new Preference)->getQualifiedTableName(), function (Blueprint $table) {
        $table->increments('id');
        $table->string('preference');
        $table->string('value');
        $table->morphs('preferable');
        $table->timestamps();
    });

```
<a name="enable-cache"></a>
## Enable Cache
By default the cache is disabled, to enable it edit the "cache.enabled" entry in `config/eloquent-preferences.php` change the value to `true,`, you can also specify the cache prefix

```php
'cache' => [
    'enabled' => true,
    'prefix' => 'eloquent-preferences',
]
```

<a name="usage"></a>
## Usage

Add the `HasPreferences` trait to the Eloquent models that you would like to have related preferences.

```php
use Gajosu\EloquentPreferences\HasPreferences;

// ...

class MyModel extends Model
{
    use HasPreferences;

    // ...
}
```

This builds a polymorphic has-many relationship called "preferences" that you can query on your model like any other Eloquent relationship. Model preferences are modeled in the `Gajosu\EloquentPreferences\Preference` class. A preference object has `preference`, `value`, and Eloquent's built-in `created_at` and `updated_at` attributes. The `HasPreferences` trait can be used by any number of model classes in your application.

```php
// Retrieving preferences via Eloquent
/** @var Gajosu\EloquentPreferences\Preference $myPreference */
$myPreference = MyModel::find($someId)->preferences()->where('preference', 'my-preference')->get();

// Saving preferences via Eloquent
$preference = new Preference;
$preference->preference = 'some preference';
$preference->value = 'some value';
$myModel->preferences()->save($preference);
```

Eloquent queries can be run directly on the `Preference` class as well.

```php
/** @var Illuminate\Database\Eloquent\Collection|Gajosu\EloquentPreferences\Preference[] $preferences */
$preferences = Preference::whereIn('preference', ['foo', 'bar'])->orderBy('created_at')->get();
```

<a name="helper-methods"></a>
### Helper Methods

The `HasPreferences` trait has a number of helper methods to make preference management a little easier.

<a name="retrieving-preferences"></a>
#### Retrieving Preferences

Call the `getPreference($preferenceName)` or `prefers($preferenceName)` methods to retrieve that preference's value.

```php
$numberOfFoos = $myModel->getPreference('number-of-foos');

$myModel->prefers('Star Trek over Star Wars') ? liveLongAndProsper() : theForceIsWithYou();
```

<a name="setting-preferences"></a>
#### Setting Preferences

Call the `setPreference($name, $value)` or `setPreferences($arrayOfNamesAndValues)` methods to set your model's preference values. Setting a preference either creates a new preference row if the preference doesn't exist or updates the existing preference with the new value.

```php
$myModel->setPreference('foo', 'bar');

$myModel->setPreferences([
    'foo' => 'bar',
    'bar' => 'baz',
]);
```

<a name="removing-preferences"></a>
#### Removing Preferences

Call the `clearPreference($preferenceName)`, `clearPreferences($arrayOfPreferenceNames)`, or `clearAllPreferences()` methods to remove one, many, or all preferences from a model. Clearing preferences removes their associated rows from the preferences table.

```php
$myModel->clearPreference('some preference');

$myModel->clearPreferences(['some preference', 'some other preference']);

$myModel->clearAllPreferences();
```

<a name="default-preference-values"></a>
### Default Preference Values

By default, `getPreference()` and `prefers()` return `null` if the preference is not stored in the database. There are two ways to declare default preference values:

1) Use an optional second parameter to `getPreference()` and `prefers()` to define a default value per call. If the preference is not stored in the database then the default value is returned.

```php
// $myPreference = 'some default value'
$myPreference = $myModel->getPreference('unknown preference', 'some default value');
```

2) Avoid requiring extra parameters to every `getPreference()` and `prefers()` call by declaring a protected `$preference_defaults` array in your model containing a key/value pair of preference names and their default values. If the preference is not stored in the database but is defined in `$preference_defaults` then the value in `$preference_defaults` is returned. If neither of these exist then optional default value parameter or `null` is returned.

```php
class MyModel extends Model
{
    use HasPreferences;

    // ...

    protected $preference_defaults = [
        'my-default-preference' => 'my-default-value',
    ];
}

// ...

// $myPreference = 'my-default-value'
$myPreference = $myModel->getPreference('my-default-preference');

// $myPreference = 'fallback value'
$myPreference = $myModel->getPreference('my-unstored-preference', 'fallback value');
```

Please note default preference values only apply when using the `getPreference()` and `prefers()` methods. Default values are not honored when retrieving preferences by Eloquent query.

<a name="casting-preference-values"></a>
### Casting Preference Values

Preferences are stored as strings in the database, but can be cast to different types when retrieved.

Declare a protected `$preference_casts` array in your model containing a key/value pair of preference names and the types to cast their values to. Preferences are stored and cast according to the same rules as [Eloquent's attribute type casts](https://laravel.com/docs/5.2/eloquent-mutators#attribute-casting).

```php
class MyModel extends Model
{
    use HasPreferences;

    // ...

    protected $preference_casts = [
        'boolean-preference' => 'boolean',
        'floating-point-preference' => 'float',
        'date-preference' => 'date',
    ];
}
```

As with default values, casting preferences is only performed when using the `getPreference()`, `prefers()`, `setPreference()`, and `setPreferences()` helper methods.

<a name="hidden-preference-attributes"></a>
### Hidden Preference Attributes

By default all preference model attributes are visible when exporting to JSON. However it is possible to declare hidden attributes that act in the same manner as [Eloquent's hidden attributes](https://laravel.com/docs/5.2/eloquent-serialization#hiding-attributes-from-json). There are two ways to declare which preference attributes to hide from JSON export:

1) If this library is being used in a Laravel project then declare hidden attributes in the "hidden-attributes" key in `config/eloquent-preferences.php`.

```
return [

    // ...

    'hidden-attributes' => ['created_at', 'updated_at'],

    // ...
];
```

2) If this library is being used outside the Laravel framework then define the `MODEL_PREFERENCE_HIDDEN_ATTRIBUTES` constant at your project's point of entry with a comma-separated list of attributes to hide from JSON export.

```php
const MODEL_PREFERENCE_HIDDEN_ATTRIBUTES = 'created_at,updated_at';
```
