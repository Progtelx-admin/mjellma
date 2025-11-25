<?php

namespace Modules\Car\Controllers;

use App\Http\Controllers\Controller;
use Modules\Car\Models\Car;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\CarBookingConfirmationEmail;
use DB;

class CarController extends Controller
{
    protected $carClass;
    protected $locationClass;
    protected $apiBase;
    protected $apiToken;
    protected $apiReferer;

    public function __construct(Car $carClass, Location $locationClass)
    {
        $this->carClass = $carClass;
        $this->locationClass = $locationClass;
        $this->apiBase = env('CAR_API_BASE');
        $this->apiToken = env('CAR_API_TOKEN');
        $this->apiReferer = env('CAR_API_REFERER');
    }

    public function callAction($method, $parameters)
    {
        if (!$this->carClass::isEnable()) {
            return redirect('/');
        }
        return parent::callAction($method, $parameters);
    }

    /**
     * Display car search page
     */
    public function index(Request $request)
    {
        $locations = $this->getCompanyLocations();
        
        // Get location list for legacy location search field
        $limit_location = 15;
        if (empty(setting_item("car_location_search_style")) || setting_item("car_location_search_style") == "normal") {
            $limit_location = 1000;
        }
        $list_location = $this->locationClass::where("status", "publish")
            ->limit($limit_location)
            ->with(['translation'])
            ->get()
            ->toTree();

        return view('Car::frontend.search', [
            'locations' => $locations,
            'list_location' => $list_location,
            'seo_meta' => $this->carClass->getSeoMetaForPageList()
        ]);
    }

