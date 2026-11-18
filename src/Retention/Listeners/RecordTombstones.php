<?php

namespace JeffersonGoncalves\Privacy\Retention\Listeners;

use JeffersonGoncalves\Privacy\Events\PersonalDataAnonymized;
use JeffersonGoncalves\Privacy\Retention\RetentionManager;

/**
 * Tombstones any anonymization of the suite (erasure requests, consent records...), so the suite's
 * retention-reapply command covers it too. The suite listens with it to its own PersonalDataAnonymized.
 */
class RecordTombstones
{
    public function __construct(protected RetentionManager $retention) {}

    public function handle(PersonalDataAnonymized $event): void
    {
        $this->retention->tombstone($event->model, $event->columns);
    }
}
