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
        $locationService = app(LocationService::class);
        $radius = 30; // 30 km radius

        // 1. Check for specific Zip Code filtering
        $zip = $request->zip_code ?? $request->zipcode ?? null;

        // If no explicit zip code is passed, check if the generic 'search' or 'location' parameter is a zip code
        if (!$zip && $request->has('search') && preg_match('/\b\d{5}\b/', $request->search, $matches)) {
            $zip = $matches[0];
        }
        if (!$zip && $request->has('location') && preg_match('/\b\d{5}\b/', $request->location, $matches)) {
            $zip = $matches[0];
        }

        if ($zip) {
            $coords = $locationService->getCoordinates($zip);
            if ($coords) {
                $lat = $coords['lat'];
                $lng = $coords['lng'];

                // Safety checks to see if the columns actually exist in the DB (in case migrations haven't run in production yet)
                $hasListingCoords = \Illuminate\Support\Facades\Schema::hasColumns('service_listings', ['latitude', 'longitude']);
                $hasUserCoords = \Illuminate\Support\Facades\Schema::hasColumns('users', ['latitude', 'longitude']);

                if ($type === 'listing') {
                    $query->where(function($q) use ($lat, $lng, $radius, $zip, $hasListingCoords, $hasUserCoords) {
                        if ($hasListingCoords) {
                            // Listing itself is within 30km
                            $q->where(function($sub) use ($lat, $lng, $radius) {
                                $sub->whereNotNull('latitude')
                                    ->whereNotNull('longitude')
                                    ->whereRaw(
                                        "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                                        [$lat, $lng, $lat, $radius]
                                    );
                            });
                        }

                        if ($hasUserCoords) {
                            // Or provider is within 30km
                            $q->orWhereHas('provider', function($sub) use ($lat, $lng, $radius) {
                                $sub->whereNotNull('latitude')
                                    ->whereNotNull('longitude')
                                    ->whereRaw(
                                        "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                                        [$lat, $lng, $lat, $radius]
                                    );
                            });
                        }

                        // Direct zip match on listing or provider as fallback
                        $q->orWhere('zip_code', 'like', "%{$zip}%")
                           ->orWhereHas('provider', function($sub) use ($zip) {
                               $sub->where('zip_code', 'like', "%{$zip}%");
                           });
                    });
                } else {
                    // For User/Worker discovery
                    $query->where(function($q) use ($lat, $lng, $radius, $zip, $hasUserCoords) {
                        if ($hasUserCoords) {
                            $q->where(function($sub) use ($lat, $lng, $radius) {
                                $sub->whereNotNull('latitude')
                                    ->whereNotNull('longitude')
                                    ->whereRaw(
                                        "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                                        [$lat, $lng, $lat, $radius]
                                    );
                            });
                        }
                        $q->orWhere('zip_code', 'like', "%{$zip}%");
                    });
                }

                $zipApplied = true;
            }
        }

        // 2. Filter by specific City (with state fallback support)
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

        // 3. Filter by specific State (Fixed: Search both listing's own state and provider's state)
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

        // 4. Filter by specific Zip Code if it wasn't already handled by radius search
        if (!isset($zipApplied) && ($request->has('zip_code') || $request->has('zipcode'))) {
            $z = $request->zip_code ?? $request->zipcode;
            if ($type === 'listing') {
                $query->where(function($q) use ($z) {
                    $q->where('zip_code', 'like', "%{$z}%")
                      ->orWhereHas('provider', function($sub) use ($z) {
                          $sub->where('zip_code', 'like', "%{$z}%");
                      });
                });
            } else {
                $query->where('zip_code', 'like', "%{$z}%");
            }
        }

        // 5. Filter by specific Location/Address
        if ($request->has('location')) {
            $location = $request->location;
            if ($type === 'listing') {
                $query->where(function($q) use ($location) {
                    $q->where('service_location', 'like', "%{$location}%")
                      ->orWhere('city', 'like', "%{$location}%")
                      ->orWhere('state', 'like', "%{$location}%")
                      ->orWhere('zip_code', 'like', "%{$location}%")
                      ->orWhereHas('provider', function($sub) use ($location) {
                          $sub->where('address', 'like', "%{$location}%")
                             ->orWhere('city', 'like', "%{$location}%")
                             ->orWhere('state', 'like', "%{$location}%")
                             ->orWhere('zip_code', 'like', "%{$location}%");
                      });
                });
            } else {
                $query->where(function($q) use ($location) {
                    $q->where('address', 'like', "%{$location}%")
                      ->orWhere('city', 'like', "%{$location}%")
                      ->orWhere('state', 'like', "%{$location}%")
                      ->orWhere('zip_code', 'like', "%{$location}%");
                });
            }
        }

        // 6. Generic search query logic (applied only if a zip code radius search wasn't triggered)
        if ($request->has('search') && !isset($zipApplied)) {
            $search = $request->search;
            if ($type === 'listing') {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('service_location', 'like', "%{$search}%")
                      ->orWhere('zip_code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%")
                      ->orWhereHas('provider', function($subQ) use ($search) {
                          $subQ->where('zip_code', 'like', "%{$search}%")
                               ->orWhere('city', 'like', "%{$search}%")
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
                      ->orWhere('zip_code', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%");
                });
            }
        }

        return $query;
    }
}
