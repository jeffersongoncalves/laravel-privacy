<?php

namespace JeffersonGoncalves\Privacy\Concerns;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Privacy\Attributes\PersonalDataAttribute;
use JeffersonGoncalves\Privacy\Events\PersonalDataAnonymized;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;

/**
 * HasPersonalData for Eloquent models, driven by the suite's #[PersonalData] attributes. A suite's trait uses
 * this one and names its attribute and its PersonalDataAnonymized event, so suites installed side by side only
 * see their own declarations and events.
 *
 * @mixin Model
 */
trait InteractsWithPersonalData
{
    /**
     * @return class-string<PersonalDataAttribute>
     */
    abstract protected static function personalDataAttribute(): string;

    /**
     * @return class-string<PersonalDataAnonymized>
     */
    abstract protected static function personalDataAnonymizedEvent(): string;

    /**
     * @return list<PersonalDataAttribute>
     */
    public static function personalDataFields(): array
    {
        /** @var array<class-string, list<PersonalDataAttribute>> $cache */
        static $cache = [];

        return $cache[static::class] ??= array_map(
            fn (ReflectionAttribute $attribute): PersonalDataAttribute => $attribute->newInstance(),
            (new ReflectionClass(static::class))->getAttributes(static::personalDataAttribute(), ReflectionAttribute::IS_INSTANCEOF),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPersonalData(): array
    {
        $data = [];

        foreach (static::personalDataFields() as $field) {
            $data[$field->column] = $this->getAttribute($field->column);
        }

        return $data;
    }

    public function anonymizePersonalData(): void
    {
        $columns = [];

        foreach (static::personalDataFields() as $field) {
            if ($field->anonymize->keeps()) {
                continue;
            }

            // Cast value on purpose: encrypted columns hash to a stable pseudonym, and setAttribute re-encrypts.
            $this->setAttribute($field->column, $field->anonymize->apply($this->getAttribute($field->column)));
            $columns[] = $field->column;
        }

        // An observer cancelling the save must not pass for a completed anonymization.
        if ($this->save() === false) {
            throw new RuntimeException('Anonymizing '.static::class.' ['.$this->getKey().'] was cancelled: save() returned false.');
        }

        $event = static::personalDataAnonymizedEvent();
        event(new $event($this, $columns));
    }
}
