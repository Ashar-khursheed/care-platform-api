<?php

namespace App\Traits;

use App\Services\LocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesLocationSearch
{
    /**
     * Apply location, address, city, state, zip_code and search filters to a listing or user query.
     * 
     * @param Builder $query
     * @param Request $request
     * @param string $type Either 'listing' or 'user'
     * @return Builder
     */
    protected function applyLocationSearch(Builder $query, Request $request, string $type = 'listing'): Builder
    {
        // 1. Filter by specific City (if provided)
        if ($request->filled('city')) {
            $query = $this->applyLocationTerm($query, $request->city, $type);
        }

        // 2. Filter by specific State (if provided)
        if ($request->filled('state')) {
            $query = $this->applyLocationTerm($query, $request->state, $type);
        }

        // 3. Filter by specific Location/Address (if provided)
        if ($request->filled('location')) {
            $query = $this->applyLocationTerm($query, $request->location, $type);
        }

        // 4. Filter by specific Zip code (if provided)
        if ($request->filled('zip_code') || $request->filled('zipcode')) {
            $zip = $request->zip_code ?? $request->zipcode;
            $query = $this->applyLocationTerm($query, $zip, $type);
        }

        // 5. Generic search query logic
        if ($request->filled('search')) {
            $search = trim($request->search);
            if ($type === 'listing') {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('service_location', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%")
                      ->orWhere('zip_code', 'like', "%{$search}%")
                      ->orWhereHas('provider', function($subQ) use ($search) {
                          $subQ->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('business_name', 'like', "%{$search}%")
                               ->orWhere('city', 'like', "%{$search}%")
                               ->orWhere('state', 'like', "%{$search}%")
                               ->orWhere('address', 'like', "%{$search}%")
                               ->orWhere('zip_code', 'like', "%{$search}%");
                      });
                });
            } else {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('bio', 'like', "%{$search}%")
                      ->orWhere('desired_role', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('zip_code', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%");
                });
            }
        }

        return $query;
    }

    /**
     * Apply a location term across all location fields (city, state, service_location/address, zip_code).
     */
    protected function applyLocationTerm(Builder $query, string $term, string $type = 'listing'): Builder
    {
        $term = trim($term);
        if (empty($term)) {
            return $query;
        }

        $lowerTerm = strtolower($term);
        $stateMap = $this->getStateMap();
        $reverseStateMap = array_change_key_case(array_flip($stateMap), CASE_LOWER);

        $stateAbbr = $stateMap[$lowerTerm] ?? null;
        $stateName = $reverseStateMap[$lowerTerm] ?? null;

        return $query->where(function($q) use ($term, $stateAbbr, $stateName, $type) {
            if ($type === 'listing') {
                $q->where('city', 'like', "%{$term}%")
                  ->orWhere('state', 'like', "%{$term}%")
                  ->orWhere('service_location', 'like', "%{$term}%")
                  ->orWhere('zip_code', 'like', "%{$term}%")
                  ->orWhereHas('provider', function($subQ) use ($term) {
                      $subQ->where('city', 'like', "%{$term}%")
                           ->orWhere('state', 'like', "%{$term}%")
                           ->orWhere('address', 'like', "%{$term}%")
                           ->orWhere('zip_code', 'like', "%{$term}%");
                  });

                if ($stateAbbr) {
                    $q->orWhere('state', 'like', "%{$stateAbbr}%")
                      ->orWhere('service_location', 'like', "%{$stateAbbr}%")
                      ->orWhereHas('provider', function($subQ) use ($stateAbbr) {
                          $subQ->where('state', 'like', "%{$stateAbbr}%")
                               ->orWhere('address', 'like', "%{$stateAbbr}%");
                      });
                }

                if ($stateName) {
                    $q->orWhere('state', 'like', "%{$stateName}%")
                      ->orWhere('service_location', 'like', "%{$stateName}%")
                      ->orWhereHas('provider', function($subQ) use ($stateName) {
                          $subQ->where('state', 'like', "%{$stateName}%")
                               ->orWhere('address', 'like', "%{$stateName}%");
                      });
                }
            } else {
                $q->where('city', 'like', "%{$term}%")
                  ->orWhere('state', 'like', "%{$term}%")
                  ->orWhere('address', 'like', "%{$term}%")
                  ->orWhere('zip_code', 'like', "%{$term}%");

                if ($stateAbbr) {
                    $q->orWhere('state', 'like', "%{$stateAbbr}%")
                      ->orWhere('address', 'like', "%{$stateAbbr}%");
                }

                if ($stateName) {
                    $q->orWhere('state', 'like', "%{$stateName}%")
                      ->orWhere('address', 'like', "%{$stateName}%");
                }
            }
        });
    }

    /**
     * Map of state names to state abbreviations.
     */
    protected function getStateMap(): array
    {
        return [
            'alabama' => 'AL', 'alaska' => 'AK', 'arizona' => 'AZ', 'arkansas' => 'AR', 'california' => 'CA',
            'colorado' => 'CO', 'connecticut' => 'CT', 'delaware' => 'DE', 'florida' => 'FL', 'georgia' => 'GA',
            'hawaii' => 'HI', 'idaho' => 'ID', 'illinois' => 'IL', 'indiana' => 'IN', 'iowa' => 'IA',
            'kansas' => 'KS', 'kentucky' => 'KY', 'louisiana' => 'LA', 'maine' => 'ME', 'maryland' => 'MD',
            'massachusetts' => 'MA', 'michigan' => 'MI', 'minnesota' => 'MN', 'mississippi' => 'MS',
            'missouri' => 'MO', 'montana' => 'MT', 'nebraska' => 'NE', 'nevada' => 'NV', 'new hampshire' => 'NH',
            'new jersey' => 'NJ', 'new mexico' => 'NM', 'new york' => 'NY', 'north carolina' => 'NC',
            'north dakota' => 'ND', 'ohio' => 'OH', 'oklahoma' => 'OK', 'oregon' => 'OR', 'pennsylvania' => 'PA',
            'rhode island' => 'RI', 'south carolina' => 'SC', 'south dakota' => 'SD', 'tennessee' => 'TN',
            'texas' => 'TX', 'utah' => 'UT', 'vermont' => 'VT', 'virginia' => 'VA', 'washington' => 'WA',
            'west virginia' => 'WV', 'wisconsin' => 'WI', 'wyoming' => 'WY'
        ];
    }
}
