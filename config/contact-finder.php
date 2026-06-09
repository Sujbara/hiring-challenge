<?php

return [
    'confidence_threshold' => (int) env('CONTACT_FINDER_CONFIDENCE_THRESHOLD', 70),

    'mock_data_path' => env(
        'CONTACT_FINDER_MOCK_DATA_PATH',
        base_path('challenge/mocks/enrichment_responses.json')
    ),

    'companies_csv_path' => env(
        'CONTACT_FINDER_COMPANIES_CSV_PATH',
        base_path('challenge/data/companies.csv')
    ),
];
