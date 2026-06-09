<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Sequence\Models\Sequence;
use App\Modules\Sequence\Observers\SequenceObserver;
use App\Modules\Payment\Models\UserPayment;
use App\Modules\Payment\Observers\UserPaymentObserver;
use App\Modules\ContactFinder\Repositories\MockEnrichmentRepository;
use App\Modules\ContactFinder\Services\ConfidenceScorer;
use App\Modules\ContactFinder\Services\ContactFinderService;
use App\Modules\ContactFinder\Services\NameMatcher;
use App\Modules\ContactFinder\Services\RoleNormalizer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MockEnrichmentRepository::class, function () {
            return new MockEnrichmentRepository(config('contact-finder.mock_data_path'));
        });

        $this->app->singleton(NameMatcher::class);
        $this->app->singleton(RoleNormalizer::class);

        $this->app->singleton(ConfidenceScorer::class, function ($app) {
            return new ConfidenceScorer(
                $app->make(NameMatcher::class),
                $app->make(RoleNormalizer::class),
            );
        });

        $this->app->singleton(ContactFinderService::class, function ($app) {
            return new ContactFinderService(
                $app->make(MockEnrichmentRepository::class),
                $app->make(NameMatcher::class),
                $app->make(RoleNormalizer::class),
                $app->make(ConfidenceScorer::class),
                config('contact-finder.confidence_threshold'),
            );
        });
    }

    public function boot(): void
    {
        Sequence::observe(SequenceObserver::class);
        UserPayment::observe(UserPaymentObserver::class);
    }
}
