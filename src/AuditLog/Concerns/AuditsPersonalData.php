<?php

namespace JeffersonGoncalves\Privacy\AuditLog\Concerns;

use JeffersonGoncalves\Privacy\Attributes\PersonalDataAttribute;
use JeffersonGoncalves\Privacy\AuditLog\AuditLog;
use JeffersonGoncalves\Privacy\AuditLog\AuditLogger;

/**
 * For HasPersonalData models: logs changes to declared personal data columns (names only) and deletions.
 * Reads can't be detected by Eloquent: call auditAccess() where you show the data. A suite's trait uses
 * this one and names its logger.
 */
trait AuditsPersonalData
{
    abstract protected static function auditLogger(): AuditLogger;

    public static function bootAuditsPersonalData(): void
    {
        static::updated(function (self $model): void {
            $columns = array_values(array_intersect(array_keys($model->getChanges()), $model->personalDataColumns()));

            if ($columns !== []) {
                static::auditLogger()->record('updated', $model, $columns);
            }
        });

        static::deleted(function (self $model): void {
            static::auditLogger()->record('deleted', $model);
        });
    }

    /**
     * @param  list<string>  $columns  the columns shown; all declared ones when empty
     */
    public function auditAccess(?string $purpose = null, array $columns = []): AuditLog
    {
        return static::auditLogger()->record('viewed', $this, $columns === [] ? $this->personalDataColumns() : $columns, $purpose);
    }

    /**
     * @return list<string>
     */
    protected function personalDataColumns(): array
    {
        return array_map(fn (PersonalDataAttribute $field): string => $field->column, static::personalDataFields());
    }
}
