<?php

namespace JeffersonGoncalves\Privacy;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JeffersonGoncalves\Privacy\Contracts\HasPersonalData;

/**
 * Base of every suite's main class (Lgpd, Gdpr, Ccpa, UsPrivacy, Quebec): the models of the suite, its
 * personal data inventory and k-anonymity. A suite sets its config key: `protected string $config = 'gdpr';`.
 */
abstract class Privacy
{
    /** The suite's config file: models in "{$config}.models", hash key in "{$config}.hash_key". */
    protected string $config;

    /**
     * The contract the suite's models implement.
     *
     * @var class-string<HasPersonalData>
     */
    protected string $contract = HasPersonalData::class;

    public function config(): string
    {
        return $this->config;
    }

    /**
     * Key for hashes and fingerprints: "{$config}.hash_key", falling back to app.key.
     */
    public function hashKey(): string
    {
        return (string) (config("{$this->config}.hash_key") ?: config('app.key'));
    }

    /**
     * Models registered in config("{$config}.models").
     *
     * @return list<class-string<Model&HasPersonalData>>
     */
    public function models(): array
    {
        $models = [];

        foreach ((array) config("{$this->config}.models", []) as $model) {
            if (! is_string($model) || ! is_subclass_of($model, Model::class) || ! is_subclass_of($model, $this->contract)) {
                throw new InvalidArgumentException("{$this->config}.models entries must be Eloquent models implementing {$this->contract}, got: ".var_export($model, true));
            }

            /** @var class-string<Model&HasPersonalData> $model */
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Personal data inventory: one row per declared column (the suite's attribute fields, plus model and table).
     *
     * @return list<array<string, mixed>>
     */
    public function inventory(): array
    {
        $rows = [];

        foreach ($this->models() as $model) {
            $table = (new $model)->getTable();

            foreach ($model::personalDataFields() as $field) {
                $rows[] = ['model' => $model, 'table' => $table] + $field->toArray();
            }
        }

        return $rows;
    }

    /**
     * Measure k-anonymity over quasi-identifiers: every combination of their values must be
     * shared by at least k rows. With a sensitive column, l is the smallest number of distinct
     * sensitive values inside a group (l-diversity).
     *
     * ponytail: scans every row in PHP (casts and generalizers apply), so run it off-peak on big tables.
     *
     * @param  class-string<Model>|Builder<Model>  $source
     * @param  array<int|string, string|Closure>  $quasiIdentifiers  columns, or column => generalizer (see Generalize)
     * @return array{rows: int, groups: int, k: int, rows_below_k: int, l: int|null}
     */
    public function kAnonymity(string|Builder $source, array $quasiIdentifiers, int $k = 5, ?string $sensitive = null): array
    {
        if ($k < 1) {
            throw new InvalidArgumentException("k must be at least 1, got {$k}.");
        }

        $generalizers = [];

        foreach ($quasiIdentifiers as $column => $generalizer) {
            if (is_int($column)) {
                $generalizers[(string) $generalizer] = null;
            } else {
                $generalizers[$column] = $generalizer instanceof Closure ? $generalizer : null;
            }
        }

        $counts = [];
        $values = [];

        $checked = false;

        foreach ((is_string($source) ? $source::query() : $source)->cursor() as $row) {
            if (! $checked) {
                // A misspelled or unselected column reads as null and would group every row together.
                foreach ([...array_keys($generalizers), ...($sensitive === null ? [] : [$sensitive])] as $column) {
                    if (! array_key_exists($column, $row->getAttributes()) && ! $row->hasGetMutator($column) && ! $row->hasAttributeMutator($column)) {
                        throw new InvalidArgumentException("Column [{$column}] is not available on ".$row::class.'.');
                    }
                }

                $checked = true;
            }

            $key = serialize(array_map(
                fn (string $column): mixed => ($generalizers[$column] ?? fn (mixed $value): mixed => $value)($row->getAttribute($column)),
                array_keys($generalizers),
            ));

            $counts[$key] = ($counts[$key] ?? 0) + 1;

            if ($sensitive !== null) {
                $values[$key][serialize($row->getAttribute($sensitive))] = true;
            }
        }

        return [
            'rows' => array_sum($counts),
            'groups' => count($counts),
            'k' => $counts === [] ? 0 : min($counts),
            'rows_below_k' => array_sum(array_filter($counts, fn (int $count): bool => $count < $k)),
            'l' => $sensitive === null || $values === [] ? null : min(array_map('count', $values)),
        ];
    }
}
