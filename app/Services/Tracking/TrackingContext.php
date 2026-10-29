<?php

namespace App\Services\Tracking;

use JsonException;

class TrackingContext
{
    /**
     * Encodes only product/order metadata for a data attribute. Customer
     * identity fields must never be supplied to this method.
     */
    public function encode(array $context): string
    {
        try {
            return base64_encode(json_encode(
                $context,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ));
        } catch (JsonException) {
            return '';
        }
    }
}
