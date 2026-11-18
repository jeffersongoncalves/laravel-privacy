<?php

namespace JeffersonGoncalves\Privacy\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\Privacy\Privacy;

/**
 * A suite's `<suite>:inventory` command sets $signature and $privacy (its main class).
 */
abstract class InventoryCommand extends Command
{
    protected $description = 'List every personal data column declared with #[PersonalData]';

    /** @var class-string<Privacy> */
    protected string $privacy;

    public function handle(): int
    {
        /** @var Privacy $privacy */
        $privacy = $this->laravel->make($this->privacy);
        $rows = $privacy->inventory();

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->warn("No personal data declared. Add models to config {$privacy->config()}.models.");

            return self::SUCCESS;
        }

        $this->table(array_keys($rows[0]), $rows);

        return self::SUCCESS;
    }
}
