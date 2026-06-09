<?php

namespace Tests\Unit\Modules\ContactFinder;

use App\Modules\ContactFinder\Repositories\MockEnrichmentRepository;
use App\Modules\ContactFinder\Services\ConfidenceScorer;
use App\Modules\ContactFinder\Services\ContactFinderService;
use App\Modules\ContactFinder\Services\NameMatcher;
use App\Modules\ContactFinder\Services\RoleNormalizer;
use Tests\TestCase;

class ContactFinderServiceTest extends TestCase
{
    private ContactFinderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = new MockEnrichmentRepository(base_path('challenge/mocks/enrichment_responses.json'));
        $nameMatcher = new NameMatcher;
        $roleNormalizer = new RoleNormalizer;
        $scorer = new ConfidenceScorer($nameMatcher, $roleNormalizer);

        $this->service = new ContactFinderService(
            $repository,
            $nameMatcher,
            $roleNormalizer,
            $scorer,
            70,
        );
    }

    public function test_high_agreement_company_returns_verified_contact(): void
    {
        $result = $this->service->find(
            'Cedar Ridge Plumbing LLC',
            '4821 Maple Ave, Lincoln, NE 68504',
        );

        $this->assertSame('Daniel Ortega', $result->contactName);
        $this->assertSame('Owner', $result->contactRole);
        $this->assertSame('d.ortega@cedarridgeplumbing.com', $result->contactEmailOrPhone);
        $this->assertGreaterThanOrEqual(70, $result->confidenceScore);
        $this->assertFalse($result->needsHumanReview);
        $this->assertStringContainsString('registry', $result->source);
        $this->assertStringContainsString('listing', $result->source);
        $this->assertStringContainsString('enrichment', $result->source);
    }

    public function test_weak_single_source_marks_human_review_and_clears_contact(): void
    {
        $result = $this->service->find(
            'Riverside Print & Sign',
            '302 W 3rd St, Davenport, IA 52801',
        );

        $this->assertTrue($result->needsHumanReview);
        $this->assertSame('', $result->contactEmailOrPhone);
        $this->assertLessThan(70, $result->confidenceScore);
        $this->assertSame('low_confidence', $result->verificationStatus);
    }

    public function test_missing_mock_data_is_not_found(): void
    {
        $result = $this->service->find(
            'Redwood Cabinetry',
            '509 Timber Ct, Eugene, OR 97401',
        );

        $this->assertTrue($result->needsHumanReview);
        $this->assertSame('', $result->contactEmailOrPhone);
        $this->assertSame(0, $result->confidenceScore);
        $this->assertSame('not_found', $result->verificationStatus);
        $this->assertSame('', $result->source);
    }

    public function test_conflicting_names_require_human_review(): void
    {
        $result = $this->service->find(
            'Coastal Breeze Pool Service',
            '233 Seagrape Way, Sarasota, FL 34236',
        );

        $this->assertTrue($result->needsHumanReview);
        $this->assertSame('', $result->contactEmailOrPhone);
        $this->assertSame('cannot_verify', $result->verificationStatus);
    }

    public function test_listing_only_company_stays_below_confidence_threshold(): void
    {
        $result = $this->service->find(
            'Magnolia Family Dental',
            '1188 Peachtree Ln, Macon, GA 31201',
        );

        $this->assertTrue($result->needsHumanReview);
        $this->assertSame('', $result->contactEmailOrPhone);
        $this->assertSame('low_confidence', $result->verificationStatus);
        $this->assertLessThan(70, $result->confidenceScore);
    }

    public function test_provenance_includes_source_urls(): void
    {
        $result = $this->service->find(
            'Pioneer Landscaping Inc',
            '940 Prairie View Dr, Boise, ID 83704',
        );

        $this->assertNotEmpty($result->provenance);
        $this->assertTrue(collect($result->provenance)->contains(
            fn (array $entry) => $entry['source_url'] === 'mock://enrichment/pioneer-landscaping'
        ));
    }
}
