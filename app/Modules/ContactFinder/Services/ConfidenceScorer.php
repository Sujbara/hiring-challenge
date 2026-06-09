<?php

namespace App\Modules\ContactFinder\Services;

use App\Modules\ContactFinder\DTOs\ProviderSignals;

class ConfidenceScorer
{
    /** @var list<string> */
    private const GENERIC_MAILBOXES = ['info', 'office', 'contact', 'sales', 'support', 'admin', 'hello'];

    public function __construct(
        private readonly NameMatcher $nameMatcher,
        private readonly RoleNormalizer $roleNormalizer,
    ) {}

    /**
     * @return array{
     *     score: int,
     *     has_name_conflict: bool,
     *     agreeing_sources: int,
     *     has_contact_channel: bool,
     *     is_generic_mailbox: bool
     * }
     */
    public function score(ProviderSignals $signals, ?string $contactName, ?string $contactRole, ?string $contactChannel): array
    {
        $score = 0;
        $names = $this->collectNames($signals);
        $consensusName = $this->nameMatcher->consensusName($names);
        $agreeingSources = $this->countAgreeingSources($signals);
        $hasNameConflict = $this->hasNameConflict($signals);
        $hasContactChannel = $contactChannel !== null && $contactChannel !== '';
        $isGenericMailbox = $this->isGenericMailbox($contactChannel);

        if ($signals->registry !== null && filled($signals->registry['name'] ?? null)) {
            $score += 25;
        }

        if ($signals->listing !== null && filled($signals->listing['phone'] ?? null)) {
            $score += 15;
        }

        if ($signals->enrichment !== null) {
            $providerConfidence = (int) ($signals->enrichment['provider_confidence'] ?? 0);
            $score += (int) round($providerConfidence * 0.35);
        }

        $score += match ($agreeingSources) {
            3 => 25,
            2 => 15,
            default => 0,
        };

        if ($this->phonesAgree($signals)) {
            $score += 10;
        }

        if ($consensusName !== null && $this->emailMatchesName($signals->enrichment['email'] ?? null, $consensusName)) {
            $score += 5;
        }

        $normalizedRole = $this->roleNormalizer->normalize($contactRole, $contactName);

        if ($this->roleNormalizer->isDecisionMaker($normalizedRole)) {
            $score += 5;
        }

        if ($hasNameConflict) {
            $score -= 25;
        }

        if ($normalizedRole === 'Registered Agent') {
            $score -= 10;
        }

        if ($isGenericMailbox) {
            $score -= 15;
        }

        if ($signals->activeProviders() === ['enrichment'] && (int) ($signals->enrichment['provider_confidence'] ?? 0) < 50) {
            $score = min($score, 45);
        }

        if (! $hasContactChannel) {
            $score = min($score, 40);
        }

        if (! $signals->hasAny()) {
            $score = 0;
        }

        return [
            'score' => max(0, min(100, $score)),
            'has_name_conflict' => $hasNameConflict,
            'agreeing_sources' => $agreeingSources,
            'has_contact_channel' => $hasContactChannel,
            'is_generic_mailbox' => $isGenericMailbox,
        ];
    }

    /**
     * @return list<?string>
     */
    private function collectNames(ProviderSignals $signals): array
    {
        return [
            $signals->registry['name'] ?? null,
            $signals->listing['name'] ?? null,
        ];
    }

    private function countAgreeingSources(ProviderSignals $signals): int
    {
        $nonNullNames = array_values(array_filter($this->collectNames($signals)));

        if ($nonNullNames === []) {
            return $signals->enrichment !== null ? 1 : 0;
        }

        $groups = [];

        foreach ($nonNullNames as $name) {
            $placed = false;

            foreach (array_keys($groups) as $canonical) {
                if ($this->nameMatcher->matches($canonical, $name)) {
                    $groups[$canonical]++;
                    $placed = true;
                    break;
                }
            }

            if (! $placed) {
                $groups[$name] = 1;
            }
        }

        if (count($groups) > 1 && max($groups) === 1) {
            return 0;
        }

        $agreeing = max($groups);
        $canonicalName = (string) array_search($agreeing, $groups, true);

        if ($this->emailSupportsName($signals, $canonicalName)) {
            $agreeing++;
        }

        return min(3, $agreeing);
    }

    private function hasNameConflict(ProviderSignals $signals): bool
    {
        $registryName = $signals->registry['name'] ?? null;
        $listingName = $signals->listing['name'] ?? null;

        if ($registryName === null || $listingName === null) {
            return false;
        }

        return ! $this->nameMatcher->matches($registryName, $listingName);
    }

    private function phonesAgree(ProviderSignals $signals): bool
    {
        $listingPhone = $signals->listing['phone'] ?? null;
        $enrichmentPhone = $signals->enrichment['phone'] ?? null;

        if ($listingPhone === null || $enrichmentPhone === null) {
            return false;
        }

        return $this->normalizePhone($listingPhone) === $this->normalizePhone($enrichmentPhone);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    private function emailMatchesName(?string $email, string $name): bool
    {
        if ($email === null) {
            return false;
        }

        $localPart = strtolower(strtok($email, '@') ?: '');
        $parts = explode(' ', strtolower($name));
        $first = $parts[0] ?? '';
        $last = $parts[count($parts) - 1] ?? '';

        $candidates = array_filter([
            $first,
            $last,
            substr($first, 0, 1),
            $first.'.'.$last,
            substr($first, 0, 1).'.'.$last,
            substr($first, 0, 1).$last,
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && str_contains($localPart, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function emailSupportsName(ProviderSignals $signals, string $name): bool
    {
        return $this->emailMatchesName($signals->enrichment['email'] ?? null, $name);
    }

    private function isGenericMailbox(?string $contactChannel): bool
    {
        if ($contactChannel === null || ! str_contains($contactChannel, '@')) {
            return false;
        }

        $localPart = strtolower(strtok($contactChannel, '@') ?: '');

        return in_array($localPart, self::GENERIC_MAILBOXES, true);
    }
}
