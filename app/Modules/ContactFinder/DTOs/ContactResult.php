<?php

namespace App\Modules\ContactFinder\DTOs;

class ContactResult
{
    /**
     * @param  array<int, array{provider: string, source_url: string, extracted_at: string}>  $provenance
     */
    public function __construct(
        public readonly string $companyName,
        public readonly string $mailingAddress,
        public readonly ?string $contactName,
        public readonly ?string $contactRole,
        public readonly string $contactEmailOrPhone,
        public readonly int $confidenceScore,
        public readonly string $source,
        public readonly bool $needsHumanReview,
        public readonly string $verificationStatus,
        public readonly array $provenance = [],
    ) {}

    public function toArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'mailing_address' => $this->mailingAddress,
            'contact_name' => $this->contactName,
            'contact_role' => $this->contactRole,
            'contact_email_or_phone' => $this->contactEmailOrPhone,
            'confidence_score' => $this->confidenceScore,
            'source' => $this->source,
            'needs_human_review' => $this->needsHumanReview,
            'verification_status' => $this->verificationStatus,
            'provenance' => $this->provenance,
        ];
    }
}