    /**
     * Make API request to external car rental service
     */
    protected function makeApiRequest($endpoint, $method = 'GET', $data = [])
    {
        try {
            $url = rtrim($this->apiBase, '/') . '/' . ltrim($endpoint, '/');

            // Add referer as query parameter if provided
            if ($this->apiReferer) {
                if ($method === 'GET') {
                    $data['referer'] = $this->apiReferer;
                }
            }

            $request = Http::withHeaders([
                'Authorization' => $this->apiToken,  // No "Bearer" prefix
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            if ($method === 'GET') {
                $response = $request->get($url, $data);
            } else {
                // For POST, add referer in the body
                if ($this->apiReferer) {
                    $data['referer'] = $this->apiReferer;
                }
                $response = $request->post($url, $data);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Car API Request Failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'API request failed',
                'status' => $response->status(),
                'message' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Car API Exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get company locations from API
     * Cache for 1 hour to reduce API calls
     */
    public function getCompanyLocations($forceRefresh = false)
    {
        $cacheKey = 'car_company_locations';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            $response = $this->makeApiRequest('company-locations');

            if ($response['success'] && isset($response['data']['company_locations'])) {
                $locations = $response['data']['company_locations'];
                return $locations;
            }

            Log::warning('Failed to fetch company locations', [
                'response' => $response
            ]);

            return [];
        });
    }

    /**
     * Get locations formatted for dropdown/select
     */
    public function getLocationsForDropdown()
    {
        $locations = $this->getCompanyLocations();

        return collect($locations)->map(function ($location) {
            return [
                'id' => $location['id'],
                'name' => $location['address'],
                'extra_price' => $location['location_extra_price']
            ];
        })->toArray();
    }

    /**
     * Calculate total location extra price
     */
    public function calculateLocationExtraPrice($pickupLocationId, $dropoffLocationId = null)
    {
        $locations = $this->getCompanyLocations();
        $totalExtraPrice = 0;

        $pickupLocation = collect($locations)->firstWhere('id', $pickupLocationId);
        if ($pickupLocation && isset($pickupLocation['location_extra_price'])) {
            $totalExtraPrice += floatval($pickupLocation['location_extra_price']);
        }

        if ($dropoffLocationId && $dropoffLocationId != $pickupLocationId) {
            $dropoffLocation = collect($locations)->firstWhere('id', $dropoffLocationId);
            if ($dropoffLocation && isset($dropoffLocation['location_extra_price'])) {
                $totalExtraPrice += floatval($dropoffLocation['location_extra_price']);
            }
        }

        return $totalExtraPrice;
    }

    /**
     * API endpoint to get locations (for AJAX calls)
     */
    public function apiGetLocations(Request $request)
    {
        $forceRefresh = $request->input('refresh', false);
        $locations = $this->getCompanyLocations($forceRefresh);

        return response()->json([
            'success' => true,
            'locations' => $locations
        ]);
    }

    /**
     * Refresh locations cache manually
     */
    public function refreshLocations()
    {
        $locations = $this->getCompanyLocations(true);

        return response()->json([
            'success' => true,
            'message' => 'Locations refreshed successfully',
            'count' => count($locations),
            'locations' => $locations
        ]);
    }

    /**
     * Debug endpoint to test API connection
     */
    public function debugApi()
    {
        return response()->json([
            'env_check' => [
                'CAR_API_BASE' => $this->apiBase ?? 'NOT SET',
                'CAR_API_TOKEN' => $this->apiToken ? 'SET (length: ' . strlen($this->apiToken) . ')' : 'NOT SET',
                'CAR_API_REFERER' => $this->apiReferer ?? 'NOT SET',
            ],
            'test_request' => $this->makeApiRequest('company-locations'),
        ]);
    }

    /**
     * Search available car reservations
     */
    public function search(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'pickup_place' => 'required|integer',
            'return_place' => 'required|integer',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|date_format:H:i',
            'dropoff_date' => 'required|date|after:pickup_date',
            'return_time' => 'required|date_format:H:i',
        ]);

        // Convert dates from Y-m-d to dd/mm/YYYY format for API
        $pickupDate = \Carbon\Carbon::parse($request->input('pickup_date'))->format('d/m/Y');
        $dropoffDate = \Carbon\Carbon::parse($request->input('dropoff_date'))->format('d/m/Y');

        // Prepare API parameters
        $params = [
            'pickup_place' => (int) $request->input('pickup_place'),
            'return_place' => (int) $request->input('return_place'),
            'pickup_date' => $pickupDate,
            'pickup_time' => $request->input('pickup_time'),
            'dropoff_date' => $dropoffDate,
            'return_time' => $request->input('return_time'),
            'page' => $request->input('page', 1)
        ];

        // Add optional filters
        if ($request->filled('car_manufacturer')) {
            $params['car_manufacturer'] = (int) $request->input('car_manufacturer');
        }
        if ($request->filled('car_type')) {
            $params['car_type'] = (int) $request->input('car_type');
        }
        if ($request->filled('car_transmission')) {
            $params['car_transmission'] = (int) $request->input('car_transmission');
        }
        
        // Store category for client-side filtering (don't send to API)
        $clientCategory = $request->input('category'); // 'high', 'low', or 'popular'

        // First, fetch page 1 to check pagination
        $firstPageParams = array_merge($params, ['page' => 1]);
        $response = $this->makeApiRequest('reservations', 'GET', $firstPageParams);

        if ($response['success']) {
            $reservations = $response['data'];
            $allCars = $reservations['cars'] ?? [];

            // Check if API provides pagination info
            $pagination = $reservations['pagination'] ?? $reservations['meta'] ?? null;
            $estimatedPerPage = 12; // Typical API page size

            if ($pagination) {
                $totalPages = (int) ($pagination['total_pages'] ?? $pagination['last_page'] ?? $pagination['totalPages'] ?? 1);

                // Fetch remaining pages if there are more
                if ($totalPages > 1) {
                    for ($page = 2; $page <= $totalPages; $page++) {
                        $pageParams = array_merge($params, ['page' => $page]);
                        $pageResponse = $this->makeApiRequest('reservations', 'GET', $pageParams);

                        if ($pageResponse['success'] && isset($pageResponse['data']['cars'])) {
                            $pageCars = $pageResponse['data']['cars'];
                            $allCars = array_merge($allCars, $pageCars);
                        } else {
                            Log::warning("Failed to fetch API page $page", [
                                'page' => $page,
                                'response' => $pageResponse
                            ]);
                        }
                    }
                }
            } else {
                // API doesn't provide pagination info, but might still be paginating
                // Check if API returns cumulative results (page 2 includes page 1 + more)
                $page = 2;
                $maxPages = 50; // Safety limit
                $firstPageCount = count($allCars);
                $previousCount = $firstPageCount;

                // If first page has exactly estimatedPerPage items, likely there are more pages
                if ($firstPageCount >= $estimatedPerPage) {
                    $lastValidPage = 1;
                    $lastValidCars = $allCars;

                    while ($page <= $maxPages) {
                        $pageParams = array_merge($params, ['page' => $page]);
                        $pageResponse = $this->makeApiRequest('reservations', 'GET', $pageParams);

                        if ($pageResponse['success'] && isset($pageResponse['data']['cars'])) {
                            $pageCars = $pageResponse['data']['cars'];
                            $currentCount = count($pageCars);

                            // If page is empty, we've reached the end
                            if (empty($pageCars)) {
                                break;
                            }

                            // Check if this page has more results than previous (cumulative API)
                            if ($currentCount > $previousCount) {
                                // API returns cumulative results - use this page
                                $lastValidPage = $page;
                                $lastValidCars = $pageCars;
                                $previousCount = $currentCount;
                                $page++;
                            } else if ($currentCount == $previousCount) {
                                // Same count - we've reached the last page
                                break;
                            } else {
                                // Count decreased - shouldn't happen, but handle it
                                break;
                            }
                        } else {
                            break;
                        }
                    }

                    // Use the last valid page which contains all cumulative results
                    $allCars = $lastValidCars;
                }
            }

            // Filter cars by location logic
            $pickupLocationId = $params['pickup_place'];
            $returnLocationId = $params['return_place'];
            $carsBeforeFilter = count($allCars);

            if ($pickupLocationId == $returnLocationId) {
                // Same location - show only cars from that location
                $allCars = array_values(array_filter($allCars, function ($car) use ($pickupLocationId) {
                    $carLocationId = $car['company_location']['id'] ?? null;
                    return $carLocationId == $pickupLocationId;
                }));
            } else {
                // Different locations - show union (cars from BOTH locations)
                $allCars = array_values(array_filter($allCars, function ($car) use ($pickupLocationId, $returnLocationId) {
                    $carLocationId = $car['company_location']['id'] ?? null;
                    return $carLocationId == $pickupLocationId || $carLocationId == $returnLocationId;
                }));
            }

            // Apply client-side sorting
            if ($clientCategory) {
                usort($allCars, function ($a, $b) use ($clientCategory) {
                    if ($clientCategory === 'popular') {
                        // Sort by popular first (popular=1 before popular=0)
                        $aPopular = isset($a['popular']) && $a['popular'] == 1 ? 1 : 0;
                        $bPopular = isset($b['popular']) && $b['popular'] == 1 ? 1 : 0;
                        
                        // Popular cars first
                        if ($aPopular != $bPopular) {
                            return $bPopular <=> $aPopular; // 1 before 0
                        }
                        
                        // If both popular or both not popular, sort by price (lowest first)
                        $priceA = floatval($a['calculation']['day_price'] ?? 0);
                        $priceB = floatval($b['calculation']['day_price'] ?? 0);
                        return $priceA <=> $priceB;
                    } elseif ($clientCategory === 'high' || $clientCategory === 'low') {
                        // Sort by price
                        $priceA = floatval($a['calculation']['day_price'] ?? 0);
                        $priceB = floatval($b['calculation']['day_price'] ?? 0);
                        
                        if ($clientCategory === 'high') {
                            return $priceB <=> $priceA; // Highest first
                        } else {
                            return $priceA <=> $priceB; // Lowest first
                        }
                    }
                    
                    return 0;
                });
                
                $allCars = array_values($allCars); // Re-index array
            }

            // Cache the full results for Load More (5 minutes)
            // Include client category in cache key
            $cacheParams = array_merge($params, ['client_category' => $clientCategory]);
            $cacheKey = 'car_search_' . md5(json_encode($cacheParams));
            Cache::put($cacheKey, $allCars, 300);

            // Now implement frontend pagination (12 cars per page)
            $frontendPage = (int) $request->input('page', 1);
            $perPage = 12; // Frontend items per page
            $totalCars = count($allCars);
            $totalFrontendPages = max(1, (int) ceil($totalCars / $perPage));
            $offset = ($frontendPage - 1) * $perPage;

            // Always slice cars for current frontend page (even if less than perPage)
            $displayCars = array_slice($allCars, $offset, $perPage);
            $reservations['cars'] = $displayCars;

            $paginationData = [
                'current_page' => $frontendPage,
                'total_pages' => $totalFrontendPages,
                'per_page' => $perPage,
                'total' => $totalCars,
                'from' => $totalCars > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalCars)
            ];

            // Get location details for display
            $locations = $this->getCompanyLocations();
            $pickupLocation = collect($locations)->firstWhere('id', $params['pickup_place']);
            $dropoffLocation = collect($locations)->firstWhere('id', $params['return_place']);

            // Get filter data for the view
            $manufacturers = $this->getCarManufacturers();
            $categories = $this->getCarCategories();
            $transmissions = $this->getCarTransmissions();

            // Calculate total location extra price
            $locationExtraPrice = $this->calculateLocationExtraPrice(
                $params['pickup_place'],
                $params['return_place']
            );

            return view('Car::frontend.search-results', [
                'reservations' => $reservations,
                'pickup_location' => $pickupLocation,
                'dropoff_location' => $dropoffLocation,
                'search_params' => $params,
                'pagination' => $paginationData,
                'manufacturers' => $manufacturers,
                'categories' => $categories,
                'transmissions' => $transmissions,
                'location_extra_price' => $locationExtraPrice,
                'success' => true
            ]);
        } else {
            Log::error('Failed to fetch car reservations', [
                'error' => $response['error'] ?? 'Unknown error',
                'params' => $params
            ]);

            return back()->withErrors([
                'search' => $response['error'] ?? 'Failed to fetch available cars. Please try again.'
            ])->withInput();
        }
    }

