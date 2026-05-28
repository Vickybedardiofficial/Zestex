<?php

namespace App\Services\Feed;

use Illuminate\Http\Request;

class FeedContextResolver
{
    /**
     * Resolve ranking context from request + user + app locale.
     *
     * @param mixed $user
     */
    public function resolve(Request $request, $user = null, string $feature = 'timeline'): array
    {
        $filter = $request->array('filter');
        $ctx = (array) data_get($filter, 'context', []);

        $country = (string) (
            data_get($ctx, 'country')
            ?? $request->input('country')
            ?? ($user->country ?? '')
            ?? ''
        );

        $city = (string) (
            data_get($ctx, 'city')
            ?? $request->input('city')
            ?? ($user->city ?? '')
            ?? ''
        );

        $area = (string) (
            data_get($ctx, 'area')
            ?? $request->input('area')
            ?? $request->header('X-Area', '')
        );

        $language = (string) (
            data_get($ctx, 'language')
            ?? $request->input('language')
            ?? ($user->language ?? '')
            ?? app()->getLocale()
        );

        return [
            'feature' => strtolower(trim($feature)),
            'country' => strtoupper(trim($country)),
            'city' => strtolower(trim($city)),
            'area' => strtolower(trim($area)),
            'language' => strtolower(trim($language)),
        ];
    }
}
