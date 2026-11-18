<?php

namespace JeffersonGoncalves\Privacy\Retention\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\Privacy\Retention\RetentionManager;

/**
 * A suite's `<suite>:retention-reapply` command sets $signature and $manager.
 */
abstract class ReapplyCommand extends Command
{
    protected $description = 'Re-anonymize tombstoned personal data that came back, e.g. after restoring a backup';

    /** @var class-string<RetentionManager> */
    protected string $manager;

    public function handle(): int
    {
        /** @var RetentionManager $retention */
        $retention = $this->laravel->make($this->manager);
        $this->info($retention->reapply().' column(s) re-anonymized.');

        return self::SUCCESS;
    }
}
