<?php

namespace JeffersonGoncalves\Privacy\Retention\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\Privacy\Retention\RetentionManager;

/**
 * A suite's `<suite>:retention {--dry-run}` command sets $signature and $manager.
 */
abstract class RetentionCommand extends Command
{
    protected $description = 'Anonymize personal data whose retention period has ended';

    /** @var class-string<RetentionManager> */
    protected string $manager;

    public function handle(): int
    {
        /** @var RetentionManager $retention */
        $retention = $this->laravel->make($this->manager);
        $dryRun = (bool) $this->option('dry-run');
        $report = $retention->apply($dryRun);

        if ($report === []) {
            $this->info('No personal data with a retention period declared.');

            return self::SUCCESS;
        }

        $this->table(['Model', 'Column', 'Retention', $dryRun ? 'Would anonymize' : 'Anonymized'], $report);

        return self::SUCCESS;
    }
}
