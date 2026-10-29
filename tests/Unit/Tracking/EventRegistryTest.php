<?php

namespace Tests\Unit\Tracking;

use App\Services\Tracking\EventRegistry;
use Tests\TestCase;

class EventRegistryTest extends TestCase
{
    public function test_registry_contains_all_63_governed_event_names(): void
    {
        $events = EventRegistry::all();

        $this->assertCount(63, $events);
        $this->assertSame('custom', $events['PageView']);
        $this->assertSame('ecommerce', $events['Purchase']);
        $this->assertSame('ecommerce', $events['Product Viewed']);
        $this->assertSame('ecommerce', $events['Order Completed']);
        $this->assertSame('custom', $events['CategoryViewed']);
        $this->assertSame('custom', $events['LeadSubmitted']);
    }

    public function test_browser_registry_matches_the_backend_registry(): void
    {
        $script = file_get_contents(public_path('js/tracking.js'));

        $this->assertNotFalse($script);
        foreach (array_keys(EventRegistry::all()) as $event) {
            $this->assertStringContainsString($event, $script);
        }
    }
}
