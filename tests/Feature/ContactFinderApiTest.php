<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContactFinderApiTest extends TestCase
{
    public function test_contact_finder_endpoint_returns_structured_result(): void
    {
        $response = $this->postJson('/api/v1/contact-finder', [
            'company_name' => 'Ironclad Welding Shop',
            'mailing_address' => '1701 Foundry Rd, Pittsburgh, PA 15201',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'company_name',
                    'mailing_address',
                    'contact_name',
                    'contact_role',
                    'contact_email_or_phone',
                    'confidence_score',
                    'source',
                    'needs_human_review',
                    'verification_status',
                    'provenance',
                ],
            ])
            ->assertJsonPath('data.company_name', 'Ironclad Welding Shop')
            ->assertJsonPath('data.needs_human_review', false);
    }

    public function test_contact_finder_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/contact-finder', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name', 'mailing_address']);
    }

    public function test_process_dataset_endpoint_returns_all_csv_rows(): void
    {
        $response = $this->postJson('/api/v1/contact-finder/process-dataset');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'needs_human_review'],
            ]);

        $this->assertSame(30, $response->json('meta.total'));
    }
}
