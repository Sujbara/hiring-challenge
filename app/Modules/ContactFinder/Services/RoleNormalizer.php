<?php

namespace App\Modules\ContactFinder\Services;

class RoleNormalizer
{
    public const PRIORITY_AP_MANAGER = 1;
    public const PRIORITY_OWNER = 2;
    public const PRIORITY_CFO = 3;
    public const PRIORITY_OFFICE_MANAGER = 4;
    public const PRIORITY_OTHER = 5;
    public const PRIORITY_REGISTERED_AGENT = 6;

    public function normalize(?string $role, ?string $name = null): ?string
    {
        $role = $this->extractRoleFromName($role, $name);

        if ($role === null) {
            return null;
        }

        $normalized = strtolower(trim($role));

        return match (true) {
            $this->containsAny($normalized, ['accounts payable', 'ap manager', 'ap specialist']) => 'AP Manager',
            $this->containsAny($normalized, ['owner', 'founder', 'proprietor', 'president']) => 'Owner',
            $this->containsAny($normalized, ['cfo', 'finance lead', 'finance director', 'controller']) => 'CFO',
            $this->containsAny($normalized, ['office manager', 'operations manager']) => 'Office Manager',
            $this->containsAny($normalized, ['manager']) => 'Manager',
            $this->containsAny($normalized, ['registered agent']) => 'Registered Agent',
            default => ucwords($role),
        };
    }

    public function priority(?string $normalizedRole): int
    {
        return match ($normalizedRole) {
            'AP Manager' => self::PRIORITY_AP_MANAGER,
            'Owner' => self::PRIORITY_OWNER,
            'CFO' => self::PRIORITY_CFO,
            'Office Manager', 'Manager' => self::PRIORITY_OFFICE_MANAGER,
            'Registered Agent' => self::PRIORITY_REGISTERED_AGENT,
            default => self::PRIORITY_OTHER,
        };
    }

    public function isDecisionMaker(?string $normalizedRole): bool
    {
        return $this->priority($normalizedRole) <= self::PRIORITY_OFFICE_MANAGER;
    }

    private function extractRoleFromName(?string $role, ?string $name): ?string
    {
        if ($role !== null && trim($role) !== '') {
            return $role;
        }

        if ($name === null || ! preg_match('/\(([^)]+)\)/', $name, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
