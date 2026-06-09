<?php

namespace App\Console\Commands;

use App\Modules\ContactFinder\Services\ContactFinderService;
use Illuminate\Console\Command;

class ProcessContactFinderCommand extends Command
{
    protected $signature = 'contact-finder:process {--csv= : Optional path to a companies CSV file}';

    protected $description = 'Process companies from CSV through the mock contact-finder providers';

    public function handle(ContactFinderService $contactFinder): int
    {
        $csvPath = $this->option('csv');
        $results = $contactFinder->processCsv(is_string($csvPath) && $csvPath !== '' ? $csvPath : null);

        $this->table(
            ['company', 'contact', 'confidence', 'review', 'status'],
            array_map(fn (array $row) => [
                $row['company_name'],
                $row['contact_email_or_phone'] !== '' ? $row['contact_email_or_phone'] : ($row['contact_name'] ?? '—'),
                $row['confidence_score'],
                $row['needs_human_review'] ? 'yes' : 'no',
                $row['verification_status'],
            ], $results)
        );

        $this->info(sprintf(
            'Processed %d companies (%d need human review).',
            count($results),
            count(array_filter($results, fn (array $row) => $row['needs_human_review']))
        ));

        return self::SUCCESS;
    }
}