    /**
     * Display car details page
     */
    public function detail(Request $request, $slug)
    {
        // Validate required parameters
        $validated = $request->validate([
            'car_id' => 'required|integer',
            'pickup_place' => 'required|integer',
            'return_place' => 'required|integer',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|date_format:H:i',
            'dropoff_date' => 'required|date|after_or_equal:pickup_date',
            'return_time' => 'required|date_format:H:i',
        ]);

        $carId = (int) $request->input('car_id');

        // Convert dates to YYYY/mm/ddTH:i format for API
        $pickupDatetime = \Carbon\Carbon::parse($request->input('pickup_date') . ' ' . $request->input('pickup_time'))
            ->format('Y/m/d\TH:i');
        $dropoffDatetime = \Carbon\Carbon::parse($request->input('dropoff_date') . ' ' . $request->input('return_time'))
            ->format('Y/m/d\TH:i');

        // Prepare API parameters
        $params = [
            'pickup_place' => (int) $request->input('pickup_place'),
            'dropoff_location' => (int) $request->input('return_place'), // Note: API uses 'dropoff_location'
            'pickup_datetime' => $pickupDatetime,
            'dropoff_datetime' => $dropoffDatetime,
        ];

        // Call the car details API
        $response = $this->makeApiRequest("reservations/details/{$carId}", 'GET', $params);

        if ($response['success'] && isset($response['data'])) {
            $carDetails = $response['data'];

            // Get location details
            $locations = $this->getCompanyLocations();
            $pickupLocation = collect($locations)->firstWhere('id', $params['pickup_place']);
            $dropoffLocation = collect($locations)->firstWhere('id', $params['dropoff_location']);

            // Calculate location extra price
            $locationExtraPrice = $this->calculateLocationExtraPrice(
                $params['pickup_place'],
                $params['dropoff_location']
            );

            // Calculate duration
            $pickupDate = \Carbon\Carbon::parse($request->input('pickup_date') . ' ' . $request->input('pickup_time'));
            $dropoffDate = \Carbon\Carbon::parse($request->input('dropoff_date') . ' ' . $request->input('return_time'));
            $duration = $pickupDate->diffInDays($dropoffDate);
            if ($duration == 0) {
                $duration = 1; // Minimum 1 day
            }

            return view('Car::frontend.detail-api', [
                'car' => $carDetails,
                'pickup_location' => $pickupLocation,
                'dropoff_location' => $dropoffLocation,
                'pickup_datetime' => $pickupDate,
                'dropoff_datetime' => $dropoffDate,
                'duration' => $duration,
                'location_extra_price' => $locationExtraPrice,
                'search_params' => $request->all(),
            ]);
        } else {
            Log::error('Failed to fetch car details', [
                'car_id' => $carId,
                'error' => $response['error'] ?? 'Unknown error'
            ]);

            return back()->withErrors([
                'car' => $response['error'] ?? 'Failed to load car details. Please try again.'
            ]);
        }
    }

