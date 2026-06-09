<?php

namespace App\Traits;

use App\Services\LocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesLocationSearch
{
    /**
     * Apply location and radius search filters to a listing or user query.
     * 
     * @param Builder $query
     * @param Request $request
     * @param string $type Either 'listing' or 'user'
     * @return Builder
     */
    protected function applyLocationSearch(Builder $query, Request $request, string $type = 'listing'): Builder
    {
        // 1. Filter by specific City (with state fallback support)
        if ($request->has('city')) {
            $city = $request->city;
            $stateAbbr = null;
            $lowerCity = strtolower($city);
            
            $stateMap = [
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
            
            if (isset($stateMap[$lowerCity])) {
                $stateAbbr = $stateMap[$lowerCity];
            }

            if ($type === 'listing') {
                $query->where(function($q) use ($city, $stateAbbr) {
                    $q->where('city', 'like', "%{$city}%")
                      ->orWhere('state', 'like', "%{$city}%")
                      ->orWhereHas('provider', function($subQ) use ($city) {
                          $subQ->where('city', 'like', "%{$city}%")
                               ->orWhere('state', 'like', "%{$city}%");
                      });
                      
                    if ($stateAbbr) {
                        $q->orWhere('state', 'like', "%{$stateAbbr}%")
                          ->orWhereHas('provider', function($subQ) use ($stateAbbr) {
                              $subQ->where('state', 'like', "%{$stateAbbr}%");
                          });
                    }
                });
            } else {
                $query->where(function($q) use ($city, $stateAbbr) {
                    $q->where('city', 'like', "%{$city}%")
                      ->orWhere('state', 'like', "%{$city}%");
                      
                    if ($stateAbbr) {
                        $q->orWhere('state', 'like', "%{$stateAbbr}%");
                    }
                });
            }
        }

        // 2. Filter by specific State (Fixed: Search both listing's own state and provider's state)
        if ($request->has('state')) {
            $state = $request->state;
            $stateAbbr = null;
            $lowerState = strtolower($state);
            
            $stateMap = [
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
            
            if (isset($stateMap[$lowerState])) {
                $stateAbbr = $stateMap[$lowerState];
            }

            if ($type === 'listing') {
                $query->where(function($q) use ($state, $stateAbbr) {
                    $q->where('state', 'like', "%{$state}%")
                      ->orWhereHas('provider', function($subQ) use ($state) {
                          $subQ->where('state', 'like', "%{$state}%");
                      });
                      
                    if ($stateAbbr) {
                        $q->orWhere('state', 'like', "%{$stateAbbr}%")
                          ->orWhereHas('provider', function($subQ) use ($stateAbbr) {
                              $subQ->where('state', 'like', "%{$stateAbbr}%");
                          });
                    }
                });
            } else {
                $query->where(function($q) use ($state, $stateAbbr) {
                    $q->where('state', 'like', "%{$state}%");
                    
                    if ($stateAbbr) {
                        $q->orWhere('state', 'like', "%{$stateAbbr}%");
                    }
                });
            }
        }

        // 3. Filter by specific Location/Address
        if ($request->has('location')) {
            $location = $request->location;
            if ($type === 'listing') {
                $query->where(function($q) use ($location) {
                    $q->where('service_location', 'like', "%{$location}%")
                      ->orWhere('city', 'like', "%{$location}%")
                      ->orWhere('state', 'like', "%{$location}%")
                      ->orWhereHas('provider', function($sub) use ($location) {
                          $sub->where('address', 'like', "%{$location}%")
                             ->orWhere('city', 'like', "%{$location}%")
                             ->orWhere('state', 'like', "%{$location}%");
                      });
                });
            } else {
                $query->where(function($q) use ($location) {
                    $q->where('address', 'like', "%{$location}%")
                      ->orWhere('city', 'like', "%{$location}%")
                      ->orWhere('state', 'like', "%{$location}%");
                });
            }
        }

        // 4. Generic search query logic
        if ($request->has('search')) {
            $search = $request->search;
            if ($type === 'listing') {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('service_location', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%")
                      ->orWhereHas('provider', function($subQ) use ($search) {
                          $subQ->where('city', 'like', "%{$search}%")
                               ->orWhere('state', 'like', "%{$search}%")
                               ->orWhere('address', 'like', "%{$search}%")
                               ->orWhere('business_name', 'like', "%{$search}%");
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
                      ->orWhere('business_name', 'like', "%{$search}%");
                });
            }
        }

        return $query;
    }
}
