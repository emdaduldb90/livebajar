<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Services\Tracking\TrackingEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackingEventController extends Controller
{
    public function store(Request $request, TrackingEventRecorder $recorder): JsonResponse
    {
        if (! $this->isSameOrigin($request)) {
            abort(403);
        }

        $input = $request->validate([
            'event_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'event_name' => ['required', 'string', 'in:PageView,ViewContent,AddToCart,InitiateCheckout,Purchase,LinkClick,PhoneClick,EmailClick,Search'],
            'properties' => ['nullable', 'array', 'max:50'],
            'page_path' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'url', 'max:2048'],
            'campaign_source' => ['nullable', 'string', 'max:120'],
            'campaign_medium' => ['nullable', 'string', 'max:120'],
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'consent_state' => ['nullable', 'in:granted,denied,unknown'],
            'ga_client_id' => ['nullable', 'string', 'max:50'],
            'ga_session_id' => ['nullable', 'string', 'max:32'],
        ]);

        $recorder->recordBrowser($input, $request);

        return response()->json(['accepted' => true], 202)->header('Cache-Control', 'no-store');
    }

    private function isSameOrigin(Request $request): bool
    {
        $origin = $request->headers->get('Origin');
        if (! is_string($origin) || $origin === '') {
            $origin = $request->headers->get('Referer');
        }
        if (! is_string($origin) || $origin === '') {
            return false;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $originScheme = parse_url($origin, PHP_URL_SCHEME);

        return is_string($originHost)
            && strtolower($originHost) === strtolower($request->getHost())
            && in_array($originScheme, ['http', 'https'], true);
    }
}
