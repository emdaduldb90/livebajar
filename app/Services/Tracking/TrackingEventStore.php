<?php

namespace App\Services\Tracking;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class TrackingEventStore
{
    private const SESSION_KEY = 'store.tracking.events';

    /** @param array<string, mixed> $properties */
    public function flashEcommerce(string $name, array $properties, ?string $eventId = null, bool $persistent = false): void
    {
        if (! EventRegistry::isEcommerce($name)) {
            return;
        }

        $this->flash([
            'type' => 'ecommerce',
            'name' => $name,
            'properties' => $properties,
            'event_id' => $eventId ?: (string) Str::uuid(),
            'persistent' => $persistent,
        ]);
    }

    /** @param array<string, scalar|null> $properties */
    public function flashCustom(string $name, array $properties, ?string $eventId = null, bool $persistent = false): void
    {
        if (! EventRegistry::isCustom($name)) {
            return;
        }

        $this->flash([
            'type' => 'custom',
            'name' => $name,
            'properties' => $properties,
            'event_id' => $eventId ?: (string) Str::uuid(),
            'persistent' => $persistent,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function pull(): array
    {
        $events = Session::pull(self::SESSION_KEY, []);

        return is_array($events) ? $events : [];
    }

    /** @param array<string, mixed> $event */
    private function flash(array $event): void
    {
        $events = Session::get(self::SESSION_KEY, []);
        $events = is_array($events) ? $events : [];
        $events[] = $event;
        Session::flash(self::SESSION_KEY, $events);
    }
}
