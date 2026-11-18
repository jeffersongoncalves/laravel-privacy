<?php

namespace JeffersonGoncalves\Privacy\AuditLog\Listeners;

use JeffersonGoncalves\Privacy\AuditLog\AuditLogger;
use JeffersonGoncalves\Privacy\Events\PersonalDataAnonymized;

/**
 * Logs every anonymization of the suite. The suite listens with it to its own PersonalDataAnonymized.
 */
class LogAnonymization
{
    public function __construct(protected AuditLogger $logger) {}

    public function handle(PersonalDataAnonymized $event): void
    {
        $this->logger->record('anonymized', $event->model, $event->columns);
    }
}
