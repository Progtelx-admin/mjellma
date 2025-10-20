<?php

namespace Modules\Hotel\Controllers;

use App\Http\Controllers\Controller;
use Modules\Hotel\Models\HotelH;
use Modules\Hotel\Models\MjellmaBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Mail\BookingConfirmationEmail;
use Illuminate\Support\Facades\Mail;
use Modules\Hotel\Events\MjellmaBookingCreatedEvent;
use Carbon\Carbon;


class HotelHController extends Controller
{
    private $apiUrl;
    private $username;
    private $password;

    /**
     * Default HTTP options used for all API requests. These options
     * force the underlying HTTP client to resolve and connect via IPv4
     * only. Without these settings, the library may prefer IPv6
     * addresses, which can cause "not_allowed_host" errors when the
     * remote API only whitelists your IPv4 egress IP. See
     * https://php.net/manual/en/function.curl-setopt.php for details on
     * the CURLOPT_IPRESOLVE constant. Both the Guzzle-specific
     * 'force_ip_resolve' and the lower-level cURL option are set
     * redundantly so that either layer respects IPv4-only resolution.
     *
     * @var array
     */
    private array $httpOptions;

    public function __construct()
    {
        // Pull from .env; keep a safe default for API_URL only
        $this->apiUrl = env('API_URL', 'https://api.worldota.net/api/b2b/v3/');
        $this->username = env('API_USERNAME');
        $this->password = env('API_PASSWORD');

        // Initialise HTTP options to force IPv4 resolution on every request.
        // Setting both 'force_ip_resolve' and the underlying cURL option
        // ensures the request uses IPv4 even if IPv6 is available. Without
        // this, RateHawk/ETG endpoints may reject the call with
        // `not_allowed_host` because the IPv6 address is not whitelisted.
        $this->httpOptions = [
            'force_ip_resolve' => 'v4',
            'curl' => [
                \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4,
            ],
        ];
    }
    /**
     * List of errors returned from the booking finish call that should be treated
     * as final.  When one of these errors is encountered, we will not attempt
     * to poll the finish status endpoint because the documentation indicates
     * there is no chance of recovery by retrying.  See RateHawk docs for
     * definitions of these error codes.  Errors contained in this list are
     * considered unrecoverable and will not trigger any further polling of the
     * finish/status endpoint.  Both `double_booking_form` and
     * `double_booking_finish` are included here because they indicate that the
     * corresponding API method has been invoked multiple times for the same
     * booking.  According to RateHawk, these errors do not represent a
     * booking interruption; rather they signal that the client should stop
     * calling the same method again.  Likewise, `timeout`, `unknown` and
     * HTTP 5xx responses are not listed here since those conditions instruct
     * us to poll the status endpoint.
     *
     * @var string[]
     */
    private array $finalFinishErrors = [
        'book_hash_not_found',
        'booking_form',
        'booking_form_expired',
        'chosen_payment_type_was_not_available_on_booking_form',
        'email',
        'incorrect_chosen_payment_type',
        'incorrect_guests_number',
        'incorrect_children_data',
        'incorrect_rooms_number',
        'insufficient_b2b_balance',
        'order_not_found',
        'rate_not_found',
        'return_path_required',
        'unauthorized_group_booking',
        'arrival_date_differs_from_checkin_date',
        'not_enough_credit_card_data',
        'incorrect_init_uuid_format',
        'incorrect_pay_uuid_format',
        'sandbox_restriction',
        'supplier_data_required',
        // Generic authentication/contract errors
        'decoding_json',
        'endpoint_exceeded_limit',
        'endpoint_not_active',
        'endpoint_not_found',
        'incorrect_credentials',
        'invalid_auth_header',
        'invalid_params',
        'no_auth_header',
        'not_allowed_host',
        'overdue_debt',
        'unexpected_method',
        // booking_finish_did_not_succeed can be returned from the finish call if the
        // API determines that the booking could not be started at all.  Treat it as
        // unrecoverable here as well, although it is more commonly returned from the
        // status call.
        'booking_finish_did_not_succeed',

        // Errors indicating the finish or form call has been invoked multiple
        // times for the same order.  These are considered final and should
        // result in the client surfacing a user‑friendly error rather than
        // retrying or polling.  They do not indicate that the booking itself
        // failed; rather, the booking was already processed or is being
        // processed, so repeating the call again is a logic error.
        'double_booking_finish',
        'double_booking_form',
    ];

    /**
     * List of errors returned from the booking finish status call that are
     * considered terminal.  When one of these is encountered, we must stop
     * polling immediately.  This array mirrors the set documented by
     * RateHawk for the `/hotel/order/booking/finish/status/` endpoint and is
     * used by pollFinishStatus().  Note that `double_booking_finish` does not
     * appear here because that error is returned only from the finish call.
     *
     * @var string[]
     */
    private array $finalStatusErrors = [
        '3ds',
        'block',
        'book_limit',
        'booking_finish_did_not_succeed',
        'charge',
        'provider',
        'soldout',
        'not_allowed',
        'order_not_found',
        // Additional final errors that may appear across endpoints
        'decoding_json',
        'endpoint_exceeded_limit',
        'endpoint_not_active',
        'endpoint_not_found',
        'incorrect_credentials',
        'invalid_auth_header',
        'invalid_params',
        'no_auth_header',
        'not_allowed_host',
        'overdue_debt',
        'unexpected_method',
    ];

    /**
     * Global booking process timeout in seconds.  This defines the total time window
     * during which the booking form, finish and finish/status calls may
     * collectively execute.  By enforcing a single timeout across all
     * booking steps we avoid situations where the status polling stops too
     * early and a booking completes on the ETG side after our client has
     * given up, leading to "ghost bookings".  Adjust this value via
     * configuration or environment variables as needed.
     *
     * @var int
     */
    private int $bookingProcessTimeout = 300; // 5 minutes by default

    /**
     * Retrieve or initialize the booking deadline for a given partner_order_id.
     * The deadline is stored in the session under the key `booking_deadlines`.
     * If no deadline exists for the provided id, a new one is created by
     * adding $bookingProcessTimeout seconds to the current time.  Once the
     * booking completes or fails, the deadline entry should be cleared via
     * clearBookingDeadline().
     *
     * @param string $partnerOrderId  Unique partner order id for the booking
     * @return int Unix timestamp representing the deadline
     */
    private function getBookingDeadline(string $partnerOrderId): int
    {
        $map = session('booking_deadlines', []);
        if (!isset($map[$partnerOrderId])) {
            $map[$partnerOrderId] = time() + $this->bookingProcessTimeout;
            session(['booking_deadlines' => $map]);
        }
        return $map[$partnerOrderId];
    }

