<?php

namespace App\Http\Controllers;

use App\Modules\ContactFinder\Services\ContactFinderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactFinderController extends Controller
{
    public function __construct(
        private readonly ContactFinderService $contactFinder,
    ) {}

    public function find(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'mailing_address' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->contactFinder->find(
            $validated['company_name'],
            $validated['mailing_address'],
        );

        return response()->json(['data' => $result->toArray()]);
    }

    public function processDataset(): JsonResponse
    {
        $results = $this->contactFinder->processCsv();

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'needs_human_review' => count(array_filter($results, fn (array $row) => $row['needs_human_review'])),
            ],
        ]);
    }
}
