<?php

if (!function_exists('country_name')) {
    /**
     * Resolve an ISO 3166-1 alpha-2 country code to its full display name.
     * Falls back to the original value when no mapping is found.
     */
    function country_name(?string $code): ?string
    {
        if (empty($code)) {
            return $code;
        }

        $map = config('countries.map', []);

        return $map[strtoupper($code)] ?? $code;
    }
}
