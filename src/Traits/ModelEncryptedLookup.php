<?php

namespace BoldlyGrow\AuditLog\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait ModelEncryptedLookup
{
    /**
     * Create an array of IDs and the key being searched and cache for faster lookup.
     *
     * @param  string  $key    The encrypted string column to search
     * @param  bool    $cache  Whether to use recently cached values. Set to false for fresh data.
     *
     * @return array<array-key, string>
     */
    protected static function cacheStringRecords(string $key, bool $cache = true): array
    {
        $cache_key = 'encrypted:lookup:' . Str::snake(Str::replace('App\\Models\\', '', self::class)) . ':' . $key;

        if (! $cache) {
            Cache::forget($cache_key);
        }

        /** @var array<array-key, string> $records */
        $records = decrypt(Cache::remember(
            key: $cache_key,
            ttl: now()->addMinutes(2),
            callback: fn () => encrypt(
                App::make(self::class)->withTrashed()
                    ->pluck($key, 'id')
                    ->transform(fn ($value) => is_scalar($value) ? strtolower((string) $value) : '')
                    ->toArray()
            )
        ));

        return $records;
    }

    /**
     * Create an array of IDs and the nested array key being searched and cache for faster lookup.
     *
     * @param  string  $column  The encrypted column to search
     * @param  string  $key     The nested array key to search
     * @param  bool    $cache   Whether to use recently cached values. Set to false for fresh data.
     *
     * @return array<array-key, string>
     */
    protected static function cacheArrayKeyRecords(string $column, string $key, bool $cache = true): array
    {
        $cache_key = 'encrypted:lookup:' . Str::snake(Str::replace('App\\Models\\', '', self::class)) . ':' . $column . ':' . $key;

        if (! $cache) {
            Cache::forget($cache_key);
        }

        /** @var array<array-key, string> $records */
        $records = decrypt(Cache::remember(
            key: $cache_key,
            ttl: now()->addMinutes(2),
            callback: fn () => encrypt(
                App::make(self::class)->withTrashed()
                    ->pluck($column, 'id')
                    ->filter(fn ($column_array) => is_array($column_array) && array_key_exists($key, $column_array))
                    ->transform(function ($column_array) use ($key) {
                        $value = data_get($column_array, $key);

                        return is_scalar($value) ? strtolower((string) $value) : '';
                    })
                    ->toArray()
            )
        ));

        return $records;
    }

    /**
     * Get the metadata keys used in the encrypted array column
     *
     * @param  string  $column  The encrypted array column to get the array keys from
     * @param  bool    $cache   Whether to use caching for the result
     *
     * @return array<int, string>
     */
    public static function encryptedArrayKeys(string $column, bool $cache = true): array
    {
        $cache_key = 'encrypted:lookup:' . Str::snake(Str::replace('App\\Models\\', '', self::class)) . ':' . $column . ':keys';

        if (! $cache) {
            Cache::forget($cache_key);
        }

        /** @var array<int, string> $keys */
        $keys = decrypt(Cache::remember(
            key: $cache_key,
            ttl: now()->addMinutes(2),
            callback: fn () => encrypt(
                App::make(self::class)
                    ->pluck($column)
                    ->transform(fn ($column_array) => is_array($column_array) ? array_keys($column_array) : [])
                    ->flatten()
                    ->unique()
                    ->values()
                    ->all()
            )
        ));

        return $keys;
    }

