<?php

namespace JeffersonGoncalves\Privacy\AuditLog\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\Privacy\AuditLog\AuditLogger;

/**
 * A suite's `<suite>:audit-verify` command sets $signature, $description and $logger.
 */
abstract class VerifyCommand extends Command
{
    protected $description = 'Check that the audit log was not altered';

    /** @var class-string<AuditLogger> */
    protected string $logger;

    public function handle(): int
    {
        /** @var AuditLogger $logger */
        $logger = $this->laravel->make($this->logger);
        $broken = $logger->verify();

        if ($broken !== null) {
            $this->error("The audit log chain breaks at entry #{$broken}: it or the entry before it was altered, removed or inserted.");

            return self::FAILURE;
        }

        $this->info('The audit log is intact.');

        return self::SUCCESS;
    }
}
