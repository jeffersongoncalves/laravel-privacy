<?php

namespace JeffersonGoncalves\Privacy\Attributes;

use JeffersonGoncalves\Privacy\Contracts\AnonymizeStrategy;

/**
 * Base of every suite's #[PersonalData] attribute: one column of an Eloquent model declared as personal data.
 * Each suite adds its own law's fields (category, legal basis, flags) and fills these three, which the engine
 * (export, anonymization, retention) relies on. $anonymize holds the suite's AnonymizeStrategy enum: property
 * types are invariant, so the suite takes it as a plain (enum-typed) constructor parameter and assigns it.
 */
abstract class PersonalDataAttribute
{
    public string $column;

    /** Period like '5 years', counted from the record's retention start. */
    public ?string $retention = null;

    public AnonymizeStrategy $anonymize;

    /**
     * The inventory row of this column (without model and table).
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