    /**
     * Compute the remaining time, in seconds, before the booking deadline is
     * reached for a specific partner_order_id.  Returns zero if the
     * deadline has passed.  Consumers should use this value to bound
     * polling intervals so that all booking steps collectively honour the
     * configured timeout window.
     *
     * @param string $partnerOrderId
     * @return int Remaining seconds until the deadline, or zero if expired
     */
    private function getRemainingBookingTime(string $partnerOrderId): int
    {
        $deadline = $this->getBookingDeadline($partnerOrderId);
        $remaining = $deadline - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Remove the booking deadline entry for a given partner_order_id.  Should
     * be invoked once a final status (either success or failure) has been
     * obtained from the finish/status endpoint.  Clearing the deadline
     * prevents stale entries from accumulating in the session.
     *
     * @param string $partnerOrderId
     * @return void
     */
    private function clearBookingDeadline(string $partnerOrderId): void
    {
        $map = session('booking_deadlines', []);
        if (isset($map[$partnerOrderId])) {
            unset($map[$partnerOrderId]);
            session(['booking_deadlines' => $map]);
        }
    }

    public function showHotels()
    {
        Log::info('Rendering hotel search form.');
        return view('Hotel::frontend.form-search-ha');
    }

    public function searchHotels(Request $request)
    {
        // Extend execution time for large dataset searches
        set_time_limit(120);

        try {
            // 1) Validate inputs, including children_count & per-child ages
            $request->validate([
                'hotel_name' => 'nullable|string',
                'location' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|integer|min:1',
                'checkin' => 'required|date',
                'checkout' => 'required|date|after:checkin',
                'rooms' => 'required|integer|min:1',
                'adults' => 'required|integer|min:1',
                'children_count' => 'required|integer|min:0|max:5',
                'children' => 'nullable|array',
                'children.*' => 'integer|min:0|max:17',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'star_rating' => 'nullable|array',
                'star_rating.*' => 'integer|between:1,5',
                'breakfast_included' => 'nullable|boolean',
                'chunk' => 'nullable|integer',
            ]);

            // 2) Build cache key including both count and ages
            $searchHash = md5(json_encode([
                $request->hotel_name,
                $request->location,
                $request->latitude,
                $request->longitude,
                $request->radius,
                $request->checkin,
                $request->checkout,
                $request->rooms,
                $request->adults,
                $request->children_count,
                $request->children,
            ]));

            // 3) Sanitize child ages (0–17)
            $childrenCount = (int) $request->input('children_count', 0);
            $rawAges = $request->input('children', []);
            $childAges = array_values(array_filter(
                array_map('intval', $rawAges),
                fn($age) => $age >= 0 && $age <= 17
            ));

            // Handle chunked loading for AJAX requests
            if ($request->ajax() && $request->has('chunk')) {
                return $this->loadHotelChunk($request, $searchHash, $childAges);
            }

            // 4) Get hotels from database immediately (no API calls)
            $hotelQuery = DB::table('hotels')
                ->select('hotel_id', 'name', 'latitude', 'longitude', 'star_rating', 'address');

            if ($request->filled('hotel_name')) {
                $hotelQuery->where('name', 'like', '%' . $request->hotel_name . '%');
            }
            if ($request->filled('star_rating')) {
                $hotelQuery->whereIn('star_rating', $request->star_rating);
            }
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $lat = $request->latitude;
                $lng = $request->longitude;
                $radius = $request->radius ?? 4;
                $hotelQuery->whereBetween('latitude', [
                    $lat - ($radius / 111),
                    $lat + ($radius / 111)
                ])->whereBetween('longitude', [
                            $lng - ($radius / (111 * cos(deg2rad($lat)))),
                            $lng + ($radius / (111 * cos(deg2rad($lat))))
                        ]);
            }

            $hotels = $hotelQuery->limit(10)->get(); // Show first 10 immediately

            // Attach images
            $hotelImages = DB::table('hotel_images')
                ->whereIn('hotel_id', $hotels->pluck('hotel_id'))
                ->groupBy('hotel_id')
                ->pluck('image_url', 'hotel_id');

            foreach ($hotels as $hotel) {
                $hotel->image_url = $hotelImages[$hotel->hotel_id] ?? asset('uploads/no_img.jpeg');
                $hotel->daily_price = null; // Will be updated via AJAX
                $hotel->has_breakfast = false;
                // Don't set is_available yet - will show loading state
            }


            // Cache search params for AJAX
            $cacheKey = "search_params_{$searchHash}";
            Cache::put($cacheKey, [
                'hotel_name' => $request->hotel_name,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius' => $request->radius,
                'star_rating' => $request->star_rating,
                'checkin' => $request->checkin,
                'checkout' => $request->checkout,
                'adults' => $request->adults,
                'rooms' => $request->rooms,
                'children_count' => $childrenCount,
                'children' => $childAges,
            ], now()->addMinutes(30));

            // Cache total count to avoid slow COUNT queries on large datasets
            $totalCountCacheKey = "hotel_count_{$searchHash}";
            $totalCount = Cache::remember($totalCountCacheKey, now()->addMinutes(30), function () use ($request) {
                return DB::table('hotels')
                    ->when($request->filled('hotel_name'), function ($q) use ($request) {
                        return $q->where('name', 'like', '%' . $request->hotel_name . '%');
                    })
                    ->when($request->filled('star_rating'), function ($q) use ($request) {
                        return $q->whereIn('star_rating', $request->star_rating);
                    })
                    ->when($request->filled('latitude') && $request->filled('longitude'), function ($q) use ($request) {
                        $lat = $request->latitude;
                        $lng = $request->longitude;
                        $radius = $request->radius ?? 4;
                        return $q->whereBetween('latitude', [
                            $lat - ($radius / 111),
                            $lat + ($radius / 111)
                        ])->whereBetween('longitude', [
                                    $lng - ($radius / (111 * cos(deg2rad($lat)))),
                                    $lng + ($radius / (111 * cos(deg2rad($lat))))
                                ]);
                    })
                    ->count();
            });

            // Return page with first 10 hotels immediately
            return view('Hotel::frontend.results-ha', [
                'hotels' => $hotels,
                'totalHotels' => $totalCount,
                'checkin' => $request->checkin,
                'checkout' => $request->checkout,
                'adults' => $request->adults,
                'children' => $childrenCount,
                'minPrice' => 0,
                'maxPrice' => 999,
                'searchHash' => $searchHash,
                'isLoading' => false, // Show hotels immediately
                'loadMore' => $totalCount > 10,
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching hotels', ['message' => $e->getMessage()]);
            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    private function loadHotelChunk(Request $request, $searchHash, $childAges)
    {
        // Extend execution time for chunk processing
        set_time_limit(60);

        $chunk = (int) $request->input('chunk', 0);
        $chunkSize = 10; // Process 10 hotels at a time
        $fetchPrices = $request->boolean('fetch_prices', true);

        try {
            // Get search params from cache
            $searchParams = Cache::get("search_params_{$searchHash}");
            if (!$searchParams) {
                return response()->json(['error' => 'Search expired'], 400);
            }

            // Build base query for counting and fetching
            $baseQuery = function () use ($searchParams) {
                $query = DB::table('hotels')
                    ->select('hotel_id', 'name', 'latitude', 'longitude', 'star_rating', 'address');

                if (!empty($searchParams['hotel_name'])) {
                    $query->where('name', 'like', '%' . $searchParams['hotel_name'] . '%');
                }
                if (!empty($searchParams['star_rating'])) {
                    $query->whereIn('star_rating', $searchParams['star_rating']);
                }
                if (!empty($searchParams['latitude']) && !empty($searchParams['longitude'])) {
                    $lat = $searchParams['latitude'];
                    $lng = $searchParams['longitude'];
                    $radius = $searchParams['radius'] ?? 4;
                    $query->whereBetween('latitude', [
                        $lat - ($radius / 111),
                        $lat + ($radius / 111)
                    ])->whereBetween('longitude', [
                                $lng - ($radius / (111 * cos(deg2rad($lat)))),
                                $lng + ($radius / (111 * cos(deg2rad($lat))))
                            ]);
                }

                return $query;
            };

            // Get total count (cached to avoid recounting on each chunk)
            $totalCountCacheKey = "hotel_count_{$searchHash}";
            $totalHotels = Cache::remember($totalCountCacheKey, now()->addMinutes(30), function () use ($baseQuery) {
                return $baseQuery()->count();
            });

            // Fetch only the current chunk from database
            $chunkHotels = $baseQuery()
                ->skip($chunk * $chunkSize)
                ->take($chunkSize)
                ->get();

            // Attach images for this chunk only
            if ($chunkHotels->isNotEmpty()) {
                $hotelImages = DB::table('hotel_images')
                    ->whereIn('hotel_id', $chunkHotels->pluck('hotel_id'))
                    ->groupBy('hotel_id')
                    ->pluck('image_url', 'hotel_id');

                foreach ($chunkHotels as $hotel) {
                    $hotel->image_url = $hotelImages[$hotel->hotel_id] ?? asset('images/default-image.jpg');
                    $hotel->daily_price = null;
                    $hotel->has_breakfast = false;
                }
            }

            // Get chunk of hotels
            $chunkHotels = collect($chunkHotels);

            if ($chunkHotels->isEmpty()) {
                return response()->json([
                    'html' => '',
                    'hasMore' => false,
                    'totalCount' => $totalHotels,
                    'loadedCount' => $totalHotels,
                    'hotels' => [], // Add empty hotels array for consistency
                ]);
            }

            // Fetch prices for this chunk from API (if requested)
            if ($fetchPrices) {
                $hotelIds = $chunkHotels->pluck('hotel_id')->toArray();

                $apiBody = [
                    'checkin' => $searchParams['checkin'],
                    'checkout' => $searchParams['checkout'],
                    'residency' => 'gb',
                    'language' => 'en',
                    'guests' => [
                        [
                            'adults' => (int) $searchParams['adults'],
                            'children' => $childAges,
                        ]
                    ],
                    'ids' => $hotelIds,
                    'currency' => $request->input('currency', 'EUR'),
                ];

                try {
                    $apiData = Http::timeout(20) // 20 seconds max - faster fallback to "No rooms available"
                        ->withOptions($this->httpOptions)
                        ->withBasicAuth($this->username, $this->password)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($this->apiUrl . 'search/serp/hotels', $apiBody)
                        ->json()['data']['hotels'] ?? [];

                    // Map prices & breakfast flags
                    $pricesResult = [];
                    foreach ($apiData as $apiHotel) {
                        $hid = $apiHotel['id'] ?? null;
                        if (!$hid)
                            continue;
                        $dailyPrice = $apiHotel['rates'][0]['daily_prices'][0] ?? null;
                        $hasBreakfast = collect($apiHotel['rates'] ?? [])
                            ->contains(function ($r) {
                                $mealValue = strtolower((string) data_get($r, 'meal', data_get($r, 'meal_data.value')));
                                return $mealValue !== '' && $mealValue !== 'nomeal' && Str::contains($mealValue, 'breakfast');
                            });

                        // Apply 15% markup for guest users
                        if ($dailyPrice && !auth()->check()) {
                            $dailyPrice = round($dailyPrice * 1.15, 2);
                        }

                        $pricesResult[$hid] = compact('dailyPrice', 'hasBreakfast');
                    }

                    foreach ($chunkHotels as $hotel) {
                        $hotel = (object) $hotel;
                        $res = $pricesResult[$hotel->hotel_id] ?? null;
                        $hotel->daily_price = $res['dailyPrice'] ?? null;
                        $hotel->has_breakfast = $res['hasBreakfast'] ?? false;
                        $hotel->is_available = $hotel->daily_price !== null; // Mark availability
                    }
                } catch (\Exception $e) {
                    Log::warning('API timeout for chunk ' . $chunk . ', showing hotels without prices', ['error' => $e->getMessage()]);
                    // Mark all hotels as unavailable if API fails
                    foreach ($chunkHotels as $hotel) {
                        $hotel = (object) $hotel;
                        if (!isset($hotel->is_available)) {
                            $hotel->is_available = false;
                        }
                    }
                }
            } else {
                // If not fetching prices, mark all as unavailable
                foreach ($chunkHotels as $hotel) {
                    $hotel = (object) $hotel;
                    $hotel->is_available = false;
                }
            }

            // Apply filters first (only if we have prices, or if filter doesn't require price)
            $filtered = $chunkHotels->filter(function ($h) use ($request) {
                $hotel = (object) $h;

                // Skip price filters if we don't have price data yet
                if ($request->filled('min_price') && $hotel->daily_price !== null && $hotel->daily_price < $request->min_price) {
                    return false;
                }
                if ($request->filled('max_price') && $hotel->daily_price !== null && $hotel->daily_price > $request->max_price) {
                    return false;
                }
                if ($request->boolean('breakfast_included') && !$hotel->has_breakfast) {
                    return false;
                }
                return true;
            });

            // Sort hotels: available ones (with prices) first
            $filtered = $filtered->sortByDesc(function ($h) {
                $hotel = (object) $h;
                return $hotel->is_available ?? false;
            })->values();

            // Generate HTML
            $html = '';
            foreach ($filtered as $hotelData) {
                $hotel = (object) $hotelData;
                $query = array_merge(
                    ['id' => $hotel->hotel_id],
                    $request->only(['checkin', 'checkout', 'adults', 'rooms', 'latitude', 'longitude', 'currency']),
                    ['children_count' => $searchParams['children_count']],
                    ['children' => $searchParams['children']]
                );

                $currencySym = match ($request->input('currency', 'EUR')) {
                    'USD' => '$',
                    'GBP' => '£',
                    'EUR' => '€',
                    default => '€',
                };

                $html .= view('Hotel::frontend.partials.hotel-card-chunk', compact('hotel', 'query', 'currencySym'))->render();
            }

            $loadedCount = ($chunk + 1) * $chunkSize;
            $hasMore = $loadedCount < $totalHotels;

            return response()->json([
                'html' => $html,
                'hasMore' => $hasMore,
                'totalCount' => $totalHotels,
                'loadedCount' => min($loadedCount, $totalHotels),
                'hotels' => $filtered->toArray(), // Add hotel objects for map markers
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading hotel chunk', ['message' => $e->getMessage(), 'chunk' => $chunk]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function fetchApiData($checkin, $checkout, $adults, $children, $hotelIds, $hotelHids)
    {
        Log::info('Preparing API request...');
        $url = $this->apiUrl . "search/serp/hotels/";

        $body = [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'residency' => 'gb',
            'language' => 'en',
            'guests' => [
                [
                    'adults' => $adults, // Ensure it's an integer
                    'children' => $children,
                ],
            ],
            'ids' => $hotelIds,
            'hid' => $hotelHids,
            'currency' => 'EUR',
        ];

        Log::info('API Request Body', ['body' => $body]);

        try {
            $response = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $body);

            Log::info('API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return $response->json()['data']['hotels'] ?? [];
            } else {
                Log::error('Hotel API Request Failed', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error during hotel API request', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }


    // public function hotelInfo($id, Request $request)
    // {
    //     Log::info('Fetching detailed info for hotel', ['hotel_id' => $id]);

    //     try {
    //         $checkin = $request->query('checkin', now()->format('Y-m-d'));
    //         $checkout = $request->query('checkout', now()->addDay()->format('Y-m-d'));
    //         $residency = $request->query('residency', 'gb');
    //         $language = $request->query('language', 'en');
    //         $currency = $request->query('currency', 'EUR');
    //         $adults = (int) $request->query('adults', 1);

    //         // Handle children properly
    //         $childrenParam = $request->query('children', []);
    //         if (is_string($childrenParam)) {
    //             $children = array_filter(array_map('intval', explode(',', $childrenParam)));
    //         } elseif (is_array($childrenParam)) {
    //             $children = array_map('intval', $childrenParam);
    //         } else {
    //             $children = [];
    //         }

    //         // Fetch hotel from DB
    //         $dbHotel = DB::table('hotels')->where('hotel_id', $id)->first();
    //         if (!$dbHotel) {
    //             Log::error('Hotel not found in DB', ['hotel_id' => $id]);
    //             return redirect()->back()->withErrors(['error' => 'Hotel not found']);
    //         }

    //         $hotelImages = DB::table('hotel_images')
    //             ->where('hotel_id', $id)
    //             ->pluck('image_url')
    //             ->toArray();

    //         // Build API request
    //         $apiBody = [
    //             'checkin' => $checkin,
    //             'checkout' => $checkout,
    //             'residency' => $residency,
    //             'language' => $language,
    //             'currency' => $currency,
    //             'id' => $id,
    //             'guests' => [[
    //                 'adults' => $adults,
    //                 'children' => $children,
    //             ]]
    //         ];

    //         $response = Http::withBasicAuth($this->username, $this->password)
    //             ->withHeaders(['Content-Type' => 'application/json'])
    //             ->post($this->apiUrl . 'search/hp/', $apiBody);

    //         if ($response->failed()) {
    //             Log::error('API call failed', ['response' => $response->body()]);
    //             throw new \Exception('Failed to fetch hotel details');
    //         }

    //         $apiData = $response->json()['data']['hotels'][0] ?? null;
    //         if (!$apiData) {
    //             return redirect()->back()->withErrors(['error' => 'Hotel not found in API']);
    //         }

    //         $hotel = [
    //             'id' => $apiData['id'] ?? $dbHotel->hotel_id,
    //             'name' => $dbHotel->name ?? $apiData['name'] ?? 'N/A',
    //             'address' => $dbHotel->address ?? $apiData['address'] ?? 'N/A',
    //             'star_rating' => $dbHotel->star_rating ?? $apiData['star_rating'] ?? 0,
    //             'check_in_time' => $apiData['check_in_time'] ?? 'N/A',
    //             'check_out_time' => $apiData['check_out_time'] ?? 'N/A',
    //             'images_ext' => $hotelImages,
    //             'price_per_night' => $apiData['rates'][0]['daily_prices'][0] ?? 'N/A',
    //         ];

    //         $roomRates = $apiData['rates'] ?? [];
    //         $hasRoomRates = count($roomRates) > 0;

    //         return view('Hotel::frontend.info-ha', compact(
    //             'hotel', 'roomRates', 'hasRoomRates',
    //             'checkin', 'checkout', 'residency', 'language',
    //             'adults', 'children', 'currency'
    //         ));

    //     } catch (\Exception $e) {
    //         Log::error('Error fetching hotel info', [
    //             'hotel_id' => $id,
    //             'error' => $e->getMessage(),
    //         ]);
    //         return redirect()->back()->withErrors(['error' => 'Could not load hotel information.']);
    //     }
    // }

    public function hotelInfo($id, Request $request)
    {
        Log::info('Fetching detailed info for hotel', ['hotel_id' => $id]);

        try {
            // 1. Parse parameters
            $checkin = $request->query('checkin', now()->format('Y-m-d'));
            $checkout = $request->query('checkout', now()->addDay()->format('Y-m-d'));
            $residency = $request->query('residency', 'gb');
            $language = $request->query('language', 'en');
            $currency = $request->query('currency', 'EUR');
            $adults = (int) $request->query('adults', 1);
            $children = collect($request->query('children', []))
                ->filter(fn($age) => is_numeric($age) && $age >= 0 && $age <= 17)
                ->map(fn($age) => (int) $age)
                ->values()
                ->toArray();

            // 2. Lookup hotel in DB
            $dbHotel = DB::table('hotels')->where('hotel_id', $id)->first();
            if (!$dbHotel || empty($dbHotel->hid)) {
                Log::error('Hotel not found or missing HID', ['hotel_id' => $id]);
                return redirect()->back()->withErrors(['Hotel not found']);
            }

            $hotelImages = DB::table('hotel_images')
                ->where('hotel_id', $id)
                ->pluck('image_url')
                ->toArray();

            $metapolicyStruct = $dbHotel->metapolicy_struct
                ? json_decode($dbHotel->metapolicy_struct, true)
                : [];

            // 3. API call: hotel rates
            $rateResponse = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'search/hp/', [
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'residency' => $residency,
                    'language' => $language,
                    'currency' => $currency,
                    'id' => $id,
                    'guests' => [['adults' => $adults, 'children' => $children]],
                ]);

            if ($rateResponse->failed()) {
                Log::error('Rate API failed', ['body' => $rateResponse->body()]);
                throw new \Exception('Failed to fetch hotel rates');
            }

            $hotelRateData = $rateResponse->json()['data']['hotels'][0] ?? null;

            // Get metapolicy_extra_info from API if available
            $metapolicyExtraInfo = $hotelRateData['metapolicy_extra_info']
                ?? $dbHotel->metapolicy_extra_info
                ?? '';

            // 4. API call: room images (via hotel info)
            // 4. API call: hotel info (using HID)
            $infoResponse = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/info/', [
                    'hid' => (int) $dbHotel->hid,
                    'language' => $language,
                ]);

            $roomImageMap = [];
            $metapolicyStruct = [];
            $metapolicyExtraInfo = '';

            if ($infoResponse->ok() && isset($infoResponse['data'])) {
                $infoData = $infoResponse['data'];

                // ✅ Extract metapolicy fields from API
                $metapolicyStruct = $infoData['metapolicy_struct'] ?? [];
                $metapolicyExtraInfo = $infoData['metapolicy_extra_info'] ?? '';

                // ✅ Map room images
                if (isset($infoData['room_groups'])) {
                    foreach ($infoData['room_groups'] as $group) {
                        $roomNameRaw = $group['name'] ?? null;
                        $roomNameKey = $this->normalizeRoomName($roomNameRaw);

                        $images = collect($group['images_ext'] ?? [])
                            ->pluck('url')
                            ->filter()
                            ->map(fn($u) => str_replace('{size}', '1080x1920', $u))
                            ->values()
                            ->toArray();

                        $roomImageMap[$roomNameKey] = $images;

                        Log::info('Mapped room group images', [
                            'room_name_key' => $roomNameKey,
                            'image_count' => count($images),
                            'first_image' => $images[0] ?? 'N/A',
                        ]);
                    }
                }
            } else {
                Log::warning('Empty or invalid hotel info API response', ['hotel_id' => $id]);
            }

            // 5. Map room rates + attach images
            // When mapping each rate we also compute the user‑visible tax amounts.  The API
            // often supplies tax amounts as per‑night or per‑guest values via the `amount`
            // field.  When `amount_show` or `amount_charge` is present we use it directly,
            // otherwise we multiply by the number of guests and nights.  We also expose
            // these computed taxes on the rate under the key `display_taxes` so the view
            // can use them without duplicating the logic.
            $roomRates = collect($hotelRateData['rates'] ?? [])->map(function ($rate) use ($roomImageMap, $checkin, $checkout, $adults, $children) {
                // Extract pricing information
                $payment = $rate['payment_options']['payment_types'][0] ?? [];
                $net = data_get($payment, 'commission_info.charge.amount_net', 0);
                $commission = data_get($payment, 'commission_info.charge.amount_commission', 0);

                // Determine human‑readable meal label based on the `meal` or `meal_data.value` field
                $rawMeal = strtolower((string) data_get($rate, 'meal', data_get($rate, 'meal_data.value')));
                if (empty($rawMeal) || $rawMeal === 'nomeal') {
                    $mealLabel = 'No meals';
                } else {
                    // Replace underscores with spaces and capitalize each word
                    $mealLabel = ucwords(str_replace('_', ' ', $rawMeal));
                }

                // Ensure the room has a valid name
                $roomNameRaw = $rate['name'] ?? $rate['room_name'] ?? null;
                if (empty($roomNameRaw)) {
                    Log::warning('Rate missing room name', ['rate' => $rate]);
                    $rate['room_images'] = [];
                    // Still attach basic pricing and meal info so UI remains consistent
                    $rate['net_amount'] = $net;
                    $rate['commission_amount'] = $commission;
                    $finalPrice = round($net + $commission, 2);

                    // Apply 15% markup for guest users
                    if (!auth()->check()) {
                        $finalPrice = round($finalPrice * 1.15, 2);
                    }

                    $rate['final_price'] = $finalPrice;
                    $rate['meal_type'] = $mealLabel;
                    return $rate;
                }

                // Match room images using normalized room name
                $roomNameKey = $this->normalizeRoomName($roomNameRaw);
                $roomImages = $roomImageMap[$roomNameKey] ?? $this->findClosestImageMatch($roomNameKey, $roomImageMap);
                if (empty($roomImages)) {
                    Log::debug('No images matched for room', ['room_name_key' => $roomNameKey]);
                }

                // Attach computed fields back to the rate
                $rate['net_amount'] = $net;
                $rate['commission_amount'] = $commission;
                $finalPrice = round($net + $commission, 2);

                // Apply 15% markup for guest users
                if (!auth()->check()) {
                    $finalPrice = round($finalPrice * 1.15, 2);
                }

                $rate['final_price'] = $finalPrice;
                $rate['meal_type'] = $mealLabel;
                // Optionally derive a simple meal code based on the first letters of each word
                $rate['meal_code'] = preg_match_all('/\b(\w)/u', $mealLabel, $m) ? strtoupper(implode('', $m[1])) : null;
                $rate['room_images'] = $roomImages;

                // Compute display taxes for this rate.  Use amount_show or amount_charge
                // when provided; otherwise multiply the per‑person per‑night amount by
                // number of nights and guests.  Attach the results to the rate so the
                // view can use them.
                $taxes = data_get($payment, 'tax_data.taxes', []);
                $displayTaxes = [];
                foreach ($taxes as $tax) {
                    $name = $tax['name'] ?? '';
                    $currencyCode = $tax['currency_code'] ?? '';
                    // Prefer the total displayable amount fields when available
                    $amount = $tax['amount_show']
                        ?? ($tax['amount_charge'] ?? ($tax['amount'] ?? 0));
                    $displayTaxes[] = [
                        'name' => $name,
                        'amount' => (float) $amount,
                        'currency_code' => $currencyCode,
                        'included_by_supplier' => $tax['included_by_supplier'] ?? false,
                    ];
                }
                $rate['display_taxes'] = $displayTaxes;

                Log::info('Processing rate', [
                    'room_name_key' => $roomNameKey,
                    'has_images' => !empty($roomImages),
                    'image_count' => count($roomImages),
                    'first_image' => $roomImages[0] ?? 'N/A',
                ]);

                return $rate;
            })->toArray();

            $hotel = [
                'id' => $hotelRateData['id'] ?? $dbHotel->hotel_id,
                'name' => $dbHotel->name ?? $hotelRateData['name'] ?? 'N/A',
                'address' => $dbHotel->address ?? $hotelRateData['address'] ?? 'N/A',
                'star_rating' => $dbHotel->star_rating ?? $hotelRateData['star_rating'] ?? 0,
                'images_ext' => $hotelImages,
                'metapolicy_struct' => $metapolicyStruct,
                'metapolicy_extra_info' => $metapolicyExtraInfo,
            ];


            return view('Hotel::frontend.info-ha', compact(
                'hotel',
                'roomRates',
                'checkin',
                'checkout',
                'adults',
                'children',
                'currency'
            ));

        } catch (\Exception $e) {
            Log::error('Error fetching hotel info', [
                'hotel_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors(['error' => 'Could not load hotel information.']);
        }
    }


    private function findClosestImageMatch(string $roomNameKey, array $imageMap): array
    {
        $roomWords = explode(' ', $roomNameKey);
        $bestMatch = [];
        $maxOverlap = 0;

        foreach ($imageMap as $key => $images) {
            $keyWords = explode(' ', $key);
            $overlap = count(array_intersect($roomWords, $keyWords));

            if ($overlap > $maxOverlap && !empty($images)) {
                $maxOverlap = $overlap;
                $bestMatch = $images;
            }
        }

        // Optional: return only if there are at least 2 common words
        return $maxOverlap >= 2 ? $bestMatch : [];
    }



    private function normalizeRoomName($name)
    {
        // Remove all text in parentheses (including multiple sets)
        $name = preg_replace('/\([^)]*\)/', '', $name);
        return trim(strtolower(preg_replace('/\s+/', ' ', $name))); // also normalize whitespace
    }

    /**
     * Polls the RateHawk finish status endpoint until a final status or final error
     * is returned.  This method implements the ETG recommendation to continue
     * requesting `/hotel/order/booking/finish/status/` when you receive
     * `timeout`, `unknown` or 5xx errors from either the finish call or the status call.
     *
     * @param string $partnerOrderId  The partner_order_id to check
     * @param int    $timeout         Total number of seconds to keep polling (default 300).  In line
     *                                with RateHawk guidance, this timeout is longer than the 60 seconds
     *                                previously used so that temporary errors like `timeout` or
     *                                `unknown` can be retried until the general booking timeout
     *                                elapses.
     * @param int    $interval        Delay in seconds between polls (default 5)
     * @return array                  The JSON payload returned from the last status call
     */
    private function pollFinishStatus(string $partnerOrderId, int $timeout = 300, int $interval = 5): array
    {
        /**
         * Polls the finish/status endpoint until either a final status is returned
         * or the timeout is reached.  The timeout is implicitly bounded by the
         * remaining booking window configured via $bookingProcessTimeout.  If
         * the remaining time for this booking is shorter than the provided
         * $timeout, the smaller value is used so that the entire booking flow
         * respects the agreed time limit.
         */

        // Adjust timeout based on the remaining booking window
        $remaining = $this->getRemainingBookingTime($partnerOrderId);
        if ($remaining > 0) {
            $timeout = min($timeout, $remaining);
        }
        // If no time remains, return immediately with a timeout error
        if ($timeout <= 0) {
            return ['status' => 'error', 'error' => 'timeout'];
        }

        $startTime = time();
        $lastResponse = ['status' => null, 'error' => null];

        // Keep polling until we hit a terminal condition or time runs out.  We
        // consider a terminal condition to be a successful status (status === 'ok')
        // or the error field containing one of the final status errors.
        do {
            try {
                $resp = Http::withOptions($this->httpOptions)
                    ->withBasicAuth($this->username, $this->password)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(30)
                    ->post($this->apiUrl . 'hotel/order/booking/finish/status/', [
                        'partner_order_id' => $partnerOrderId,
                    ]);

                // Successful HTTP response
                if ($resp->successful()) {
                    $json = $resp->json();
                    $lastResponse = $json;
                    $status = data_get($json, 'status');
                    $error = data_get($json, 'error');

                    // When the booking has completed successfully (status = 'ok') or
                    // when a final error appears, break out of the loop.
                    if ($status === 'ok' || ($error && in_array($error, $this->finalStatusErrors, true))) {
                        break;
                    }
                } else {
                    // Non-200 response: treat 5xx as temporary errors.  Record
                    // status code so the caller can inspect it.  Only break
                    // immediately on 4xx because these are usually contract errors.
                    $statusCode = $resp->status();
                    $lastResponse = [
                        'status' => 'error',
                        'error' => $statusCode,
                    ];
                    if ($statusCode >= 400 && $statusCode < 500) {
                        // 4xx errors are not recoverable by polling
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Network or other exception; treat as temporary and continue.
                $lastResponse = [
                    'status' => 'error',
                    'error' => 'unknown',
                ];
            }

            // Sleep before the next poll.  Use PHP's sleep() to avoid busy-waiting.
            sleep($interval);
        } while ((time() - $startTime) < $timeout);

        return $lastResponse;
    }


    public function getHotelSuggestions(Request $request)
    {
        $query = $request->input('query');
        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $hotels = HotelH::where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['name']);

        return response()->json($hotels);
    }

    public function prebookRoom(Request $request)
    {
        try {
            $validated = $request->validate([
                'book_hash' => 'required|string',
                'room_name' => 'required|string',
                'children' => 'sometimes',
            ]);

            $bookHash = $validated['book_hash'];
            $priceIncreasePercent = (int) $request->input('price_increase_percent', 20);
            $roomName = $validated['room_name'];

            $apiBody = [
                'hash' => $bookHash,
                'price_increase_percent' => $priceIncreasePercent,
            ];

            $response = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/prebook/', $apiBody);

            if ($response->failed()) {
                throw new \Exception('Prebook API request failed: ' . $response->body());
            }

            $prebookData = $response->json();
            $rawChildren = $request->input('children', []);
            $children = is_string($rawChildren)
                ? (json_decode($rawChildren, true) ?: [])
                : (array) $rawChildren;

            $displayFinalPrice = (float) $request->input('display_final_price', 0);
            $displayCurrency = $request->input('display_currency', 'EUR');

            session([
                'prebookData' => $prebookData,
                'roomName' => $roomName,
                'checkin' => $request->checkin,
                'checkout' => $request->checkout,
                'adults' => (int) $request->input('adults', 1),
                'children' => $children,
                'display_final_price' => $displayFinalPrice,
                'display_currency' => $displayCurrency,
            ]);

            return redirect()->route('hotel.prebook.result');
        } catch (\Exception $e) {
            Log::error('Prebook failed', ['error' => $e->getMessage()]);

            return back()->withErrors([
                'error' => 'Prebooking failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function prebookResult()
    {
        $prebookData = session('prebookData');
        $roomName = session('roomName');
        $checkin = session('checkin', now()->format('Y-m-d'));
        $checkout = session('checkout', now()->addDay()->format('Y-m-d'));
        $adults = session('adults', 1);
        $children = session('children', []);

        $oldPrice = (float) session('display_final_price', 0.0);
        $oldCurrency = session('display_currency', '');

        if (!$prebookData) {
            return redirect()->route('hotel.info')->withErrors(['error' => 'No prebooking data found.']);
        }

        $hotelData = $prebookData['data']['hotels'][0] ?? null;
        $rate = $hotelData['rates'][0] ?? null;
        $hotelId = $hotelData['id'] ?? null;

        if (!$hotelId) {
            return redirect()->route('hotel.search')->withErrors(['error' => 'Hotel ID is missing from prebooking data.']);
        }

        $hotel = DB::table('hotels')->where('hotel_id', $hotelId)->first();
        $hotelImage = DB::table('hotel_images')
            ->where('hotel_id', $hotelId)
            ->orderBy('id')
            ->value('image_url');

        $hotelDetails = [
            'id' => $hotelId,
            'name' => $hotel->name ?? $hotelData['name'] ?? 'Hotel Name Not Available',
            'address' => $hotel->address ?? $hotelData['address'] ?? 'Location Not Available',
            'star_rating' => $hotel->star_rating ?? $hotelData['star_rating'] ?? 0,
        ];

        $newPrice = null;
        $newCurrency = '';
        $priceChanged = false;
        $displayFinalPrice = 0;

        if ($rate) {
            $payment = $rate['payment_options']['payment_types'][0] ?? [];
            $net = data_get($payment, 'commission_info.charge.amount_net', $payment['amount'] ?? 0);
            $commission = data_get($payment, 'commission_info.charge.amount_commission', 0);
            $newPrice = (float) $net + (float) $commission;
            $newCurrency = $payment['currency_code'] ?? '';

            // Apply 15% markup for guest users (what customer will pay)
            $displayFinalPrice = $newPrice;
            if (!auth()->check()) {
                $displayFinalPrice = round($newPrice * 1.15, 2);
            }

            if ($oldPrice > 0 && abs($displayFinalPrice - $oldPrice) > 0.01) {
                $priceChanged = true;
            }
        }

        return view('Hotel::frontend.prebook-result-ha', compact(
            'prebookData',
            'roomName',
            'checkin',
            'checkout',
            'hotelDetails',
            'hotelImage',
            'priceChanged',
            'oldPrice',
            'newPrice',
            'displayFinalPrice',
            'oldCurrency',
            'newCurrency'
        ));
    }


    // public function bookRoom(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'book_hash' => 'required|string',
    //             'partner_order_id' => 'required|string',
    //             'user_ip' => 'required|ip',
    //             'hotel_id' => 'required|string',
    //             'checkin' => 'required|date',
    //             'checkout' => 'required|date',
    //             'meal_plan' => 'nullable|string',
    //         ]);

    //         $bookHash = $request->input('book_hash');
    //         $partnerOrderId = $request->input('partner_order_id');
    //         $userIp = $request->input('user_ip');

    //         // Save booking data in session
    //         session([
    //             'booking.partner_order_id' => $partnerOrderId,
    //             'booking.book_hash' => $bookHash,
    //             'booking.user_ip' => $userIp,
    //             'booking.hotel_id' => $request->input('hotel_id'),
    //             'booking.checkin' => $request->input('checkin'),
    //             'booking.checkout' => $request->input('checkout'),
    //             'booking.meal_plan' => $request->input('meal_plan'),
    //             'booking.adults' => $request->input('adults', 1),
    //             'booking.children' => json_decode($request->input('children', '[]'), true),
    //         ]);

    //         $apiBody = [
    //             'partner_order_id' => $partnerOrderId,
    //             'book_hash' => $bookHash,
    //             'language' => 'en',
    //             'user_ip' => $userIp,
    //         ];

    //         Log::info('📤 Sending booking form request to API', ['payload' => $apiBody]);

    //         $response = Http::withBasicAuth($this->username, $this->password)
    //             ->withHeaders(['Content-Type' => 'application/json'])
    //             ->post($this->apiUrl . 'hotel/order/booking/form/', $apiBody);

    //         Log::info('📥 Booking API Response', [
    //             'status' => $response->status(),
    //             'body' => $response->body(),
    //         ]);

    //         if ($response->failed()) {
    //             throw new \Exception('Booking API request failed: ' . $response->body());
    //         }

    //         $bookingData = $response->json();

    //         if (isset($bookingData['status']) && $bookingData['status'] === 'ok') {
    //             $data = $bookingData['data'];
    //             session(['bookingData' => $data]);

    //             // 🔥 Store with order_id in cache for PCB
    //             $token = Str::random(32);
    //             Cache::put("pending_booking_{$token}", array_merge($request->all(), [
    //                 'order_id' => $data['order_id'],
    //             ]), now()->addMinutes(10));

    //             return redirect()->route('hotel.booking.confirmation', ['book_hash' => $bookHash]);
    //         } else {
    //             throw new \Exception('Booking failed: ' . ($bookingData['error'] ?? 'Unknown error'));
    //         }

    //     } catch (\Exception $e) {
    //         Log::error('❌ Booking failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return redirect()->back()->withErrors(['error' => 'Booking failed: ' . $e->getMessage()]);
    //     }
    // }

    public function bookRoom(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'book_hash' => 'required|string',
                'partner_order_id' => 'required|string',
                'user_ip' => 'required|ip',
                'hotel_id' => 'required|string',
                'checkin' => 'required|date',
                'checkout' => 'required|date',
                'meal_plan' => 'nullable|string',
            ]);

            $bookHash = $request->input('book_hash');
            $partnerOrderId = $request->input('partner_order_id');
            $userIp = $request->input('user_ip');

            // Persist booking basics in session for later steps
            session([
                'booking.partner_order_id' => $partnerOrderId,
                'booking.book_hash' => $bookHash,
                'booking.user_ip' => $userIp,
                'booking.hotel_id' => $request->input('hotel_id'),
                'booking.checkin' => $request->input('checkin'),
                'booking.checkout' => $request->input('checkout'),
                'booking.meal_plan' => $request->input('meal_plan'),
                'booking.adults' => $request->input('adults', 1),
                'booking.children' => json_decode($request->input('children', '[]'), true),
                // Store display price (with 15% markup for guests) from prebook
                'display_final_price' => $request->input('display_final_price'),
                'display_currency' => $request->input('display_currency', 'EUR'),
            ]);

            // Initialize the booking deadline for this order so that all subsequent
            // steps (form, finish, status) share a single timeout window.
            $this->getBookingDeadline($partnerOrderId);

            $apiBody = [
                'partner_order_id' => $partnerOrderId,
                'book_hash' => $bookHash,
                'language' => 'en',
                'user_ip' => $userIp,
            ];

            Log::info('📤 Sending booking form request to API', ['payload' => $apiBody]);

            $response = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/form/', $apiBody);

            Log::info('📥 Booking API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // If HTTP request fails (4xx/5xx), throw exception
            if ($response->failed()) {
                throw new \Exception('Booking API request failed: ' . $response->body());
            }

            $json = $response->json();
            $status = data_get($json, 'status');
            $error = data_get($json, 'error');
            $httpStatus = $response->status();

            // If the form call succeeded, store bookingData and go to confirmation
            if ($status === 'ok') {
                $data = $json['data'] ?? [];
                // Extract all taxes that are not included by the supplier and
                // attach them to the booking data so the frontend can display
                // every applicable tax to the user.  According to the ETG API
                // documentation, the `tax_data` field of the search step
                // contains all taxes and fees that must be shown【788235750464074†L456-L470】.
                if (isset($data['tax_data']['taxes']) && is_array($data['tax_data']['taxes'])) {
                    $notIncludedTaxes = [];
                    foreach ($data['tax_data']['taxes'] as $tax) {
                        // The `included_by_supplier` flag indicates whether
                        // RateHawk already includes the tax in the price.  We
                        // collect all taxes where this flag is false.
                        if (empty($tax['included_by_supplier'])) {
                            $notIncludedTaxes[] = $tax;
                        }
                    }
                    $data['not_included_taxes'] = $notIncludedTaxes;
                }
                session(['bookingData' => $data]);

                // NO CACHE: Store directly in session for immediate access
                // $token = Str::random(32);
                // Cache::put("pending_booking_{$token}", array_merge($request->all(), [
                //     'order_id' => $data['order_id'],
                // ]), now()->addMinutes(10));

                return redirect()->route('hotel.booking.confirmation', ['book_hash' => $bookHash]);
            }

            // Otherwise, handle the error from the booking form call
            // Some errors are final; others (unknown/timeout or server errors) should trigger polling
            // of the finish/status endpoint to see if the booking was created anyway.

            // 1. Final error: double_booking_form (booking already submitted)
            if ($error === 'double_booking_form') {
                return redirect()->route('hotel.booking.failed')->withErrors([
                    'error' => __('This booking request has already been submitted or processed. Please try with a new request.'),
                ]);
            }

            // 2. Sandbox restriction error
            if ($error === 'sandbox_restriction') {
                return redirect()->route('hotel.booking.failed')->withErrors([
                    'error' => __('Booking failed: Your RateHawk API credentials are for testing/sandbox only. Please contact support to upgrade to production credentials to make real bookings.'),
                ]);
            }

            // 3. Temporary errors: unknown, timeout or HTTP 5xx
            $shouldPoll = false;
            if ($error && in_array($error, ['unknown', 'timeout'], true)) {
                Log::warning('⏳ Booking form returned temporary error; will poll finish/status', ['error' => $error]);
                $shouldPoll = true;
            } elseif ($httpStatus >= 500) {
                Log::warning('⏳ Booking form returned server error; will poll finish/status', ['status' => $httpStatus]);
                $shouldPoll = true;
            } else {
                // Any other error from form call is treated as final and surfaced to user
                throw new \Exception('Booking failed: ' . ($error ?? 'Unknown error'));
            }

            // 3. Poll finish/status if required
            if ($shouldPoll) {
                // Poll using the same finish/status endpoint.  Even though the finish call
                // has not yet been sent, RateHawk may still return a status for the order
                // if the booking has been created internally.  Respect the remaining
                // booking window so as not to exceed the global timeout.
                $remainingTime = $this->getRemainingBookingTime($partnerOrderId);
                $statusData = $this->pollFinishStatus($partnerOrderId, $remainingTime);
                Log::info('bookRoom finish/status response', ['status' => $statusData]);

                if (data_get($statusData, 'status') === 'ok') {
                    // Note: do not clear the booking deadline here; final success
                    // is still determined during the finish/finish status calls.
                    // If status returns ok, treat as successful booking and go to confirmation
                    return redirect()->route('hotel.booking.confirmation', ['book_hash' => $bookHash]);
                }

                // Otherwise, the booking did not complete.  Present failure message.
                return redirect()->route('hotel.booking.failed')->withErrors([
                    'error' => __('Booking failed after retrying. Please try again.'),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Booking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }



    public function bookingConfirmation($book_hash)
    {
        $bookingData = session('bookingData');
        $vendors = User::where('role_id', 2)->get();

        if (!$bookingData) {
            Log::warning('No booking data found in session during confirmation.');
            return redirect()->route('hotel.show')->withErrors(['error' => 'No booking data found.']);
        }

        Log::info('Displaying booking confirmation', ['bookingData' => $bookingData]);

        // Get display final price (with 15% markup for guests) from session
        $displayFinalPrice = session('display_final_price', $bookingData['payment_types'][0]['amount'] ?? 0);
        $displayCurrency = session('display_currency', $bookingData['payment_types'][0]['currency_code'] ?? 'EUR');

        // Pass $book_hash and display prices so we can display them in the Blade
        return view('Hotel::frontend.booking-confirmation-ha', compact('bookingData', 'vendors', 'book_hash', 'displayFinalPrice', 'displayCurrency'));
    }


    public function processPayment(Request $request)
    {
        // 1) Basic validation
        $request->validate([
            'order_id' => 'required|string',
            'partner_order_id' => 'required|string',
            'payment_method' => 'required|integer|min:0',
            'guests' => 'required|array|min:1',
            'guests.*.first_name' => 'required|string',
            'guests.*.last_name' => 'required|string',
        ]);

        try {
            $orderId = $request->input('order_id');
            $partnerOrderId = $request->input('partner_order_id');

            // Ensure a booking deadline exists for this partner order id so that
            // all subsequent polling respects a single timeout window across
            // booking finish and status calls.  If the deadline is already
            // present from the form step, this call will return the existing
            // value; otherwise it will initialise one.
            $this->getBookingDeadline($partnerOrderId);
            $pmIndex = $request->input('payment_method');
            $guests = $request->input('guests');

            $bookingData = session('bookingData');
            if (!$bookingData) {
                throw new \Exception('Session bookingData missing');
            }

            $paymentTypes = data_get($bookingData, 'payment_types', []);
            if (!isset($paymentTypes[$pmIndex])) {
                throw new \Exception('Invalid payment method index');
            }
            $selectedPayment = $paymentTypes[$pmIndex];

            // 2) If CC data is needed, validate
            if (!empty($selectedPayment['is_need_credit_card_data'])) {
                $request->validate([
                    'card_number' => 'required|digits:16',
                    'card_holder' => 'required|string',
                    'expiry_month' => 'required|digits:2',
                    'expiry_year' => 'required|digits:2',
                    'cvc' => 'required|digits_between:3,4',
                ]);
                $creditCardData = [
                    'card_number' => $request->input('card_number'),
                    'card_holder' => $request->input('card_holder'),
                    'expiry_month' => $request->input('expiry_month'),
                    'expiry_year' => $request->input('expiry_year'),
                    'cvc' => $request->input('cvc'),
                ];
            }

            $rooms = session('rooms', []);
            if (empty($rooms)) {
                throw new \Exception('Rooms data missing from session');
            }

            // 3) Build payload
            $apiBody = [
                'partner_order_id' => $partnerOrderId,
                'order_id' => $orderId,
                'payment_type' => [
                    'type' => $selectedPayment['type'],
                    'amount' => $selectedPayment['amount'],
                    'currency_code' => $selectedPayment['currency_code'],
                ],
                'language' => 'en',
                'rooms' => $rooms,
                'guests' => $guests,
                'credit_card_data' => $creditCardData ?? null,
            ];

            Log::info('Sending payment payload', ['payload' => $apiBody]);

            // Send the booking finish request to RateHawk.  Do not assume that a
            // successful HTTP response from this endpoint means the booking
            // itself has completed.  According to the ETG documentation, the
            // final status of a booking can only be obtained by polling the
            // `/hotel/order/booking/finish/status/` endpoint until a terminal
            // status is returned【788235750464074†L491-L501】.  See finishBooking()
            // for an example of the recommended logic.
            $resp = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/finish/', $apiBody);

            // Attempt to decode the JSON response.  Even if the HTTP status is
            // not 2xx, we still want to inspect the status and error fields.
            $json = null;
            try {
                $json = $resp->json();
            } catch (\Exception $e) {
                Log::error('processPayment: Unable to decode JSON', ['exception' => $e->getMessage()]);
                return view('Hotel::frontend.booking-pending', [
                    'status' => ['error' => 'decoding_json'],
                    'order_id' => $orderId,
                ]);
            }

            $status = data_get($json, 'status');
            $error = data_get($json, 'error');
            $httpStatus = $resp->status();
            $shouldPoll = false;

            // Persist provisional booking result (if present) so later pages can
            // display details while polling.  If no data is provided this will
            // simply store null.
            session(['bookingResult' => $json['data'] ?? null]);

            if ($error) {
                // If a terminal error is returned from the finish call, do not
                // poll.  Display the pending page with the error so the user
                // knows the booking failed and can retry.  Errors listed in
                // $finalFinishErrors are considered unrecoverable【788235750464074†L491-L501】.
                if (in_array($error, $this->finalFinishErrors, true)) {
                    Log::error('processPayment: Final finish error encountered', ['error' => $error]);
                    return view('Hotel::frontend.booking-pending', [
                        'status' => ['error' => $error],
                        'order_id' => $orderId,
                    ]);
                }
                // Temporary errors (timeout or unknown) should trigger polling of
                // the status endpoint to see if the booking eventually
                // completes【788235750464074†L491-L501】.  Any other error code is
                // treated as unrecoverable and surfaced to the user immediately.
                if (in_array($error, ['timeout', 'unknown'], true)) {
                    $shouldPoll = true;
                } else {
                    Log::error('processPayment: Unhandled finish error', ['error' => $error]);
                    return view('Hotel::frontend.booking-pending', [
                        'status' => ['error' => $error],
                        'order_id' => $orderId,
                    ]);
                }
            } elseif ($httpStatus >= 500) {
                // 5xx responses are considered temporary; poll status until a
                // final result is obtained.
                $shouldPoll = true;
            } else {
                // When no error is present, the status field will be `ok` or
                // `processing`.  Both cases require polling until the final
                // status arrives.  We set shouldPoll accordingly.
                if ($status === 'ok' || $status === 'processing') {
                    $shouldPoll = true;
                }
            }

            if ($shouldPoll) {
                // Poll the finish/status endpoint repeatedly until a final
                // response or until the remaining booking window expires.
                $remainingTime = $this->getRemainingBookingTime($partnerOrderId);
                $statusData = $this->pollFinishStatus($partnerOrderId, $remainingTime);
                Log::info('processPayment: finish/status response after polling', ['statusData' => $statusData]);
                if (data_get($statusData, 'status') === 'ok') {
                    // Booking confirmed after polling.  Clear the deadline and redirect.
                    $this->clearBookingDeadline($partnerOrderId);
                    return redirect()->route('hotel.payment.success');
                }
                // Otherwise, display the pending page with the last status or
                // error returned from the status call.
                return view('Hotel::frontend.booking-pending', [
                    'status' => $statusData,
                    'order_id' => $orderId,
                ]);
            }

            // If no polling is required and no unrecoverable error occurred,
            // consider the booking successful and redirect to the success page.
            // This branch covers the rare case when RateHawk finishes the
            // booking immediately and returns a final `ok` status from
            // /booking/finish/.  If status is not ok, fall back to pending.
            if ($status === 'ok') {
                // In the unlikely event that the finish call immediately returns
                // a final ok status and no polling was deemed necessary,
                // clear the booking deadline and treat the booking as complete.
                $this->clearBookingDeadline($partnerOrderId);
                return redirect()->route('hotel.payment.success');
            }

            // Fallback: show pending if the status is neither ok nor final.
            return view('Hotel::frontend.booking-pending', [
                'status' => ['status' => $status, 'error' => $error],
                'order_id' => $orderId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('processPayment error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
    }


    // public function finishBooking(Request $request)
    // {
    //     try {
    //         // Validate the request
    //         $request->validate([
    //             'partner_order_id' => 'required|string',
    //             'user.email' => 'required|email',
    //             'user.phone' => 'required|string',
    //             'supplier_data.first_name_original' => 'required|string',
    //             'supplier_data.last_name_original' => 'required|string',
    //             'supplier_data.phone' => 'required|string',
    //             'supplier_data.email' => 'required|email',
    //             'rooms' => 'required|array',
    //             'rooms.*.guests' => 'required|array',
    //             'rooms.*.guests.*.first_name' => 'required|string',
    //             'rooms.*.guests.*.last_name' => 'required|string',
    //             'payment_type.type' => 'required|string',
    //             'payment_type.amount' => 'required|string',
    //             'payment_type.currency_code' => 'required|string',
    //         ]);

    //         // Extract data from the request
    //         $data = $request->all();

    //         // Prepare the API payload
    //         $apiPayload = [
    //             'user' => $data['user'],
    //             'supplier_data' => $data['supplier_data'],
    //             'partner' => [
    //                 'partner_order_id' => $data['partner_order_id'],
    //             ],
    //             'language' => 'en',
    //             'rooms' => $data['rooms'],
    //             'payment_type' => $data['payment_type'],
    //         ];

    //         // Add return_path if payment_type is 'now'
    //         if (isset($data['payment_type']['type']) && $data['payment_type']['type'] === 'now') {
    //             $apiPayload['return_path'] = route('hotel.payment.success');
    //         }


    //         // Call the API
    //         $response = Http::withBasicAuth($this->username, $this->password)
    //             ->withHeaders(['Content-Type' => 'application/json'])
    //             ->post($this->apiUrl . 'hotel/order/booking/finish/', $apiPayload);

    //         if ($response->failed()) {
    //             throw new \Exception('API request failed: ' . $response->body());
    //         }

    //         $responseData = $response->json();

    //         // Handle the response
    //         if ($responseData['status'] === 'ok') {
    //             return redirect()->route('hotel.payment.success')->with('success', 'Booking completed successfully!');
    //         } else {
    //             throw new \Exception($responseData['error'] ?? 'Unknown error occurred.');
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Booking finish failed', ['error' => $e->getMessage()]);
    //         return redirect()->back()->withErrors(['error' => $e->getMessage()]);
    //     }
    // }

    /**
     * Called after PCB returns "FullyPaid"
     */
    public function handlePcbReturn(Request $request)
    {
        $orderId = $request->query('ID');       // PCB Order ID
        $status = $request->query('STATUS');   // e.g. "FullyPaid"
        $token = $request->query('token');    // For retrieving cached booking

        \Log::info('PCB Bank returned with', [
            'ID' => $orderId,
            'STATUS' => $status,
            'token' => $token,
        ]);

        // 1. NO CACHE: Retrieve booking data from session instead
        $bookingData = session("pending_booking_{$token}");
        $pcbOrder = session("pcb_order_{$token}");

        if (!$bookingData || !$pcbOrder) {
            \Log::error('No booking data found in session');
            return redirect()->route('hotel.search')
                ->withErrors(['error' => 'Session expired. Please try again.']);
        }

        // 2. Check if payment was successful
        if (strtolower($status) !== 'fullypaid') {
            \Log::error('PCB Bank payment was not successful', ['status' => $status]);
            return redirect()->route('hotel.search')
                ->withErrors(['error' => 'Payment was not successful.']);
        }

        // 3. Store PCB Bank order details in cache for later saving to MjellmaBooking
        $pcbService = new \App\Services\PcbBankService();
        $orderDetails = $pcbService->getOrderDetails($pcbOrder['id'], $pcbOrder['password']);

        if ($orderDetails) {
            // NO CACHE: Store PCB Bank response in session instead
            session(["pcb_response_{$bookingData['partner_order_id']}" => $orderDetails]);
            \Log::info('💾 PCB Bank response saved to session', [
                'partner_order_id' => $bookingData['partner_order_id'],
                'pcb_status' => $orderDetails['status'] ?? 'Unknown'
            ]);
        }

        // 4. Create local Payment record and Invoice for successful PCB Bank payment
        try {
            $this->createPaymentRecordAndInvoice($bookingData, $pcbOrder, $orderId);
        } catch (\Exception $e) {
            \Log::warning('⚠️ Failed to create payment record and invoice', [
                'error' => $e->getMessage(),
                'booking_data' => $bookingData
            ]);
            // Continue with booking completion even if invoice creation fails
        }

        // 5. Mark that we do NOT want to send credit card details to RateHawk
        //    We simply treat it like "deposit" or "offline" in RateHawk terms
        $bookingData['payment_type']['type'] = 'deposit';
        $bookingData['payment_type']['is_need_credit_card_data'] = false;

        // 6. Ensure phone is valid for RateHawk's validation (min length 5)
        if (empty($bookingData['phone']) || strlen($bookingData['phone']) < 5) {
            $bookingData['phone'] = '+0000000000'; // fallback
        }

        // 7. Optionally, store or override any needed user details to pass to RateHawk
        $bookingData['first_name'] = $bookingData['first_name'] ?? 'Guest';
        $bookingData['last_name'] = $bookingData['last_name'] ?? 'User';

        \Log::info('Confirming booking after PCB as a deposit booking', ['payload' => $bookingData]);

        // 8. Call finishBooking with the updated data
        return app()->call([$this, 'finishBooking'], ['request' => new Request($bookingData)]);
    }

    /**
     * Create local Payment record and Invoice for successful PCB Bank payment
     */
    private function createPaymentRecordAndInvoice($bookingData, $pcbOrder, $orderId)
    {
        // Get PCB Bank service to retrieve order details
        $pcbService = new \App\Services\PcbBankService();
        $orderDetails = $pcbService->getOrderDetails($pcbOrder['id'], $pcbOrder['password']);

        if (!$orderDetails) {
            throw new \Exception('Could not retrieve PCB Bank order details');
        }

        // Create a local booking record for invoice purposes
        $booking = new \Modules\Booking\Models\Booking();
        $booking->code = 'PCB-' . $orderId . '-' . time();
        $booking->status = \Modules\Booking\Models\Booking::PAID;
        $booking->first_name = $bookingData['first_name'] ?? 'Guest';
        $booking->last_name = $bookingData['last_name'] ?? 'User';
        $booking->email = $bookingData['email'] ?? 'customer@example.com';
        $booking->phone = $bookingData['phone'] ?? '+0000000000';

        // Use display_final_price (customer paid amount with 15% markup for guests)
        $customerPaidAmount = $bookingData['display_final_price'] ?? $bookingData['payment_type']['amount'] ?? 0;
        $booking->pay_now = $customerPaidAmount;
        $booking->paid = $customerPaidAmount;
        $booking->save();

        // Create payment record
        $payment = new \Modules\Booking\Models\Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = 'pcb_bank';
        $payment->status = 'completed';
        $payment->amount = $customerPaidAmount;

        // Store comprehensive payment data for invoice generation
        // Structure the data to match what PcbBankGateway::getInvoiceData expects
        $invoiceData = [
            'pcb_gateway_response' => $orderDetails,
            'pcb_order_id' => $orderId,
            'pcb_type_rid' => $orderDetails['typeRid'] ?? null,
            'pcb_status' => $orderDetails['status'] ?? null,
            'pcb_create_time' => $orderDetails['createTime'] ?? null,
            'pcb_last_status_login' => $orderDetails['lastStatusLogin'] ?? null,
            'pcb_transaction_id' => $orderDetails['trans'][0]['actionId'] ?? null,
            'pcb_approval_code' => $orderDetails['trans'][0]['approvalCode'] ?? null,
            'pcb_rid_by_pmo' => $orderDetails['trans'][0]['ridByPmo'] ?? null,
            'pcb_approved_partial' => $orderDetails['trans'][0]['approvedPartial'] ?? false,
            'pcb_payment_method' => $orderDetails['srcToken']['paymentMethod'] ?? 'Card',
            'pcb_card_last4' => substr($orderDetails['srcToken']['displayName'] ?? '', -4),
            'pcb_card_pan' => 'XXXXXXXXXXXX' . substr($orderDetails['srcToken']['displayName'] ?? '', -4),
            'pcb_card_brand' => $orderDetails['srcToken']['card']['brand'] ?? 'Visa',
            'pcb_cvv2_auth_status' => $orderDetails['srcToken']['card']['authentication']['needCvv2'] ?? false,
            'pcb_amount_eur' => number_format($customerPaidAmount, 2),
            'pcb_amount_cents' => $customerPaidAmount * 100,
            'pcb_currency' => $bookingData['payment_type']['currency_code'] ?? 'EUR',
            'pcb_currency_code' => '978',
            'pcb_transaction_datetime' => $orderDetails['createTime'] ?? now()->toDateTimeString(),
            'pcb_order_type_title' => $orderDetails['type']['title'] ?? null,
            'pcb_allow_void' => false,
            'pcb_allow_cvv2' => $orderDetails['srcToken']['card']['authentication']['needCvv2'] ?? false,
            'pcb_payment_time' => $orderDetails['createTime'] ?? now()->toDateTimeString(),
            'pcb_processor_response' => 'Approved',
        ];

        $payment->logs = json_encode($invoiceData);
        $payment->save();

        // Create invoice using InvoiceService
        $invoiceService = new \App\Services\InvoiceService();
        $invoice = $invoiceService->createInvoice($payment);

        // Generate PDF
        $invoiceService->generatePdf($invoice);

        // Send invoice via email
        $invoiceService->sendInvoice($invoice);

        \Log::info('📄 Invoice created and sent for PCB Bank payment', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'booking_id' => $booking->id,
            'pcb_order_id' => $orderId
        ]);

        return $invoice;
    }


    public function handleBookingSubmission(Request $request)
    {
        $paymentType = $request->input('payment_type.type');
        $requiresCard = $request->input('payment_type.is_need_credit_card_data', false);

        Log::info('📥 Booking Submission', [
            'payment_type' => $paymentType,
            'requires_card' => $requiresCard,
        ]);

        // Check if PCB Bank payment is selected (from the dropdown)
        $isPcbPayment = $request->input('payment_type.type') === 'pcb_bank';

        if (($paymentType === 'now' && $requiresCard) || $isPcbPayment) {
            // NO CACHE: Generate token and store in session
            $token = Str::random(32);
            session(["pending_booking_{$token}" => $request->all()]);

            // Use display_final_price for customer payment (includes 15% markup for guests)
            // RateHawk will still receive the original payment_type.amount
            $displayPrice = $request->input('display_final_price');
            $amount = $displayPrice ?: $request->input('payment_type.amount');

            $description = 'Hotel Booking Pre-Payment';
            $redirectUrl = route('pcb.booking.return', ['token' => $token]);

            $pcb = new \App\Services\PcbBankService();
            $order = $pcb->createOrder($amount, $description, $redirectUrl);

            if ($order && isset($order['id'], $order['password'], $order['hppUrl'])) {
                // NO CACHE: Store PCB order in session
                session([
                    "pcb_order_{$token}" => [
                        'id' => $order['id'],
                        'password' => $order['password'],
                    ]
                ]);

                Log::info('🔁 Redirecting to PCB Bank', ['url' => $order['hppUrl']]);
                return redirect($order['hppUrl'] . "?id={$order['id']}&password={$order['password']}");
            }

            Log::error('❌ PCB createOrder failed');
            return back()->withErrors(['error' => 'Failed to redirect to PCB Bank.']);
        }

        // COMMENTED OUT: Deposit payment flow - Only PCB Bank payment is supported now
        // Log::info('✅ No credit card required — calling finishBooking directly');
        // return app()->call([$this, 'finishBooking'], ['request' => $request]);

        // If we reach here, payment method is not supported
        Log::error('❌ Unsupported payment method');
        return back()->withErrors(['error' => 'Only PCB Bank payment is supported. Please select PCB Bank payment method.']);
    }

    public function confirmAfterPcb(Request $request)
    {
        $partnerOrderId = $request->input('ID');
        $status = $request->input('STATUS');

        Log::info('📨 PCB Bank returned with', ['ID' => $partnerOrderId, 'STATUS' => $status]);

        // NO CACHE: Get booking data from session
        $bookingData = session('booking_' . $partnerOrderId);
        $pcbData = session('pcb_order_' . $partnerOrderId);

        if (!$bookingData || !$pcbData) {
            Log::error('❌ No booking data found in session');
            return redirect()->route('hotel.search')->withErrors(['error' => 'Booking session expired or not found.']);
        }

        if (!in_array(strtolower($status), ['success', 'fullypaid', 'paid'])) {
            Log::error('❌ PCB Bank payment was not successful');
            return redirect()->route('hotel.search')->withErrors(['error' => 'Payment was not successful.']);
        }

        // Inject UUIDs into bookingData
        $bookingData['init_uuid'] = $pcbData['id'];
        $bookingData['pay_uuid'] = $pcbData['password'];

        Log::info('🔄 Confirming booking after PCB', ['payload' => $bookingData]);

        // Build a request manually
        $newRequest = new \Illuminate\Http\Request($bookingData);

        return app()->call([$this, 'finishBooking'], ['request' => $newRequest]);
    }


    public function sendBookingForm($partnerOrderId, $bookHash, $userIp)
    {
        $payload = [
            'partner_order_id' => $partnerOrderId,
            'book_hash' => $bookHash,
            'language' => 'en',
            'user_ip' => $userIp,
        ];

        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/booking/form/', $payload);

        if ($response->successful()) {
            $data = $response->json()['data'];
            return $data; // contains order_id, item_id, payment_types, etc.
        }

        Log::error('❌ Booking form failed', ['response' => $response->body()]);
        return null;
    }

    public function getCardTokenFromPcb($orderId, $initUuid, $payUuid)
    {
        $payload = [
            'order_id' => (int) $orderId,
            'init_uuid' => $initUuid,
            'pay_uuid' => $payUuid,
        ];

        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/booking/credit-card/', $payload);

        if ($response->successful() && isset($response->json()['data']['token'])) {
            return $response->json()['data']['token'];
        }

        Log::error('❌ Failed to get credit card token from RateHawk', ['response' => $response->body()]);
        return null;
    }

    //me card data

    // public function finishBooking(Request $request)
    // {
    //     $data = $request->all();

    //     // Overriding supplier data: always use fixed name and email and generate a random phone number.
    //     $supplierData = [
    //         'first_name_original' => 'Mjellma Travel',
    //         'last_name_original'  => 'Mjellma Travel',
    //         // Generate a random 10-digit phone number. Adjust the range as needed.
    //         'phone'               => (string) rand(1000000000, 9999999999),
    //         'email'               => 'mjellmatravel@hotmail.com',
    //     ];

    //     $payload = [
    //         'user' => [
    //             'email' => $data['user']['email'],
    //             'phone' => $data['user']['phone'],
    //         ],
    //         'supplier_data' => $supplierData,
    //         'partner' => [
    //             'partner_order_id' => $data['partner_order_id'],
    //         ],
    //         'language' => 'en',
    //         'rooms' => $data['rooms'],
    //         'payment_type' => [
    //             'type'                  => $data['payment_type']['type'],
    //             'amount'                => $data['payment_type']['amount'],
    //             'currency_code'         => $data['payment_type']['currency_code'],
    //             'is_need_credit_card_data' => $data['payment_type']['is_need_credit_card_data'],
    //         ],
    //         'return_path' => $data['return_path'],
    //     ];

    //     Log::debug('Final payload before credit card data check', ['payload' => $payload]);

    //     // Add credit card data if required
    //     if (!empty($data['payment_type']['is_need_credit_card_data'])) {
    //         if (empty($data['init_uuid']) || empty($data['pay_uuid'])) {
    //             Log::error('Credit card data missing: init_uuid or pay_uuid not found', [
    //                 'init_uuid' => $data['init_uuid'] ?? 'not set',
    //                 'pay_uuid'  => $data['pay_uuid'] ?? 'not set'
    //             ]);
    //         }
    //         $payload['credit_card_data'] = [
    //             'init_uuid' => (string)$data['init_uuid'], // Cast to string just in case
    //             'pay_uuid'  => $data['pay_uuid'],
    //         ];
    //     } else {
    //         Log::debug('Payment type does not require credit card data');
    //     }

    //     Log::debug('Sending final booking payload to RateHawk', ['payload' => $payload]);

    //     $response = Http::withBasicAuth($this->username, $this->password)
    //         ->withHeaders(['Content-Type' => 'application/json'])
    //         ->post($this->apiUrl . 'hotel/order/booking/finish/', $payload);

    //     Log::debug('Received response from RateHawk booking finish', ['response_body' => $response->body()]);

    //     $json = $response->json();

    //     if ($response->successful() && $json['status'] === 'ok') {
    //         Log::info('✅ Booking success', ['response' => $json]);
    //         return response()->json(['success' => true, 'result' => $json]);
    //     }

    //     Log::error('❌ Booking failed', [
    //         'payload_sent' => $payload,
    //         'response_body' => $response->body()
    //     ]);

    //     return response()->json(['error' => $json['error'] ?? 'Unknown error'], 500);
    // }

    //insufficient_b2b_balance

    // public function finishBooking(Request $request)
    // {
    //     $data = $request->all();

    //     // Validate we have an order_id
    //     if (!isset($data['order_id'])) {
    //         \Log::error('Missing order_id in booking data.');
    //         return response()->json(['error' => 'Missing order_id'], 422);
    //     }

    //     // Build final payload for RateHawk with deposit payment type
    //     $payload = [
    //         'user' => [
    //             'first_name' => $data['first_name'] ?? '',
    //             'last_name'  => $data['last_name'] ?? '',
    //             'email'      => $data['email'] ?? 'guest@example.com',
    //             // use phone fallback if needed
    //             'phone'      => (isset($data['phone']) && strlen($data['phone']) >= 5)
    //                                 ? $data['phone'] : '+0000000000',
    //         ],
    //         'supplier_data' => [
    //             'first_name_original' => $data['supplier_data']['first_name_original'] ?? '',
    //             'last_name_original'  => $data['supplier_data']['last_name_original'] ?? '',
    //             'phone'               => $data['supplier_data']['phone'] ?? '',
    //             'email'               => $data['supplier_data']['email'] ?? '',
    //         ],
    //         'partner' => [
    //             'partner_order_id'  => $data['partner_order_id'] ?? '',
    //         ],
    //         'order_id'     => $data['order_id'],
    //         'language'     => $data['language'] ?? 'en',
    //         'rooms'        => $data['rooms'] ?? [],
    //         'payment_type' => [
    //             'type'          => $data['payment_type']['type'] ?? 'deposit', // deposit/offline
    //             'amount'        => $data['payment_type']['amount'] ?? '0',
    //             'currency_code' => $data['payment_type']['currency_code'] ?? 'EUR',
    //             'is_need_credit_card_data' => false,
    //         ],
    //         'return_path' => $data['return_path'] ?? '',
    //     ];

    //     \Log::debug('Sending final booking payload to RateHawk', ['payload' => $payload]);

    //     $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->username, $this->password)
    //         ->withHeaders(['Content-Type' => 'application/json'])
    //         ->post($this->apiUrl . 'hotel/order/booking/finish/', $payload);

    //     \Log::debug('Received response from RateHawk booking finish', ['response_body' => $response->body()]);

    //     $json = $response->json();
    //     if ($response->successful() && isset($json['status']) && $json['status'] === 'ok') {
    //         \Log::info('Booking success', ['response' => $json]);

    //         // Here you can store the final booking details in your DB
    //         // and then redirect to a final "Booking Completed" page or similar
    //         return response()->json(['success' => true, 'result' => $json]);
    //     }

    //     \Log::error('Booking failed', [
    //         'payload_sent'  => $payload,
    //         'response_body' => $response->body()
    //     ]);

    //     return response()->json(['error' => $json['error'] ?? 'Unknown error'], 500);
    // }

    // public function finishBooking(Request $request)
    // {
    //     $data = $request->all();

    //     if (!isset($data['order_id'])) {
    //         \Log::error('Missing order_id in booking data.');
    //         return response()->json(['error' => 'Missing order_id'], 422);
    //     }

    //     // Normalize user details.
    //     $userFirstName = isset($data['first_name']) ? trim($data['first_name']) : '';
    //     $userLastName  = isset($data['last_name']) ? trim($data['last_name']) : '';
    //     $userEmail     = (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL))
    //                         ? trim($data['email'])
    //                         : 'guest@example.com';
    //     $userPhone     = isset($data['phone']) ? trim($data['phone']) : '';
    //     if (strlen($userPhone) < 5) {
    //         $userPhone = '+0000000000';
    //     }

    //     // Determine the original payment type from the input (it may be "now" for a PCB flow or something else)
    //     $originalPaymentType = $data['payment_type']['type'] ?? 'deposit';

    //     // For testing: if it's a PCB ("now") flow, keep type "now" but override credit card requirement to false.
    //     // Otherwise, use "deposit".
    //     if ($originalPaymentType === 'now') {
    //         $finalPaymentType = 'now';
    //     } else {
    //         $finalPaymentType = 'deposit';
    //     }

    //     $payload = [
    //         'user' => [
    //             'first_name' => $userFirstName,
    //             'last_name'  => $userLastName,
    //             'email'      => $userEmail,
    //             'phone'      => $userPhone,
    //         ],
    //         'supplier_data' => [
    //             'first_name_original' => $data['supplier_data']['first_name_original'] ?? '',
    //             'last_name_original'  => $data['supplier_data']['last_name_original'] ?? '',
    //             'phone'               => $data['supplier_data']['phone'] ?? '',
    //             'email'               => $data['supplier_data']['email'] ?? '',
    //         ],
    //         'partner' => [
    //             'partner_order_id'  => $data['partner_order_id'] ?? '',
    //             'comment'           => $data['partner_comment'] ?? '',
    //             'amount_sell_b2b2c' => isset($data['amount_sell_b2b2c']) && is_numeric($data['amount_sell_b2b2c'])
    //                                     ? number_format($data['amount_sell_b2b2c'], 2, '.', '')
    //                                     : '0.00',
    //         ],
    //         'order_id'   => $data['order_id'],
    //         'language'   => $data['language'] ?? 'en',
    //         'rooms'      => $data['rooms'] ?? [],
    //         'payment_type' => [
    //             'type'          => $finalPaymentType,
    //             'amount'        => $data['payment_type']['amount'] ?? '',
    //             'currency_code' => $data['payment_type']['currency_code'] ?? 'EUR',
    //             // For testing, always mark as not requiring credit card data
    //             'is_need_credit_card_data' => false,
    //         ],
    //         'return_path' => $data['return_path'] ?? '',
    //     ];

    //     \Log::debug('Sending final booking payload to RateHawk', ['payload' => $payload]);

    //     $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->username, $this->password)
    //         ->withHeaders(['Content-Type' => 'application/json'])
    //         ->post($this->apiUrl . 'hotel/order/booking/finish/', $payload);

    //     \Log::debug('Received response from RateHawk booking finish', ['response_body' => $response->body()]);

    //     $json = $response->json();

    //     // For testing only: if RateHawk returns "insufficient_b2b_balance", simulate a successful booking.
    //     if (isset($json['error']) && $json['error'] === 'insufficient_b2b_balance') {
    //         \Log::warning('Simulating booking success due to insufficient_b2b_balance for testing.');
    //         $json = [
    //             'status' => 'ok',
    //             'data'   => [
    //                 'order_id' => $payload['order_id'],
    //                 'item_id'  => $payload['rooms'][0]['guests'][0]['first_name'] . '_simulated',
    //                 // Add additional simulated booking details as needed.
    //             ],
    //         ];
    //     }

    //     if (isset($json['status']) && $json['status'] === 'ok') {
    //         \Log::info('Booking success', ['response' => $json]);

    //         // Return the view with order_id and partner_order_id for displaying to the user.
    //         return view('Hotel::frontend.payment-success', [
    //             'order_id' => $payload['order_id'],
    //             'partner_order_id' => $data['partner_order_id'] ?? null,
    //         ]);
    //     }

    //     \Log::error('Booking failed', [
    //         'payload_sent'  => $payload,
    //         'response_body' => $response->body()
    //     ]);

    //     return response()->json(['error' => $json['error'] ?? 'Unknown error'], 500);
    // }

    public function finishBooking(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'partner_order_id' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string|min:5',
            'payment_type' => 'required|array',
            'payment_type.type' => 'required|string',
            'payment_type.amount' => 'required|numeric',
            'payment_type.currency_code' => 'required|string',
            'rooms' => 'required|array',
            'rooms.*.guests' => 'required|array',
            'rooms.*.guests.*.first_name' => 'required|string',
            'rooms.*.guests.*.last_name' => 'required|string',
        ]);

        $partnerOrderId = $request->input('partner_order_id');

        // Ensure a booking deadline exists so that all subsequent polling
        // shares a single timeout window.  If a deadline already exists (for
        // example, from the form step) it will not be overwritten.
        $this->getBookingDeadline($partnerOrderId);
        $existing = MjellmaBooking::where('partner_order_id', $partnerOrderId)->first();

        if ($existing) {
            if ($existing->api_status === 'ok') {
                return view('Hotel::frontend.payment-success', [
                    'order_id' => $existing->order_id,
                    'partner_order_id' => $existing->partner_order_id,
                ]);
            }

            if ($existing->api_status === 'processing') {
                Log::info('📡 Existing booking is still processing — skipping re-call to /finish/');
                // Poll finish/status using the remaining booking window.  This
                // ensures we respect the same timeout across all steps and
                // avoid ghost bookings.
                $remainingTime = $this->getRemainingBookingTime($partnerOrderId);
                $statusData = $this->pollFinishStatus($partnerOrderId, $remainingTime);
                Log::info('⏳ Polled finish/status during retry', ['status' => $statusData]);

                if (data_get($statusData, 'status') === 'ok') {
                    // Mark the booking as completed and clear the deadline
                    $existing->update(['api_status' => 'ok']);
                    $this->clearBookingDeadline($partnerOrderId);
                    return view('Hotel::frontend.payment-success', [
                        'order_id' => $existing->order_id,
                        'partner_order_id' => $existing->partner_order_id,
                    ]);
                }

                return view('Hotel::frontend.booking-pending', [
                    'status' => $statusData,
                    'order_id' => $existing->order_id,
                ]);
            }
        }

        $payload = [
            'order_id' => $request->input('order_id'),
            'partner' => ['partner_order_id' => $partnerOrderId],
            'user' => [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => 'blerimmi@hotmail.com',
                'phone' => $request->input('phone'),
            ],
            'supplier_data' => $request->input('supplier_data', []),
            'rooms' => $request->input('rooms'),
            'payment_type' => $request->input('payment_type'),
            'language' => $request->input('language', 'en'),
            'return_path' => $request->input('return_path'),
            'item_id' => $request->input('item_id'),
            'book_hash' => $request->input('book_hash'),
        ];

        Log::info('📥 Booking Submission', ['payload' => $payload]);

        // Send the finish call.  Any exception here should bubble up and
        // trigger the catch block below.
        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($this->apiUrl . 'hotel/order/booking/finish/', $payload);

        $json = $response->json();
        Log::info('finishBooking response', ['response' => $json]);

        // Extract status and error returned from the finish call
        $status = data_get($json, 'status');
        $error = data_get($json, 'error');
        $httpStatus = $response->status();

        // Determine whether we should proceed to poll the status endpoint.
        // According to RateHawk documentation:
        // - Any error in $finalFinishErrors is considered final and we should
        //   surface it to the user immediately.  This includes
        //   `double_booking_finish`, which indicates that the finish call has
        //   already been issued for this booking.  Repeating the call is a
        //   logic error and does not warrant further polling.
        // - `timeout`, `unknown` and HTTP 5xx responses are temporary and require
        //   polling the status endpoint.
        // - If status is `ok` or `processing` without an error, we should poll
        //   until completion because the booking may still be settling.
        $shouldPoll = false;

        if ($error) {
            // If we received an error, check if it is final.  Any error in
            // $finalFinishErrors, including double_booking_finish, should
            // immediately surface a user‑friendly message and skip polling.
            if (in_array($error, $this->finalFinishErrors, true)) {
                Log::error('❌ Final finish error encountered', ['error' => $error]);
                return view('Hotel::frontend.booking-pending', [
                    'status' => ['error' => $error],
                    'order_id' => $payload['order_id'],
                ]);
            }
            // For timeout/unknown errors we should poll
            if (in_array($error, ['timeout', 'unknown'], true)) {
                Log::warning('⏳ finish returned temporary error, will poll status', ['error' => $error]);
                $shouldPoll = true;
            } else {
                // Unexpected error code: treat as unrecoverable and surface to user
                Log::error('❌ Unhandled finish error', ['error' => $error]);
                return view('Hotel::frontend.booking-pending', [
                    'status' => ['error' => $error],
                    'order_id' => $payload['order_id'],
                ]);
            }
        } elseif ($httpStatus >= 500) {
            // Server errors should be treated as temporary; poll status
            Log::warning('⏳ finish call returned server error, will poll status', ['http_status' => $httpStatus]);
            $shouldPoll = true;
        } else {
            // No error field means status is either ok or processing; both warrant polling
            if ($status === 'ok' || $status === 'processing') {
                $shouldPoll = true;
            }
        }

        // If the finish call is ok or processing, persist it to our DB as
        // "processing".  According to ETG, a status of "ok" returned from
        // /booking/finish/ does not mean the booking has fully completed
        //【995068486253864†L492-L501】.  We therefore treat both "ok" and
        // "processing" as transient states that require polling the
        // /booking/finish/status/ endpoint until a final ok is obtained.  By
        // storing "processing" in the database for both cases we ensure
        // subsequent calls to finishBooking() will continue polling rather
        // than assuming success prematurely.
        if ($status === 'ok' || $status === 'processing') {
            // NO CACHE: Get PCB Bank response from session if available
            $pcbResponse = session("pcb_response_{$partnerOrderId}");

            $bookingData = [
                'order_id' => $payload['order_id'],
                'payment_type' => $payload['payment_type']['type'],
                'payment_amount' => $payload['payment_type']['amount'],
                'currency_code' => $payload['payment_type']['currency_code'],
                // Store customer information for email notifications
                'user_email' => $payload['user']['email'] ?? null,
                'user_phone' => $payload['user']['phone'] ?? null,
                // Always store "processing" here so later calls know to poll
                'api_status' => 'processing',
            ];

            // Add PCB Bank response if available
            if ($pcbResponse) {
                $bookingData['pcb_bank_response'] = $pcbResponse;
                $bookingData['pcb_status'] = $pcbResponse['status'] ?? 'Unknown';
                \Log::info('💾 PCB Bank response saved to MjellmaBooking', [
                    'partner_order_id' => $partnerOrderId,
                    'pcb_status' => $pcbResponse['status'] ?? 'Unknown'
                ]);
            }

            MjellmaBooking::updateOrCreate(
                ['partner_order_id' => $partnerOrderId],
                $bookingData
            );
        }

        if ($shouldPoll) {
            // Poll the finish/status endpoint until a final status or final error.
            // Respect the remaining booking window so that all steps share
            // a common timeout and we avoid ghost bookings.  The remaining
            // time is computed from the booking deadline created earlier.
            $remainingTime = $this->getRemainingBookingTime($partnerOrderId);
            $statusData = $this->pollFinishStatus($partnerOrderId, $remainingTime);
            Log::info('finishBooking status response', ['status' => $statusData]);

            if (data_get($statusData, 'status') === 'ok') {
                // Final success: mark as completed in DB and clear deadline
                $mjellmaBooking = MjellmaBooking::where('partner_order_id', $partnerOrderId)->first();
                if ($mjellmaBooking) {
                    $mjellmaBooking->api_status = 'ok';
                    $mjellmaBooking->save();

                    // Trigger event to send customer confirmation email
                    event(new MjellmaBookingCreatedEvent($mjellmaBooking));
                }

                $this->clearBookingDeadline($partnerOrderId);
                return view('Hotel::frontend.payment-success', [
                    'order_id' => $payload['order_id'],
                    'partner_order_id' => $partnerOrderId,
                ]);
            }

            // Booking not completed yet; return pending with the last status
            return view('Hotel::frontend.booking-pending', [
                'status' => $statusData,
                'order_id' => $payload['order_id'],
            ]);
        }

        // If we reach here it means there was no need to poll and no unrecoverable
        // error was encountered.  In practice this branch is rare but can
        // happen if RateHawk returns a non-`ok` non-`processing` status without
        // errors.  Treat it as final success.  Update the booking record to
        // 'ok' and clear the deadline to avoid leaving stale entries.
        MjellmaBooking::where('partner_order_id', $partnerOrderId)
            ->update(['api_status' => 'ok']);
        $this->clearBookingDeadline($partnerOrderId);
        return view('Hotel::frontend.payment-success', [
            'order_id' => $payload['order_id'],
            'partner_order_id' => $partnerOrderId,
        ]);
    }


    public function completeBooking(Request $request)
    {
        $booking = MjellmaBooking::findOrFail($request->input('booking_id'));
        $partnerId = $request->input('partner_order_id', $booking->partner_order_id);

        // Ensure a booking deadline exists for this order id so that all steps
        // share the same timeout window.  If a deadline was already set in a
        // prior step, this call will simply return the existing value.
        $this->getBookingDeadline($partnerId);

        // Build the finish payload according to the booking record and request
        $finishPayload = [
            'order_id' => $booking->order_id,
            'partner_order_id' => $partnerId,
            'supplier_data' => $request->input('supplier_data', []),
            'payment_type' => [
                'amount' => $booking->payment_amount,
                'currency_code' => $booking->currency_code,
                'type' => $booking->payment_type,
            ],
            'return_path' => $request->input('return_path'),
            'rooms' => $request->input('rooms'),
            'language' => $request->input('language', 'en'),
            'book_hash' => $booking->book_hash,
            'item_id' => $booking->item_id,
        ];

        try {
            $resp = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($this->apiUrl . 'hotel/order/booking/finish/', $finishPayload);

            $json = $resp->json();
            $status = data_get($json, 'status');
            $error = data_get($json, 'error');
            $httpStatus = $resp->status();

            Log::info('completeBooking finish response', ['response' => $json]);

            // Determine if we should poll finish/status
            $shouldPoll = false;
            if ($error) {
                // Final finish errors (including double_booking_finish) should be surfaced
                // immediately and not retried or polled.
                if (in_array($error, $this->finalFinishErrors, true)) {
                    return view('Hotel::frontend.booking-pending', [
                        'status' => ['error' => $error],
                        'order_id' => $booking->order_id,
                    ]);
                }
                // Temporary errors (timeout/unknown) warrant polling the finish/status endpoint
                if (in_array($error, ['timeout', 'unknown'], true)) {
                    Log::warning('⏳ Temporary finish error returned; will poll finish/status', ['error' => $error]);
                    $shouldPoll = true;
                } else {
                    // Any other error is treated as unrecoverable
                    return view('Hotel::frontend.booking-pending', [
                        'status' => ['error' => $error],
                        'order_id' => $booking->order_id,
                    ]);
                }
            } elseif ($httpStatus >= 500) {
                Log::warning('⏳ Server error from finish call; will poll finish/status', ['status' => $httpStatus]);
                $shouldPoll = true;
            } else {
                // status is ok or processing (no error)
                if ($status === 'ok' || $status === 'processing') {
                    $shouldPoll = true;
                }
            }

            // Persist booking status if finish returned ok or processing.  Do not
            // record 'ok' here because the booking may still be settling.  We
            // instead record 'processing' for both statuses so later calls know
            // to continue polling until the final ok status is returned【995068486253864†L492-L501】.
            if ($status === 'ok' || $status === 'processing') {
                $booking->update(['status' => 'processing']);
            }

            if ($shouldPoll) {
                // Poll using the remaining booking window so that all steps
                // collectively respect a single timeout.  We avoid a fixed
                // short window to allow recovery from transient issues until
                // the overall booking timeout expires.
                $remainingTime = $this->getRemainingBookingTime($partnerId);
                $statusData = $this->pollFinishStatus($partnerId, $remainingTime);
                Log::info('completeBooking status response', ['status' => $statusData]);
                if (data_get($statusData, 'status') === 'ok') {
                    // Mark final success and clear the deadline
                    $booking->update(['status' => 'ok']);
                    $this->clearBookingDeadline($partnerId);
                    return view('Hotel::frontend.payment-success', [
                        'booking' => $booking,
                        'statusData' => $statusData,
                    ]);
                }
                return view('Hotel::frontend.booking-pending', [
                    'booking' => $booking,
                    'status' => $statusData,
                ]);
            }

            // If no polling is needed and no unrecoverable error, treat as final
            // success.  Update the booking to 'ok' and clear the deadline.
            $booking->update(['status' => 'ok']);
            $this->clearBookingDeadline($partnerId);
            return view('Hotel::frontend.payment-success', [
                'booking' => $booking,
                'statusData' => $json,
            ]);

        } catch (\Exception $e) {
            Log::error('completeBooking finish call threw exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return view('Hotel::frontend.booking-pending', [
                'booking' => $booking,
                'status' => ['error' => 'unknown'],
            ]);
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $payload = [
            'ordering' => [
                'ordering_type' => 'desc',
                'ordering_by' => 'created_at'
            ],
            'pagination' => [
                'page_size' => 50,
                'page_number' => 1
            ],
            'language' => 'en'
        ];

        if ($user->role_id !== 1) {
            $partnerOrderIds = MjellmaBooking::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('agent_id', $user->id);
            })->pluck('partner_order_id')->filter()->values()->toArray();

            if (empty($partnerOrderIds)) {
                return view('Hotel::admin.booking', ['bookings' => [], 'statuses' => []]);
            }

            $payload['search'] = [
                'partner_order_ids' => $partnerOrderIds
            ];
        }

        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/info/', $payload);

        $bookings = [];
        $statuses = [];

        if ($response->successful() && isset($response['data']['orders'])) {
            foreach ($response['data']['orders'] as $order) {
                $bookings[] = [
                    'order_id' => $order['order_id'] ?? '',
                    'client_name' => collect(data_get($order, 'rooms_data.0.guest_data.guests', []))
                        ->map(function ($guest) {
                            return trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? ''));
                        })
                        ->filter()
                        ->implode(', '),
                    'user_email' => data_get($order, 'user.email', 'N/A'),
                    'user_phone' => data_get($order, 'user.phone', 'N/A'),
                    'payment_amount' => data_get($order, 'client_price.amount', 'N/A'),
                    'currency_code' => data_get($order, 'client_price.currency_code', ''),
                    'created_at' => data_get($order, 'created_at', 'N/A'),
                    'invoice_id' => data_get($order, 'invoice_id', 'N/A'),
                    'status' => data_get($order, 'status', 'unknown'),
                    'partner_order_id' => data_get($order, 'partner_data.order_id'),
                ];

                $statuses[$order['order_id']] = $order['status'] ?? 'unknown';
            }
        } else {
            Log::error('Failed to fetch bookings from RateHawk', [
                'response' => $response->body()
            ]);
        }

        return view('Hotel::admin.booking', compact('bookings', 'statuses'));
    }


    //see details
    public function showBookingDetails(Request $request, $orderId)
    {
        try {
            // 1) Fetch “order/info”
            $infoResp = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/info/', [
                    'ordering' => ['ordering_type' => 'desc', 'ordering_by' => 'created_at'],
                    'pagination' => ['page_size' => 1, 'page_number' => 1],
                    'search' => ['order_ids' => [(int) $orderId]],
                    'language' => 'en',
                ]);

            if (!$infoResp->successful()) {
                throw new \Exception("order/info returned HTTP " . $infoResp->status());
            }

            $infoJson = $infoResp->json();
            if (($infoJson['status'] ?? '') !== 'ok' || empty($infoJson['data']['orders'][0])) {
                throw new \Exception($infoJson['error'] ?? 'No order found');
            }

            $bookingInfo = $infoJson['data']['orders'][0];
            $partnerOrderId = data_get($bookingInfo, 'partner_data.order_id');
            if (!$partnerOrderId) {
                throw new \Exception("Missing partner_order_id in partner_data");
            }

            // 2) Fetch “finish/status”
            $statusResp = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/finish/status/', [
                    'partner_order_id' => $partnerOrderId
                ]);

            if (!$statusResp->successful()) {
                throw new \Exception("finish/status returned HTTP " . $statusResp->status());
            }

            $statusJson = $statusResp->json();
            $finalStatus = $statusJson['status'] ?? 'UNKNOWN';
            $finishData = $statusJson;

        } catch (\Exception $e) {
            Log::error('Error in showBookingDetails', [
                'orderId' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors([
                'error' => 'Could not load booking details: ' . $e->getMessage()
            ]);
        }

        return view('Hotel::admin.details', [
            'booking' => $bookingInfo,
            'finalStatus' => $finalStatus,
            'finishData' => $finishData,
        ]);
    }

    //cancel
    // public function cancelBooking(Request $request, $partnerOrderId)
    // {
    //     $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->username, $this->password)
    //         ->withHeaders(['Content-Type' => 'application/json'])
    //         ->post($this->apiUrl . 'hotel/order/cancel/', [
    //             'partner_order_id' => $partnerOrderId
    //         ]);

    //     $json = $response->json();

    //     if ($json['status'] === 'ok') {
    //         return redirect()->back()->with('success', 'Booking cancelled successfully.');
    //     }

    //     return redirect()->back()->with('error', $json['error'] ?? 'Failed to cancel booking.');
    // }


    public function cancelBooking(Request $request, $partnerOrderId)
    {
        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/cancel/', [
                'partner_order_id' => $partnerOrderId,
                'user' => [
                    'email' => 'blerimmi@hotmail.com'
                ]
            ]);

        $json = $response->json();

        if ($json['status'] === 'ok') {
            return redirect()->back()->with('success', 'Booking cancelled successfully.');
        }

        return redirect()->back()->with('error', $json['error'] ?? 'Failed to cancel booking.');
    }

    public function sendEmailFromPartnerOrder($partnerOrderId)
    {
        // 1. Get the booking from your local DB
        $storedBooking = MjellmaBooking::where('partner_order_id', $partnerOrderId)->first();

        if (!$storedBooking || !filter_var($storedBooking->user_email, FILTER_VALIDATE_EMAIL)) {
            Log::warning("❌ Invalid or missing user_email for partner_order_id: $partnerOrderId");
            return false;
        }

        // 2. Fetch booking info from RateHawk API
        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/info/', [
                "ordering" => [
                    "ordering_type" => "desc",
                    "ordering_by" => "created_at"
                ],
                "pagination" => [
                    "page_size" => 1,
                    "page_number" => 1
                ],
                "search" => [
                    "partner_order_ids" => [$partnerOrderId] // ← Using partner_order_id instead of order_ids
                ],
                "language" => "en"
            ]);

        $json = $response->json();

        if ($json['status'] !== 'ok' || empty($json['data']['orders'][0])) {
            Log::error("❌ Could not fetch booking from RateHawk for partner_order_id: $partnerOrderId", [
                'response' => $json
            ]);
            return false;
        }

        $bookingData = $json['data']['orders'][0];

        // 3. Send the email
        Mail::to($storedBooking->user_email)
            ->send(new BookingConfirmationEmail($bookingData));

        Log::info("📧 Booking confirmation email sent to {$storedBooking->user_email} for partner_order_id: $partnerOrderId");

        return true;
    }

    //user agent
    public function bookingHistory(Request $request)
    {
        $user = auth()->user();

        // Make sure we are querying correctly
        $query = MjellmaBooking::query();

        $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('agent_id', $user->id);
        });

        if ($status = $request->input('status')) {
            $query->where('pcb_status', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        // If you also want to get live statuses from API
        $orderIds = $bookings->pluck('order_id')->filter()->values()->map(function ($id) {
            return (int) $id;
        })->toArray();

        $statuses = [];

        if (!empty($orderIds)) {
            $response = Http::withOptions($this->httpOptions)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/info/', [
                    'ordering' => [
                        'ordering_type' => 'desc',
                        'ordering_by' => 'created_at'
                    ],
                    'pagination' => [
                        'page_size' => count($orderIds),
                        'page_number' => 1
                    ],
                    'search' => [
                        'order_ids' => $orderIds
                    ],
                    'language' => 'en'
                ]);

            $data = $response->json();
            if (isset($data['data']['orders'])) {
                foreach ($data['data']['orders'] as $order) {
                    $statuses[$order['order_id']] = $order['status'];
                }
            }
        }

        return view('User::frontend.bookingHistory', [
            'bookings' => $bookings,
            'statuses' => $statuses,
            'statues' => config('booking.statuses'), // Typo kept if your blade still expects 'statues'
        ]);
    }

    public function showBookingInvoice(Request $request, $orderId)
    {
        $response = Http::withOptions($this->httpOptions)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/info/', [
                "ordering" => [
                    "ordering_type" => "desc",
                    "ordering_by" => "created_at"
                ],
                "pagination" => [
                    "page_size" => 1,
                    "page_number" => 1
                ],
                "search" => [
                    "order_ids" => [(int) $orderId]
                ],
                "language" => "en"
            ]);

        $json = $response->json();

        if ($json['status'] === 'ok' && isset($json['data']['orders'][0])) {
            $booking = json_decode(json_encode($json['data']['orders'][0]), true); // force array
            $service = null;

            if (!empty($booking['object_model']) && !empty($booking['object_id'])) {
                $serviceModel = get_bookable_service_by_model($booking['object_model']);
                if ($serviceModel && $modelInstance = $serviceModel::find($booking['object_id'])) {
                    $service = $modelInstance;
                }
            }

            return view('Hotel::admin.invoice', [
                'booking' => $booking,
                'service' => $service
            ]);
        }

        return redirect()->back()->with('error', $json['error'] ?? 'Unable to fetch invoice.');
    }


}
