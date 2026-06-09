<?php

namespace App\Modules\ContactFinder\Repositories;

use App\Modules\ContactFinder\DTOs\ProviderSignals;
use RuntimeException;

class MockEnrichmentRepository
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $data = null;

    public function __construct(
        private readonly string $dataPath,
    ) {}

    public function findByCompanyName(string $companyName): ProviderSignals
    {
        $record = $this->loadData()[$companyName] ?? null;

        if ($record === null) {
            return new ProviderSignals;
        }

        return new ProviderSignals(
            registry: $record['registry'] ?? null,
            listing: $record['listing'] ?? null,
            enrichment: $record['enrichment'] ?? null,
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadData(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        if (! is_readable($this->dataPath)) {
            throw new RuntimeException("Mock enrichment data not found at [{$this->dataPath}].");
        }

        $decoded = json_decode((string) file_get_contents($this->dataPath), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Mock enrichment data is not valid JSON.');
        }

        return $this->data = $decoded;
    }
}
