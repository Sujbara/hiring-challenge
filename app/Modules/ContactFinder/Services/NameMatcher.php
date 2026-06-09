<?php

namespace App\Modules\ContactFinder\Services;

class NameMatcher
{
    /** @var array<string, list<string>> */
    private const NICKNAMES = [
        'robert' => ['bob', 'rob', 'bobby'],
        'william' => ['bill', 'will', 'billy'],
        'james' => ['jim', 'jimmy'],
        'richard' => ['rick', 'dick'],
        'thomas' => ['tom', 'tommy'],
        'michael' => ['mike'],
        'joseph' => ['joe'],
        'daniel' => ['dan', 'danny'],
        'katherine' => ['karen', 'kate', 'katie'],
        'elizabeth' => ['liz', 'beth', 'betty'],
    ];

    public function matches(?string $left, ?string $right): bool
    {
        $left = $this->normalizePersonName($left);
        $right = $this->normalizePersonName($right);

        if ($left === null || $right === null) {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        if ($this->initialMatches($left, $right)) {
            return true;
        }

        return $this->nicknameMatches($left, $right);
    }

    /**
     * @param  list<?string>  $names
     */
    public function consensusName(array $names): ?string
    {
        $normalized = array_values(array_filter(array_map(
            fn (?string $name) => $this->normalizePersonName($name),
            $names
        )));

        if ($normalized === []) {
            return null;
        }

        $counts = [];

        foreach ($normalized as $name) {
            $matchedGroup = false;

            foreach (array_keys($counts) as $canonical) {
                if ($this->matches($canonical, $name)) {
                    $counts[$canonical]++;
                    $matchedGroup = true;
                    break;
                }
            }

            if (! $matchedGroup) {
                $counts[$name] = 1;
            }
        }

        arsort($counts);

        $topCount = reset($counts);
        $groupsAboveOne = array_filter($counts, fn (int $count) => $count > 1);

        if (count($groupsAboveOne) > 1) {
            return null;
        }

        if ($topCount === 1 && count($counts) > 1) {
            return null;
        }

        return (string) array_key_first($counts);
    }

    public function normalizePersonName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $name = preg_replace('/\s*\([^)]*\)\s*/', ' ', $name) ?? $name;
        $name = preg_replace('/^dr\.?\s+/i', '', $name) ?? $name;
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);

        return $name === '' ? null : $name;
    }

    public function displayName(?string $name): ?string
    {
        $normalized = $this->normalizePersonName($name);

        if ($normalized === null) {
            return null;
        }

        if (preg_match('/^dr\.?\s+/i', (string) $name)) {
            return 'Dr. '.$normalized;
        }

        return $normalized;
    }

    private function initialMatches(string $left, string $right): bool
    {
        $leftParts = explode(' ', $left);
        $rightParts = explode(' ', $right);

        if (count($leftParts) < 2 || count($rightParts) < 2) {
            return false;
        }

        $leftLast = array_pop($leftParts);
        $rightLast = array_pop($rightParts);
        $leftFirst = implode(' ', $leftParts);
        $rightFirst = implode(' ', $rightParts);

        if (strcasecmp($leftLast, $rightLast) !== 0) {
            return false;
        }

        return $this->firstNameMatchesInitial($leftFirst, $rightFirst);
    }

    private function firstNameMatchesInitial(string $leftFirst, string $rightFirst): bool
    {
        $leftInitial = strtolower(rtrim($leftFirst, '.'));
        $rightInitial = strtolower(rtrim($rightFirst, '.'));

        if ($this->isInitial($leftInitial) && ! $this->isInitial($rightInitial)) {
            return str_starts_with($rightInitial, $leftInitial[0]);
        }

        if ($this->isInitial($rightInitial) && ! $this->isInitial($leftInitial)) {
            return str_starts_with($leftInitial, $rightInitial[0]);
        }

        return false;
    }

    private function isInitial(string $name): bool
    {
        return strlen($name) === 1 || (strlen($name) === 2 && str_ends_with($name, '.'));
    }

    private function nicknameMatches(string $left, string $right): bool
    {
        $leftParts = explode(' ', $left);
        $rightParts = explode(' ', $right);

        if (count($leftParts) < 2 || count($rightParts) < 2) {
            return false;
        }

        $leftLast = array_pop($leftParts);
        $rightLast = array_pop($rightParts);
        $leftFirst = strtolower(implode(' ', $leftParts));
        $rightFirst = strtolower(implode(' ', $rightParts));

        if (strcasecmp($leftLast, $rightLast) !== 0) {
            return false;
        }

        if ($leftFirst === $rightFirst) {
            return true;
        }

        foreach (self::NICKNAMES as $formal => $aliases) {
            $forms = array_merge([$formal], $aliases);

            if (in_array($leftFirst, $forms, true) && in_array($rightFirst, $forms, true)) {
                return true;
            }
        }

        return false;
    }
}