    /**
     * Get available reservations (API endpoint for AJAX)
     */
    public function apiGetReservations(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'pickup_place' => 'required|integer',
            'return_place' => 'required|integer',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|date_format:H:i',
            'dropoff_date' => 'required|date|after:pickup_date',
            'return_time' => 'required|date_format:H:i',
            'page' => 'nullable|integer|min:1'
        ]);

        // Convert dates from Y-m-d to dd/mm/YYYY format
        $pickupDate = \Carbon\Carbon::parse($request->input('pickup_date'))->format('d/m/Y');
        $dropoffDate = \Carbon\Carbon::parse($request->input('dropoff_date'))->format('d/m/Y');

        // Prepare API parameters
        $params = [
            'pickup_place' => (int) $request->input('pickup_place'),
            'return_place' => (int) $request->input('return_place'),
            'pickup_date' => $pickupDate,
            'pickup_time' => $request->input('pickup_time'),
            'dropoff_date' => $dropoffDate,
            'return_time' => $request->input('return_time'),
            'page' => $request->input('page', 1)
        ];

        // Add optional filters
        if ($request->filled('car_manufacturer')) {
            $params['car_manufacturer'] = (int) $request->input('car_manufacturer');
        }
        if ($request->filled('car_type')) {
            $params['car_type'] = (int) $request->input('car_type');
        }
        if ($request->filled('car_transmission')) {
            $params['car_transmission'] = (int) $request->input('car_transmission');
        }
        if ($request->filled('category')) {
            $params['category'] = $request->input('category'); // 'high', 'low', or 'popular'
        }

        // Call the reservations API
        $response = $this->makeApiRequest('reservations', 'GET', $params);

        if ($response['success']) {
            return response()->json([
                'success' => true,
                'data' => $response['data']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $response['error'] ?? 'Failed to fetch reservations',
                'message' => $response['message'] ?? 'Unknown error'
            ], 400);
        }
    }

    /**
     * Get car manufacturers from API
     */
    public function getCarManufacturers($forceRefresh = false)
    {
        $cacheKey = 'car_manufacturers';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            $response = $this->makeApiRequest('car-manufacturers');

            if ($response['success']) {
                // Try different possible response structures
                if (isset($response['data']['car_manufacturers'])) {
                    return $response['data']['car_manufacturers'];
                } elseif (isset($response['data']) && is_array($response['data'])) {
                    // If data is directly an array
                    return $response['data'];
                }
            }

            Log::warning('Failed to fetch car manufacturers', [
                'success' => $response['success'] ?? false,
                'has_data' => isset($response['data']),
                'response_keys' => isset($response['data']) ? array_keys($response['data']) : []
            ]);
            return [];
        });
    }

    /**
     * Get car categories from API
     */
    public function getCarCategories($forceRefresh = false)
    {
        $cacheKey = 'car_categories';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            $response = $this->makeApiRequest('car-categories');

            if ($response['success']) {
                // Try different possible response structures
                if (isset($response['data']['car_categories'])) {
                    return $response['data']['car_categories'];
                } elseif (isset($response['data']) && is_array($response['data'])) {
                    // If data is directly an array
                    return $response['data'];
                }
            }

            Log::warning('Failed to fetch car categories', [
                'success' => $response['success'] ?? false,
                'has_data' => isset($response['data']),
                'response_keys' => isset($response['data']) ? array_keys($response['data']) : []
            ]);
            return [];
        });
    }

    /**
     * Get car transmissions from API
     */
    public function getCarTransmissions($forceRefresh = false)
    {
        $cacheKey = 'car_transmissions';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            $response = $this->makeApiRequest('car-transmissions');

            if ($response['success']) {
                // Try different possible response structures
                if (isset($response['data']['car_transmissions'])) {
                    return $response['data']['car_transmissions'];
                } elseif (isset($response['data']) && is_array($response['data'])) {
                    // If data is directly an array
                    return $response['data'];
                }
            }

            Log::warning('Failed to fetch car transmissions', [
                'success' => $response['success'] ?? false,
                'has_data' => isset($response['data']),
                'response_keys' => isset($response['data']) ? array_keys($response['data']) : []
            ]);
            return [];
        });
    }

    /**
     * API endpoint to get manufacturers (for AJAX calls)
     */
    public function apiGetManufacturers(Request $request)
    {
        $forceRefresh = $request->input('refresh', false);
        $manufacturers = $this->getCarManufacturers($forceRefresh);

        return response()->json([
            'success' => true,
            'manufacturers' => $manufacturers
        ]);
    }

    /**
     * API endpoint to get categories (for AJAX calls)
     */
    public function apiGetCategories(Request $request)
    {
        $forceRefresh = $request->input('refresh', false);
        $categories = $this->getCarCategories($forceRefresh);

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * API endpoint to get transmissions (for AJAX calls)
     */
    public function apiGetTransmissions(Request $request)
    {
        $forceRefresh = $request->input('refresh', false);
        $transmissions = $this->getCarTransmissions($forceRefresh);

        return response()->json([
            'success' => true,
            'transmissions' => $transmissions
        ]);
    }

    /**
     * Process checkout with external car rental API (Step 1)
     * This fetches reservation details and countries
     */
    public function checkout(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'car_id' => 'required|integer',
            'pickup_place' => 'required|integer',
            'dropoff_location' => 'required|integer',
            'pickup_datetime' => 'required|string',
            'dropoff_datetime' => 'required|string',
            'franch' => 'required|integer',
            'extras' => 'nullable|array',
            'franchise' => 'nullable|array',
        ]);

        // Format datetime for API (YYYY-mm-ddTH%3Ai format)
        // Input: "2025-11-01 10:00" -> Output: "2025-11-01T10%3A00"
        $pickupDatetime = str_replace([':', ' '], ['%3A', 'T'], $validated['pickup_datetime']);
        $dropoffDatetime = str_replace([':', ' '], ['%3A', 'T'], $validated['dropoff_datetime']);

        // Prepare extras string (No%2CNo%2CNo format)
        $extrasString = 'No%2CNo%2CNo'; // Default
        if (!empty($validated['extras'])) {
            // Build extras string based on selected extras
            $extrasIds = collect($validated['extras'])->pluck('id')->toArray();
            if (!empty($extrasIds)) {
                $extrasString = implode('%2C', $extrasIds);
            }
        }

        // Prepare additional and franchising objects
        $additional = [];
        if (!empty($validated['extras'])) {
            foreach ($validated['extras'] as $extra) {
                $additional[] = [
                    'id' => $extra['id'],
                    'name' => $extra['name'] ?? '',
                    'price' => $extra['price'] ?? 0
                ];
            }
        }

        $franchising = [];
        if (!empty($validated['franchise'])) {
            $franchising = [
                'id' => $validated['franchise']['id'] ?? null,
                'name' => $validated['franchise']['name'] ?? '',
                'price' => $validated['franchise']['price'] ?? 0
            ];
        }

        // Prepare API request data for /api/checkout
        $apiData = [
            'car_id' => (int) $validated['car_id'],
            'pickup_place' => (int) $validated['pickup_place'],
            'dropoff_location' => (int) $validated['dropoff_location'],
            'pickup_datetime' => $pickupDatetime,
            'dropoff_datetime' => $dropoffDatetime,
            'franch' => (int) $validated['franch'],
            'extra' => $extrasString,
            'referer' => $this->apiReferer ?? 'Referer',
            'aditional' => !empty($additional) ? (object) $additional : (object) [],
            'franchising' => !empty($franchising) ? (object) $franchising : (object) []
        ];

        // Call external API checkout endpoint
        $response = $this->makeApiRequest('checkout', 'POST', $apiData);

        if ($response['success'] && isset($response['data'])) {
            // Store checkout data in session for the form page
            session([
                'car_checkout_step1' => [
                    'api_response' => $response['data'],
                    'request_data' => $validated,
                    'extras' => $validated['extras'] ?? [],
                    'franchise' => $validated['franchise'] ?? [],
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Loading checkout form...'),
                'redirect' => route('car.checkout.form')
            ]);
        } else {
            Log::error('Car checkout API failed', [
                'error' => $response['error'] ?? 'Unknown',
                'status' => $response['status'] ?? null,
                'message' => $response['message'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? $response['error'] ?? __('Checkout failed. Please try again.')
            ], 400);
        }
    }

    /**
     * Display checkout form page (Step 2)
     */
    public function checkoutForm()
    {
        $checkoutData = session('car_checkout_step1');

        if (!$checkoutData) {
            return redirect()->route('car.search')->with('error', __('No checkout data found. Please start again.'));
        }

        $apiResponse = $checkoutData['api_response'] ?? [];

        // Merge user-selected extras into the reservation data
        if (!empty($checkoutData['extras'])) {
            $apiResponse['selected_extra_equipment'] = $checkoutData['extras'];
        }

        // Add franchise info if available
        if (!empty($checkoutData['franchise'])) {
            $apiResponse['selected_franchise'] = $checkoutData['franchise'];
        }

        return view('Car::frontend.checkout-form', [
            'checkout_data' => $checkoutData,
            'api_response' => $apiResponse,
            'countries' => $apiResponse['countries'] ?? [],
            'reservation' => $apiResponse,
        ]);
    }

    /**
     * Store checkout - Complete the booking (Step 3)
     */
    public function storeCheckout(Request $request)
    {
        $checkoutData = session('car_checkout_step1');

        if (!$checkoutData) {
            return response()->json([
                'success' => false,
                'message' => __('Session expired. Please start again.')
            ], 400);
        }

        // Validate customer information
        $validated = $request->validate([
            'title' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'birthday' => 'required|date', // Changed to accept date format from date input (YYYY-MM-DD)
            'driving_license' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'plz' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'payment_method' => 'required|string|in:pcb_bank,cash',
        ]);

        // Convert birthday from YYYY-MM-DD to dd.mm.YYYY format for API
        $birthday = \Carbon\Carbon::parse($validated['birthday'])->format('d.m.Y');

        // Check if PCB Bank payment is selected
        if ($validated['payment_method'] === 'pcb_bank') {
            return $this->processPcbBankPayment($request, $checkoutData, $validated, $birthday);
        }

        // Continue with cash payment flow (direct API call)

        $requestData = $checkoutData['request_data'];
        $apiResponse = $checkoutData['api_response'];

        // Calculate day_nr (number of days)
        $pickupDate = \Carbon\Carbon::parse(str_replace('T', ' ', str_replace('%3A', ':', $requestData['pickup_datetime'])));
        $dropoffDate = \Carbon\Carbon::parse(str_replace('T', ' ', str_replace('%3A', ':', $requestData['dropoff_datetime'])));
        $dayNr = max(1, $pickupDate->diffInDays($dropoffDate));

        // Prepare extra_equipment array
        $extraEquipment = [];
        if (!empty($checkoutData['extras'])) {
            foreach ($checkoutData['extras'] as $index => $extra) {
                $extraEquipment[$index + 1] = (string) ($extra['id'] ?? '');
            }
        }

        // Format datetime for store-checkout (yyyy-mm-dd H:i)
        $pickupDatetimeFormatted = $pickupDate->format('Y-m-d H:i');
        $dropoffDatetimeFormatted = $dropoffDate->format('Y-m-d H:i');

        // Prepare API data for store-checkout
        $storeData = [
            'title' => $validated['title'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'birthday' => $birthday, // Use converted birthday format (dd.mm.YYYY)
            'driving_license' => $validated['driving_license'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'plz' => $validated['plz'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'collision_damage' => (int) $requestData['franch'],
            'day_nr' => (int) $dayNr,
            'extra_equipment' => $extraEquipment,
            'car_id' => (int) $requestData['car_id'],
            'pickup_datetime' => $pickupDatetimeFormatted,
            'dropoff_datetime' => $dropoffDatetimeFormatted,
            'pickup_location' => (int) $requestData['pickup_place'],
            'dropoff_location' => (int) $requestData['dropoff_location'],
            'referer' => $this->apiReferer ?? 'REFERAL',
        ];

        // Call external API store-checkout endpoint
        $response = $this->makeApiRequest('store-checkout', 'POST', $storeData);

        if ($response['success']) {
            // Store final confirmation data in session
            session([
                'car_booking_complete' => [
                    'api_response' => $response['data'] ?? [],
                    'customer_data' => $validated,
                    'reservation_data' => $requestData,
                ]
            ]);

            // Send confirmation email
            try {
                // Merge API response with customer and reservation data for complete email
                $emailData = array_merge(
                    $response['data'] ?? [],
                    [
                        'customer' => $validated,
                        'pickup_datetime' => $pickupDatetimeFormatted,
                        'dropoff_datetime' => $dropoffDatetimeFormatted,
                        'days' => $dayNr,
                        'extras' => $checkoutData['extras'] ?? [],
                        'franchise' => $checkoutData['franchise'] ?? [],
                    ]
                );
                
                Mail::to($validated['email'])
                    ->send(new CarBookingConfirmationEmail($emailData, $validated));
            } catch (\Exception $e) {
                Log::error('Failed to send car booking confirmation email', [
                    'error' => $e->getMessage(),
                    'email' => $validated['email'],
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't fail the booking if email fails
            }

            // Clear step 1 data
            session()->forget('car_checkout_step1');

            return response()->json([
                'success' => true,
                'message' => __('Booking completed successfully!'),
                'redirect' => route('car.checkout.confirm')
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? $response['error'] ?? __('Booking failed. Please try again.')
            ], 400);
        }
    }

    /**
     * Display checkout confirmation page (Final step)
     */
    public function checkoutConfirm()
    {
        $bookingData = session('car_booking_complete');

        if (!$bookingData) {
            return redirect()->route('car.search')->with('error', __('No booking data found'));
        }

        return view('Car::frontend.checkout-confirm', [
            'booking_data' => $bookingData,
            'api_response' => $bookingData['api_response'] ?? [],
            'customer_data' => $bookingData['customer_data'] ?? [],
        ]);
    }

    /**
     * Process PCB Bank payment for car rental
     */
    protected function processPcbBankPayment($request, $checkoutData, $validated, $birthday)
    {
        // Generate unique token for this booking
        $token = \Illuminate\Support\Str::random(32);

        // Store all booking data in session for later processing
        $bookingData = array_merge($validated, [
            'birthday' => $birthday,
            'reservation_data' => $checkoutData['request_data'],
            'extras' => $checkoutData['extras'] ?? [],
            'franchise' => $checkoutData['franchise'] ?? [],
            'api_response' => $checkoutData['api_response'] ?? [],
        ]);

        session(["car_pending_booking_{$token}" => $bookingData]);

        // Calculate total amount to charge
        $calculation = $checkoutData['api_response']['calculation'] ?? [];
        $grandTotal = floatval($calculation['grand_total'] ?? 0);
        
        // Add extras total
        $extrasTotal = 0;
        if (!empty($checkoutData['extras'])) {
            foreach ($checkoutData['extras'] as $extra) {
                $extrasTotal += floatval($extra['price'] ?? 0);
            }
        }
        
        // Add franchise total
        $franchiseTotal = 0;
        if (!empty($checkoutData['franchise'])) {
            $franchisePrice = floatval($checkoutData['franchise']['price'] ?? 0);
            $days = $checkoutData['api_response']['days'] ?? 1;
            $franchiseTotal = $franchisePrice * $days;
        }
        
        $totalAmount = $grandTotal + $extrasTotal + $franchiseTotal;

        $description = 'Car Rental Payment - ' . $validated['first_name'] . ' ' . $validated['last_name'];
        $redirectUrl = route('car.pcb.return', ['token' => $token]);

        // Create PCB Bank order
        $pcb = new \App\Services\PcbBankService();
        $order = $pcb->createOrder($totalAmount, $description, $redirectUrl);

        if ($order && isset($order['id'], $order['password'], $order['hppUrl'])) {
            // Store PCB order in session
            session([
                "car_pcb_order_{$token}" => [
                    'id' => $order['id'],
                    'password' => $order['password'],
                ]
            ]);

            // Return redirect URL for AJAX
            return response()->json([
                'success' => true,
                'redirect' => $order['hppUrl'] . "?id={$order['id']}&password={$order['password']}"
            ]);
        }

        Log::error('❌ PCB createOrder failed for car rental');
        return response()->json([
            'success' => false,
            'message' => __('Failed to initialize payment gateway. Please try again.')
        ], 400);
    }

    /**
     * Handle PCB Bank return for car rental
     */
    public function handlePcbReturn(Request $request)
    {
        $orderId = $request->query('ID');
        $status = $request->query('STATUS');
        $token = $request->query('token');

        // Retrieve booking data from session
        $bookingData = session("car_pending_booking_{$token}");
        $pcbOrder = session("car_pcb_order_{$token}");

        if (!$bookingData || !$pcbOrder) {
            Log::error('No car booking data found in session');
            return redirect()->route('car.search')->withErrors(['error' => 'Session expired. Please try again.']);
        }

        // Check if payment was successful
        if (strtolower($status) !== 'fullypaid') {
            Log::error('PCB Bank payment was not successful for car rental', ['status' => $status]);
            return redirect()->route('car.search')->withErrors(['error' => 'Payment was not successful.']);
        }

        // Store PCB Bank order details
        $pcbService = new \App\Services\PcbBankService();
        $orderDetails = $pcbService->getOrderDetails($pcbOrder['id'], $pcbOrder['password']);

        if ($orderDetails) {
            session(["car_pcb_response_{$token}" => $orderDetails]);
        }

        // Complete the booking with the external car API
        return $this->completeCarBookingAfterPayment($bookingData, $pcbOrder, $token);
    }

    /**
     * Complete car booking after PCB Bank payment
     */
    protected function completeCarBookingAfterPayment($bookingData, $pcbOrder, $token)
    {
        $requestData = $bookingData['reservation_data'];

        // Calculate day_nr
        $pickupDate = \Carbon\Carbon::parse($requestData['pickup_datetime']);
        $dropoffDate = \Carbon\Carbon::parse($requestData['dropoff_datetime']);
        $dayNr = max(1, $pickupDate->diffInDays($dropoffDate));

        // Prepare extra_equipment array
        $extraEquipment = [];
        if (!empty($bookingData['extras'])) {
            foreach ($bookingData['extras'] as $index => $extra) {
                $extraEquipment[$index + 1] = (string) ($extra['id'] ?? '');
            }
        }

        // Format datetime for store-checkout (yyyy-mm-dd H:i)
        $pickupDatetimeFormatted = $pickupDate->format('Y-m-d H:i');
        $dropoffDatetimeFormatted = $dropoffDate->format('Y-m-d H:i');

        // Prepare API data for store-checkout
        $storeData = [
            'title' => $bookingData['title'],
            'first_name' => $bookingData['first_name'],
            'last_name' => $bookingData['last_name'],
            'email' => $bookingData['email'],
            'birthday' => $bookingData['birthday'],
            'driving_license' => $bookingData['driving_license'],
            'phone' => $bookingData['phone'],
            'address' => $bookingData['address'],
            'plz' => $bookingData['plz'],
            'city' => $bookingData['city'],
            'country' => $bookingData['country'],
            'collision_damage' => (int) $requestData['franch'],
            'day_nr' => (int) $dayNr,
            'extra_equipment' => $extraEquipment,
            'car_id' => (int) $requestData['car_id'],
            'pickup_datetime' => $pickupDatetimeFormatted,
            'dropoff_datetime' => $dropoffDatetimeFormatted,
            'pickup_location' => (int) $requestData['pickup_place'],
            'dropoff_location' => (int) $requestData['dropoff_location'],
            'referer' => $this->apiReferer ?? 'REFERAL',
            'payment_method' => 'pcb_bank',
            'pcb_order_id' => $pcbOrder['id'],
        ];

        // Call external API store-checkout endpoint
        $response = $this->makeApiRequest('store-checkout', 'POST', $storeData);

        if ($response['success']) {
            // Store final confirmation data in session
            session([
                'car_booking_complete' => [
                    'api_response' => $response['data'] ?? [],
                    'customer_data' => $bookingData,
                    'reservation_data' => $requestData,
                    'pcb_order' => $pcbOrder,
                ]
            ]);

            // Send confirmation email
            try {
                // Merge API response with customer and reservation data for complete email
                $emailData = array_merge(
                    $response['data'] ?? [],
                    [
                        'customer' => $bookingData,
                        'pickup_datetime' => $pickupDatetimeFormatted,
                        'dropoff_datetime' => $dropoffDatetimeFormatted,
                        'days' => $dayNr,
                        'extras' => $bookingData['extras'] ?? [],
                        'franchise' => $bookingData['franchise'] ?? [],
                        'payment_method' => 'PCB Bank',
                        'pcb_order_id' => $pcbOrder['id'],
                    ]
                );
                
                Mail::to($bookingData['email'])
                    ->send(new CarBookingConfirmationEmail($emailData, $bookingData));
            } catch (\Exception $e) {
                Log::error('Failed to send car booking confirmation email (PCB payment)', [
                    'error' => $e->getMessage(),
                    'email' => $bookingData['email'],
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't fail the booking if email fails
            }

            // Clear session data
            session()->forget("car_pending_booking_{$token}");
            session()->forget("car_pcb_order_{$token}");
            session()->forget('car_checkout_step1');

            return redirect()->route('car.checkout.confirm')->with('success', __('Booking completed successfully!'));
        } else {
            return redirect()->route('car.search')->withErrors([
                'error' => $response['message'] ?? $response['error'] ?? __('Booking failed. Please try again.')
            ]);
        }
    }

    /**
     * API endpoint for Load More functionality (AJAX)
     * Fetch only the requested page, not all pages
     */
    public function loadMore(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'pickup_place' => 'required|integer',
            'return_place' => 'required|integer',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|date_format:H:i',
            'dropoff_date' => 'required|date|after:pickup_date',
            'return_time' => 'required|date_format:H:i',
            'page' => 'required|integer|min:1',
            'total_loaded' => 'nullable|integer' // How many cars already loaded on frontend
        ]);

        // Convert dates
        $pickupDate = \Carbon\Carbon::parse($request->input('pickup_date'))->format('d/m/Y');
        $dropoffDate = \Carbon\Carbon::parse($request->input('dropoff_date'))->format('d/m/Y');

        $frontendPage = (int) $request->input('page'); // Frontend page (2, 3, 4...)
        $perPage = 12; // Frontend items per page

        // Calculate how many cars we need to skip
        $alreadyLoaded = (int) $request->input('total_loaded', ($frontendPage - 1) * $perPage);

        // Prepare API parameters
        $params = [
            'pickup_place' => (int) $request->input('pickup_place'),
            'return_place' => (int) $request->input('return_place'),
            'pickup_date' => $pickupDate,
            'pickup_time' => $request->input('pickup_time'),
            'dropoff_date' => $dropoffDate,
            'return_time' => $request->input('return_time'),
            'page' => 1
        ];

        // Add optional filters
        if ($request->filled('car_manufacturer')) {
            $params['car_manufacturer'] = (int) $request->input('car_manufacturer');
        }
        if ($request->filled('car_type')) {
            $params['car_type'] = (int) $request->input('car_type');
        }
        if ($request->filled('car_transmission')) {
            $params['car_transmission'] = (int) $request->input('car_transmission');
        }
        
        // Store category for client-side filtering (don't send to API)
        $clientCategory = $request->input('category');

        // Create cache key from search params (including client category)
        $cacheParams = array_merge($params, ['client_category' => $clientCategory]);
        $cacheKey = 'car_search_' . md5(json_encode($cacheParams));

        // Try to get cached results first (from initial search)
        $allCars = Cache::get($cacheKey);

        if ($allCars && is_array($allCars)) {
            // Using cached results
        } else {
            // Fetch ALL cars from API (same logic as main search)
            $allCars = [];
            $apiPage = 1;
            $maxApiPages = 50;
            $estimatedPerPage = 12;
            $previousCount = 0;
            $lastValidPage = 1;
            $lastValidCars = [];

            // Keep fetching API pages until we reach the end
            while ($apiPage <= $maxApiPages) {
                $apiParams = array_merge($params, ['page' => $apiPage]);
                $response = $this->makeApiRequest('reservations', 'GET', $apiParams);

                if ($response['success'] && isset($response['data']['cars'])) {
                    $cars = $response['data']['cars'];
                    $carsCount = count($cars);

                    Log::info("Fetching API page $apiPage for load more", [
                        'cars_returned' => $carsCount,
                        'previous_count' => $previousCount,
                        'is_cumulative' => $carsCount > $previousCount
                    ]);

                    if ($apiPage == 1) {
                        // First page
                        $allCars = $cars;
                        $previousCount = $carsCount;
                        $lastValidPage = 1;
                        $lastValidCars = $cars;
                    } else {
                        // Check if API returns cumulative results
                        if ($carsCount > $previousCount) {
                            // Cumulative - save this page
                            $lastValidPage = $apiPage;
                            $lastValidCars = $cars;
                            $previousCount = $carsCount;
                            $allCars = $cars;
                        } else if ($carsCount == $previousCount) {
                            // Same count - we've reached the end
                            break;
                        } else {
                            // Count decreased - shouldn't happen
                            Log::warning("Page $apiPage has fewer results - using previous");
                            break;
                        }
                    }

                    $apiPage++;
                } else {
                    break;
                }
            }

            // Filter cars by location logic (same as main search)
            $pickupLocationId = $params['pickup_place'];
            $returnLocationId = $params['return_place'];
            $carsBeforeFilter = count($allCars);

            if ($pickupLocationId == $returnLocationId) {
                // Same location - show only cars from that location
                $allCars = array_values(array_filter($allCars, function ($car) use ($pickupLocationId) {
                    $carLocationId = $car['company_location']['id'] ?? null;
                    return $carLocationId == $pickupLocationId;
                }));
            } else {
                // Different locations - show union (cars from BOTH locations)
                $allCars = array_values(array_filter($allCars, function ($car) use ($pickupLocationId, $returnLocationId) {
                    $carLocationId = $car['company_location']['id'] ?? null;
                    return $carLocationId == $pickupLocationId || $carLocationId == $returnLocationId;
                }));
            }

            // Apply client-side sorting
            if ($clientCategory) {
                usort($allCars, function ($a, $b) use ($clientCategory) {
                    if ($clientCategory === 'popular') {
                        // Sort by popular first (popular=1 before popular=0)
                        $aPopular = isset($a['popular']) && $a['popular'] == 1 ? 1 : 0;
                        $bPopular = isset($b['popular']) && $b['popular'] == 1 ? 1 : 0;
                        
                        // Popular cars first
                        if ($aPopular != $bPopular) {
                            return $bPopular <=> $aPopular; // 1 before 0
                        }
                        
                        // If both popular or both not popular, sort by price (lowest first)
                        $priceA = floatval($a['calculation']['day_price'] ?? 0);
                        $priceB = floatval($b['calculation']['day_price'] ?? 0);
                        return $priceA <=> $priceB;
                    } elseif ($clientCategory === 'high' || $clientCategory === 'low') {
                        // Sort by price
                        $priceA = floatval($a['calculation']['day_price'] ?? 0);
                        $priceB = floatval($b['calculation']['day_price'] ?? 0);
                        
                        if ($clientCategory === 'high') {
                            return $priceB <=> $priceA; // Highest first
                        } else {
                            return $priceA <=> $priceB; // Lowest first
                        }
                    }
                    
                    return 0;
                });
                
                $allCars = array_values($allCars); // Re-index array
            }

            // Cache the results for future Load More requests (5 minutes)
            Cache::put($cacheKey, $allCars, 300);
        }

        $totalCars = count($allCars);

        // Get only the next 12 cars for this frontend page
        $displayCars = array_slice($allCars, $alreadyLoaded, $perPage);

        // Render HTML for the cars
        $html = '';
        foreach ($displayCars as $car) {
            $html .= view('Car::frontend.layouts.search.api-car-item', ['car' => $car])->render();
        }

        $newTotalLoaded = $alreadyLoaded + count($displayCars);
        $hasMore = $newTotalLoaded < $totalCars;

        return response()->json([
            'success' => true,
            'html' => $html,
            'loaded_text' => __('Loaded :current of :total cars', ['current' => $newTotalLoaded, 'total' => $totalCars]),
            'all_loaded_text' => __('All :total cars loaded', ['total' => $totalCars]),
            'current_page' => $frontendPage,
            'total_loaded' => $newTotalLoaded,
            'total_available' => $totalCars,
            'has_more' => $hasMore
        ]);
    }
}