    /**
     * Get records that have an exact string match in an encrypted column
     *
     * @example
     *      $users = AuditLog::whereEncryptedStringExact('actor_name', 'John Smith')->get();
     *      $users = AuditLog::whereEncryptedStringExact('actor_email', 'jsmith@acme.com')->first();
     *
     * @param  Builder<Model>  $query
     * @param  string          $key    The encrypted string column to search
     * @param  string          $value  The exact string value to match
     * @param  bool            $cache  Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedStringExact(Builder $query, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheStringRecords(key: $key, cache: $cache))
            ->filter(fn ($item) => $item === strtolower($value))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Get records that have a partial string match in an encrypted column
     *
     * @example
     *      $users = AuditLog::whereEncryptedStringPartial('actor_name', 'John')->get();
     *      $users = AuditLog::whereEncryptedStringPartial('actor_email', 'acme.com')->first();
     *
     * @param  Builder<Model>  $query
     * @param  string          $key    The encrypted string column to search
     * @param  string          $value  The partial string value to search for
     * @param  bool            $cache  Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedStringPartial(Builder $query, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheStringRecords(key: $key, cache: $cache))
            ->filter(fn ($item) => str_contains(strtolower($item), strtolower($value)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Get records that start with a partial string in an encrypted column
     *
     * @example
     *      $users = AuditLog::whereEncryptedStringStartsWith('actor_name', 'John')->get();
     *      $users = AuditLog::whereEncryptedStringStartsWith('actor_email', 'jsmith')->first();
     *
     * @param  Builder<Model>  $query
     * @param  string          $key    The encrypted string column to search
     * @param  string          $value  The partial string value to search for
     * @param  bool            $cache  Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedStringStartsWith(Builder $query, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheStringRecords(key: $key, cache: $cache))
            ->filter(fn ($item) => str_starts_with($item, strtolower($value)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Get records that end with a partial string in an encrypted column
     *
     * @example
     *      $users = AuditLog::whereEncryptedStringEndsWith('actor_name', 'Smith')->get();
     *      $users = AuditLog::whereEncryptedStringEndsWith('actor_email', 'acme.com')->first();
     *
     * @param  Builder<Model>  $query
     * @param  string          $key    The encrypted string column to search
     * @param  string          $value  The partial string value to search for
     * @param  bool            $cache  Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedStringEndsWith(Builder $query, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheStringRecords(key: $key, cache: $cache))
            ->filter(fn ($item) => str_ends_with($item, strtolower($value)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Search an array for a partial value in any array key
     *
     * @example
     *      $users = AuditLog::whereEncryptedArraySearch('metadata', 'John')->get();
     *
     * @param  Builder<Model>  $query
     * @param  string          $column  The encrypted array column to search
     * @param  string          $search  The partial string array value to search for
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedArraySearch(Builder $query, string $column, string $search): Builder
    {
        $ids = collect(App::make(self::class)->withTrashed()->pluck($column, 'id'))
            ->transform(fn ($array) => (string) json_encode($array))
            ->filter(fn ($json) => Str::contains(haystack: Str::lower($json), needles: Str::lower($search)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Search an array for any value for a specific array key that has an exact string match
     *
     * @example
     *      $users = AuditLog::whereEncryptedArrayExact('metadata', 'approved_by', 'John Smith')->get();
     *
     * @param  Builder<Model>  $query
     * @param  string          $column  The encrypted column to search
     * @param  string          $key     The nested array key to search
     * @param  string          $value   The exact string array value to match
     * @param  bool            $cache   Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedArrayExact(Builder $query, string $column, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheArrayKeyRecords(column: $column, key: $key, cache: $cache))
            ->filter(fn ($item) => $item === strtolower($value))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Search an array for any value for a specific array key that has a partial string match
     *
     * @example
     *      $users = AuditLog::whereEncryptedArrayPartial('metadata', 'approved_by', 'John')->get();
     *
     * @param  Builder<Model>  $query
     * @param  string          $column  The encrypted column to search
     * @param  string          $key     The nested array key to search
     * @param  string          $value   The partial string array value to match
     * @param  bool            $cache   Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedArrayPartial(Builder $query, string $column, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheArrayKeyRecords(column: $column, key: $key, cache: $cache))
            ->filter(fn ($item) => str_contains($item, strtolower($value)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Search an array for any value for a specific array key that starts with a partial string match
     *
     * @example
     *      $users = AuditLog::whereEncryptedArrayStartsWith('metadata', 'approved_by', 'John')->get();
     *
     * @param  Builder<Model>  $query
     * @param  string          $column  The encrypted column to search
     * @param  string          $key     The nested array key to search
     * @param  string          $value   The prefix string array value to match
     * @param  bool            $cache   Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedArrayStartsWith(Builder $query, string $column, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheArrayKeyRecords(column: $column, key: $key, cache: $cache))
            ->filter(fn ($item) => str_starts_with($item, strtolower($value)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Search an array for any value for a specific array key that ends with a partial string match
     *
     * @example
     *      $users = AuditLog::whereEncryptedArrayEndsWith('metadata', 'approved_by', 'Smith')->get();
     *
     * @param  Builder<Model>  $query
     * @param  string          $column  The encrypted column to search
     * @param  string          $key     The nested array key to search
     * @param  string          $value   The suffix string array value to match
     * @param  bool            $cache   Whether to use recently cached values. Set to false for fresh data.
     *
     * @return Builder<Model>
     */
    public function scopeWhereEncryptedArrayEndsWith(Builder $query, string $column, string $key, string $value, bool $cache = true): Builder
    {
        $ids = collect(static::cacheArrayKeyRecords(column: $column, key: $key, cache: $cache))
            ->filter(fn ($item) => str_ends_with($item, strtolower($value)))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('id', $ids);
    }
}
