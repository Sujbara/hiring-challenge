<?php

namespace App\Modules\ContactFinder\DTOs;

class ProviderSignals
{
    public function __construct(
        public readonly ?array $registry = null,
        public readonly ?array $listing = null,
        public readonly ?array $enrichment = null,
    ) {}

    public function hasAny(): bool
    {
        return $this->registry !== null || $this->listing !== null || $this->enrichment !== null;
    }

    /**
     * @return list<string>
     */
    public function activeProviders(): array
    {
        $providers = [];

        if ($this->registry !== null) {
            $providers[] = 'registry';
        }

        if ($this->listing !== null) {
            $providers[] = 'listing';
        }

        if ($this->enrichment !== null) {
            $providers[] = 'enrichment';
        }

        return $providers;
    }
}
