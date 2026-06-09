<?php

namespace App\Modules\ContactFinder\Services;

use App\Modules\ContactFinder\DTOs\ContactResult;
use App\Modules\ContactFinder\DTOs\ProviderSignals;
use App\Modules\ContactFinder\Repositories\MockEnrichmentRepository;
use Illuminate\Support\Facades\Log;

class ContactFinderService
{
    public function __construct(
        private readonly MockEnrichmentRepository $repository,
        private readonly NameMatcher $nameMatcher,
        private readonly RoleNormalizer $roleNormalizer,
        private readonly ConfidenceScorer $confidenceScorer,
        private readonly int $confidenceThreshold,
    ) {}

    public function find(string $companyName, string $mailingAddress): ContactResult
    {
        $signals = $this->repository->findByCompanyName($companyName);

        Log::info('contact_finder.lookup', [
            'company_name' => $companyName,
            'providers' => $signals->activeProviders(),
        ]);

        if (! $signals->hasAny()) {
            return $this->buildResult(
                companyName: $companyName,
                mailingAddress: $mailingAddress,
                signals: $signals,
                contactName: null,
                contactRole: null,
                contactChannel: null,
                confidenceScore: 0,
                verificationStatus: 'not_found',
                forceReview: true,
            );
        }

        $contactName = $this->resolveContactName($signals);
        $contactRole = $this->resolveContactRole($signals, $contactName);
        $contactChannel = $this->resolveContactChannel($signals);
        $scoring = $this->confidenceScorer->score($signals, $contactName, $contactRole, $contactChannel);

        $verificationStatus = match (true) {
            $scoring['has_name_conflict'] => 'cannot_verify',
            ! $scoring['has_contact_channel'] => 'no_contact_channel',
            $scoring['score'] < $this->confidenceThreshold => 'low_confidence',
            default => 'verified',
        };

        return $this->buildResult(
            companyName: $companyName,
            mailingAddress: $mailingAddress,
            signals: $signals,
            contactName: $contactName,
            contactRole: $contactRole,
            contactChannel: $contactChannel,
            confidenceScore: $scoring['score'],
            verificationStatus: $verificationStatus,
            forceReview: $scoring['has_name_conflict'] || $scoring['score'] < $this->confidenceThreshold,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function processCsv(?string $csvPath = null): array
    {
        $path = $csvPath ?? config('contact-finder.companies_csv_path');
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Unable to read companies CSV at [{$path}].");
        }

        $results = [];
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $result = $this->find($row[0], $row[1]);
            $results[] = $result->toArray();
        }

        fclose($handle);

        return $results;
    }

    private function resolveContactName(ProviderSignals $signals): ?string
    {
        $names = [
            $signals->registry['name'] ?? null,
            $signals->listing['name'] ?? null,
        ];

        $consensus = $this->nameMatcher->consensusName($names);

        if ($consensus !== null) {
            return $this->nameMatcher->displayName($consensus);
        }

        foreach ([$signals->registry['name'] ?? null, $signals->listing['name'] ?? null] as $name) {
            if ($name !== null) {
                return $this->nameMatcher->displayName($name);
            }
        }

        return null;
    }

    private function resolveContactRole(ProviderSignals $signals, ?string $contactName): ?string
    {
        $roles = array_filter([
            $this->roleNormalizer->normalize($signals->registry['role'] ?? null, $signals->registry['name'] ?? null),
            $this->roleNormalizer->normalize(null, $signals->listing['name'] ?? null),
        ]);

        if ($roles === []) {
            return null;
        }

        usort($roles, fn (string $left, string $right) => $this->roleNormalizer->priority($left) <=> $this->roleNormalizer->priority($right));

        return $roles[0];
    }

    private function resolveContactChannel(ProviderSignals $signals): ?string
    {
        $email = $signals->enrichment['email'] ?? null;
        $phones = array_values(array_filter([
            $signals->enrichment['phone'] ?? null,
            $signals->listing['phone'] ?? null,
        ]));

        return $email ?? ($phones[0] ?? null);
    }

    private function buildResult(
        string $companyName,
        string $mailingAddress,
        ProviderSignals $signals,
        ?string $contactName,
        ?string $contactRole,
        ?string $contactChannel,
        int $confidenceScore,
        string $verificationStatus,
        bool $forceReview,
    ): ContactResult {
        $needsHumanReview = $forceReview;
        $publishedContact = (! $needsHumanReview && $contactChannel !== null) ? $contactChannel : '';

        return new ContactResult(
            companyName: $companyName,
            mailingAddress: $mailingAddress,
            contactName: $contactName,
            contactRole: $contactRole,
            contactEmailOrPhone: $publishedContact,
            confidenceScore: $confidenceScore,
            source: implode('|', $signals->activeProviders()),
            needsHumanReview: $needsHumanReview,
            verificationStatus: $verificationStatus,
            provenance: $this->buildProvenance($signals),
        );
    }

    /**
     * @return list<array{provider: string, source_url: string, extracted_at: string}>
     */
    private function buildProvenance(ProviderSignals $signals): array
    {
        $provenance = [];
        $extractedAt = now()->toIso8601String();

        foreach ($signals->activeProviders() as $provider) {
            $payload = match ($provider) {
                'registry' => $signals->registry,
                'listing' => $signals->listing,
                'enrichment' => $signals->enrichment,
                default => null,
            };

            if (! is_array($payload) || empty($payload['source_url'])) {
                continue;
            }

            $provenance[] = [
                'provider' => $provider,
                'source_url' => $payload['source_url'],
                'extracted_at' => $extractedAt,
            ];
        }

        return $provenance;
    }
}
