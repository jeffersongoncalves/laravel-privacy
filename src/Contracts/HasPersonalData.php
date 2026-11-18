<?php

namespace JeffersonGoncalves\Privacy\Contracts;

use JeffersonGoncalves\Privacy\Attributes\PersonalDataAttribute;

/**
 * Base of every suite's HasPersonalData contract.
 */
interface HasPersonalData
{
    /**
     * @return list<PersonalDataAttribute>
     */
    public static function personalDataFields(): array;

    /**
     * Declared personal data of this record, keyed by column (access and portability).
     *
     * @return array<string, mixed>
     */
    public function exportPersonalData(): array;

    /**
     * Apply each column's anonymize strategy and persist (erasure).
     */
    public function anonymizePersonalData(): void;
}
