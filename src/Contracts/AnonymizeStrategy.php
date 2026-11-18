<?php

namespace JeffersonGoncalves\Privacy\Contracts;

use BackedEnum;

/**
 * A suite's AnonymizeStrategy enum (nullify, hash, mask, keep): see Concerns\AppliesAnonymizeStrategy.
 */
interface AnonymizeStrategy extends BackedEnum
{
    public function apply(mixed $value): mixed;

    /** Keep leaves the column untouched (e.g. data under a legal retention duty). */
    public function keeps(): bool;
}
