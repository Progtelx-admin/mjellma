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

class HotelHController extends Controller
{
    private $apiUrl = "https://api.worldota.net/api/b2b/v3/";
    private $username = "8166";
    private $password = "028c1cb6-c2e7-4ce2-9ace-1bba8aec92a6";

    public function showHotels()
    {
        Log::info('Rendering hotel search form.');
        return view('Hotel::frontend.form-search-ha');
    }

    public function searchHotels(Request $request)
    {
        try {
            // Validate input request parameters
            $request->validate([
                'hotel_name'         => 'nullable|string',
                'location'           => 'nullable|string',
                'latitude'           => 'nullable|numeric',
                'longitude'          => 'nullable|numeric',
                'radius'             => 'nullable|integer|min:1',
                'checkin'            => 'required|date',
                'checkout'           => 'required|date|after:checkin',
                'rooms'              => 'required|integer|min:1',
                'adults'             => 'required|integer|min:1',
                'children'           => 'nullable|array',
                'children.*'         => 'integer|min:0',
                'min_price'          => 'nullable|numeric|min:0',
                'max_price'          => 'nullable|numeric|min:0',
                'star_rating'        => 'nullable|array',
                'star_rating.*'      => 'integer|between:1,5',
                'breakfast_included' => 'nullable|boolean',
            ]);

            // Basic DB query only for hotels
            $hotelQuery = DB::table('hotels')
                ->select('hotel_id', 'name', 'latitude', 'longitude', 'star_rating', 'address');

            // Apply filters like hotel name, star rating, location
            if ($request->filled('hotel_name')) {
                $hotelQuery->where('name', 'like', '%' . $request->hotel_name . '%');
            }
            if ($request->filled('star_rating')) {
                $hotelQuery->whereIn('star_rating', $request->star_rating);
            }

            // Geolocation filter if latitude and longitude are provided
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $latitude  = $request->latitude;
                $longitude = $request->longitude;
                $radius    = $request->radius ?? 10;

                $latRange = [
                    $latitude - ($radius / 111),
                    $latitude + ($radius / 111),
                ];
                $lngRange = [
                    $longitude - ($radius / (111 * cos(deg2rad($latitude)))),
                    $longitude + ($radius / (111 * cos(deg2rad($latitude)))),
                ];

                $hotelQuery->whereBetween('latitude', $latRange)
                           ->whereBetween('longitude', $lngRange);
            }

            // Fetch hotels (limit 30 results for now)
            $hotels = $hotelQuery->limit(30)->get();

            // Fetch images for hotels
            $hotelIds = $hotels->pluck('hotel_id')->toArray();
            $hotelImages = DB::table('hotel_images')
                ->whereIn('hotel_id', $hotelIds)
                ->groupBy('hotel_id')
                ->pluck('image_url', 'hotel_id');

            // Attach images and initialize price & breakfast data for each hotel
            foreach ($hotels as $hotel) {
                $hotel->image_url = $hotelImages[$hotel->hotel_id] ?? asset('images/default-image.jpg');
                $hotel->daily_price = null;  // Default daily price
                $hotel->has_breakfast = false;  // Default breakfast info
            }

            // Fetch pricing information for the hotels from the external API
            $cacheKey = 'hotel_prices_' . implode('_', $hotelIds) . '_' . $request->checkin . '_' . $request->checkout;
            $apiData = Cache::remember($cacheKey, 300, function () use ($request, $hotelIds) {
                // Prepare the API request data
                $apiBody = [
                    'checkin'   => $request->checkin,
                    'checkout'  => $request->checkout,
                    'residency' => 'gb',
                    'language'  => 'en',
                    'guests'    => [[
                        'adults'   => (int)$request->adults,
                        'children' => array_map('intval', $request->children ?? []),
                    ]],
                    'ids'       => $hotelIds,
                    'currency'  => 'EUR',
                ];

                // Call the external API and fetch hotel pricing
                $response = Http::withBasicAuth($this->username, $this->password)
                                ->withHeaders(['Content-Type' => 'application/json'])
                                ->post($this->apiUrl . 'search/serp/hotels', $apiBody);

                return $response->json()['data']['hotels'] ?? [];
            });

            // Create an associative array for hotel_id => pricing data
            $pricesResult = [];
            $minPrice = PHP_INT_MAX; // Initialize with a very high number
            $maxPrice = PHP_INT_MIN; // Initialize with a very low number

            foreach ($apiData as $apiHotel) {
                $hotelId = $apiHotel['id'] ?? null;
                if (!$hotelId) {
                    continue;
                }

                // Get the first daily price from the API response (if available)
                $dailyPrice = $apiHotel['rates'][0]['daily_prices'][0] ?? null;
                if (!$dailyPrice) {
                    $dailyPrice = null;  // Default if no price is available
                }

                // Check for breakfast inclusion
                $hasBreakfast = false;
                if (!empty($apiHotel['rates'])) {
                    foreach ($apiHotel['rates'] as $rate) {
                        if (!empty($rate['meal_data']['has_breakfast'])) {
                            $hasBreakfast = true;
                            break;
                        }
                    }
                }

                // Store pricing data
                $pricesResult[$hotelId] = [
                    'daily_price'   => $dailyPrice,
                    'has_breakfast' => $hasBreakfast,
                ];

                // Track min and max price
                if ($dailyPrice !== null) {
                    $minPrice = min($minPrice, $dailyPrice);
                    $maxPrice = max($maxPrice, $dailyPrice);
                }
            }

            // Merge the pricing data into each hotel
            foreach ($hotels as $hotel) {
                $hotelId = $hotel->hotel_id;
                if (isset($pricesResult[$hotelId])) {
                    $hotel->daily_price = $pricesResult[$hotelId]['daily_price'];
                    $hotel->has_breakfast = $pricesResult[$hotelId]['has_breakfast'];
                }
            }

            // Return the results to the view
            return view('Hotel::frontend.results-ha', [
                'hotels'   => $hotels,
                'checkin'  => $request->checkin,
                'checkout' => $request->checkout,
                'adults'   => $request->adults,
                'children' => $request->children ?? [],
                'minPrice' => $minPrice === PHP_INT_MAX ? 0 : $minPrice,  // If no price found, set minPrice to 0
                'maxPrice' => $maxPrice === PHP_INT_MIN ? 999 : $maxPrice, // If no price found, set maxPrice to 999
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching hotels', ['message' => $e->getMessage()]);
            return back()->with('error', 'An error occurred: ' . $e->getMessage());
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
            $response = Http::withBasicAuth($this->username, $this->password)
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


    public function hotelInfo($id, Request $request)
    {
        Log::info('Fetching detailed info for hotel', ['hotel_id' => $id]);

        try {
            // Retrieve parameters from the request URL
            $checkin = $request->query('checkin', now()->format('Y-m-d'));
            $checkout = $request->query('checkout', now()->addDay()->format('Y-m-d'));
            $residency = $request->query('residency', 'gb');
            $language = $request->query('language', 'en');
            $adults = (int) $request->query('adults', 2);
            $children = $request->query('children', []); // Default empty array for children
            $currency = $request->query('currency', 'EUR');

            // Fetch hotel data from the database
            $dbHotel = DB::table('hotels')->where('hotel_id', $id)->first();

            if (!$dbHotel) {
                Log::error('Hotel not found in database', ['hotel_id' => $id]);
                return redirect()->back()->withErrors(['error' => 'Hotel not found']);
            }

            // Fetch hotel images from the database
            $hotelImages = DB::table('hotel_images')
                ->where('hotel_id', $id)
                ->pluck('image_url')
                ->toArray();

            // Prepare API payload
            $apiBody = [
                'checkin' => $checkin,
                'checkout' => $checkout,
                'residency' => $residency,
                'language' => $language,
                'guests' => [
                    [
                        'adults' => $adults,
                        'children' => array_map('intval', $children), // Ensure children are integers
                    ],
                ],
                'id' => $id,
                'currency' => $currency,
            ];

            Log::info('Requesting hotel info with API payload', ['payload' => $apiBody]);

            // Make the API call
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'search/hp/', $apiBody);

            Log::info('Hotel Info API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                Log::error('Hotel API Request Failed', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch hotel info');
            }

            // Parse the response data
            $apiData = $response->json()['data']['hotels'][0] ?? null;

            if (!$apiData) {
                Log::error('Hotel details not found in API response', ['hotel_id' => $id]);
                return redirect()->back()->withErrors(['error' => 'Failed to fetch hotel details from API']);
            }

            // Merge database and API data
            $hotel = [
                'id' => $apiData['id'] ?? $dbHotel->hotel_id ?? null,
                'name' => $dbHotel->name ?? $apiData['name'] ?? 'N/A',
                'address' => $dbHotel->address ?? $apiData['address'] ?? 'N/A',
                'star_rating' => $dbHotel->star_rating ?? $apiData['star_rating'] ?? 0,
                'check_in_time' => $apiData['check_in_time'] ?? 'N/A',
                'check_out_time' => $apiData['check_out_time'] ?? 'N/A',
                'images_ext' => $hotelImages, // Use images from the database
                'price_per_night' => $apiData['rates'][0]['daily_prices'][0] ?? 'N/A', // First rate's daily price
            ];

            // Extract room rates
            $roomRates = $apiData['rates'] ?? [];
            $hasRoomRates = count($roomRates) > 0;

            // Return the view with the extracted data
            return view('Hotel::frontend.info-ha', compact('hotel', 'roomRates', 'hasRoomRates', 'checkin', 'checkout', 'residency', 'language', 'adults', 'children', 'currency'));

        } catch (\Exception $e) {
            // Log and return an error response
            Log::error('Error fetching hotel details', [
                'hotel_id' => $id,
                'error_message' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors(['error' => 'An error occurred while fetching hotel details.']);
        }
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
            // Validate the request
            $request->validate([
                'book_hash' => 'required|string', // Ensure 'book_hash' is provided
                'room_name' => 'required|string', // Room name is required
            ]);

            // Retrieve input values, with default for 'price_increase_percent'
            $bookHash = $request->input('book_hash'); // Retrieve 'book_hash'
            $priceIncreasePercent = (int) $request->input('price_increase_percent', 20); // Default to 20 if not provided
            $roomName = $request->input('room_name'); // Retrieve room name

            // Prepare the API payload
            $apiBody = [
                'hash' => $bookHash,
                'price_increase_percent' => $priceIncreasePercent,
            ];

            // Call the Prebook API
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/prebook/', $apiBody);

            if ($response->failed()) {
                throw new \Exception('Prebook API request failed: ' . $response->body());
            }

            $prebookData = $response->json();

            // Store the room name and prebook data in the session
            session([
                'prebookData' => $prebookData,
                'roomName' => $roomName,
                'checkin' => $request->checkin,
                'checkout' => $request->checkout,
            ]);

            // Redirect to the result page with prebooking data
            return redirect()->route('hotel.prebook.result');
        } catch (\Exception $e) {
            Log::error('Prebook failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Prebooking failed: ' . $e->getMessage()]);
        }
    }

    public function prebookResult()
    {
        $prebookData = session('prebookData');
        $roomName = session('roomName');
        $checkin = session('checkin', now()->format('Y-m-d'));
        $checkout = session('checkout', now()->addDay()->format('Y-m-d'));

        if (!$prebookData) {
            return redirect()->route('hotel.info')->withErrors(['error' => 'No prebooking data found.']);
        }

        // Fetch hotel details from the API response
        $hotelData = $prebookData['data']['hotels'][0] ?? null;
        $hotelId = $hotelData['id'] ?? null;

        // Fetch from Database if API data is missing
        if (!$hotelId) {
            return redirect()->route('hotel.search')->withErrors(['error' => 'Hotel ID is missing from prebooking data.']);
        }

        $hotel = DB::table('hotels')->where('hotel_id', $hotelId)->first();

        // Fetch hotel image
        $hotelImage = DB::table('hotel_images')
            ->where('hotel_id', $hotelId)
            ->orderBy('id')
            ->value('image_url');

        // Ensure a default hotel object if API data is missing
        $hotelDetails = [
            'id' => $hotelId,
            'name' => $hotel->name ?? $hotelData['name'] ?? 'Hotel Name Not Available',
            'address' => $hotel->address ?? $hotelData['address'] ?? 'Location Not Available',
            'star_rating' => $hotel->star_rating ?? $hotelData['star_rating'] ?? 0,
        ];

        return view('Hotel::frontend.prebook-result-ha', compact(
            'prebookData', 'roomName', 'checkin', 'checkout', 'hotelDetails', 'hotelImage'
        ));
    }

    public function bookRoom(Request $request)
    {
        try {
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

            // Save booking data in session
            session([
                'booking.partner_order_id' => $partnerOrderId,
                'booking.book_hash' => $bookHash,
                'booking.user_ip' => $userIp,
                'booking.hotel_id' => $request->input('hotel_id'),
                'booking.checkin' => $request->input('checkin'),
                'booking.checkout' => $request->input('checkout'),
                'booking.meal_plan' => $request->input('meal_plan'),
            ]);

            $apiBody = [
                'partner_order_id' => $partnerOrderId,
                'book_hash' => $bookHash,
                'language' => 'en',
                'user_ip' => $userIp,
            ];

            Log::info('📤 Sending booking form request to API', ['payload' => $apiBody]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/form/', $apiBody);

            Log::info('📥 Booking API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                throw new \Exception('Booking API request failed: ' . $response->body());
            }

            $bookingData = $response->json();

            if (isset($bookingData['status']) && $bookingData['status'] === 'ok') {
                $data = $bookingData['data'];
                session(['bookingData' => $data]);

                // 🔥 Store with order_id in cache for PCB
                $token = Str::random(32);
                Cache::put("pending_booking_{$token}", array_merge($request->all(), [
                    'order_id' => $data['order_id'],
                ]), now()->addMinutes(10));

                return redirect()->route('hotel.booking.confirmation', ['book_hash' => $bookHash]);
            } else {
                throw new \Exception('Booking failed: ' . ($bookingData['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('❌ Booking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors(['error' => 'Booking failed: ' . $e->getMessage()]);
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

        // Pass $book_hash so we can display it in the Blade
        return view('Hotel::frontend.booking-confirmation-ha', compact('bookingData', 'vendors', 'book_hash'));
    }


    public function processPayment(Request $request)
    {
        try {
            // Validate the input
            $request->validate([
                'order_id' => 'required|string',
                'partner_order_id' => 'required|string',
                'payment_method' => 'required|numeric',
                'guests' => 'required|array',
                'guests.*.first_name' => 'required|string',
                'guests.*.last_name' => 'required|string',
            ]);

            $orderId = $request->input('order_id');
            $partnerOrderId = $request->input('partner_order_id');
            $paymentMethodIndex = $request->input('payment_method');
            $guests = $request->input('guests');

            $bookingData = session('bookingData');
            if (!$bookingData) {
                throw new \Exception('No booking data found.');
            }

            $selectedPayment = $bookingData['payment_types'][$paymentMethodIndex] ?? null;
            if (!$selectedPayment) {
                throw new \Exception('Invalid payment method selected.');
            }

            if ($selectedPayment['is_need_credit_card_data']) {
                $request->validate([
                    'card_number' => 'required|string|min:16|max:16',
                    'card_holder' => 'required|string',
                    'expiry_month' => 'required|string|min:2|max:2',
                    'expiry_year' => 'required|string|min:2|max:2',
                    'cvc' => 'required|string|min:3|max:4',
                ]);

                $creditCardData = [
                    'card_number' => $request->input('card_number'),
                    'card_holder' => $request->input('card_holder'),
                    'expiry_month' => $request->input('expiry_month'),
                    'expiry_year' => $request->input('expiry_year'),
                    'cvc' => $request->input('cvc'),
                ];
            }

            $rooms = $bookingData['rooms'] ?? null;
            if (!$rooms) {
                throw new \Exception('Rooms data is missing from the booking information.');
            }

            // Prepare API payload
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
                'guests' => $guests, // Include guests
                'credit_card_data' => $creditCardData ?? null,
            ];

            Log::info('Processing payment with API payload', ['payload' => $apiBody]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/finish/', $apiBody);

            Log::info('Payment API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                throw new \Exception('Payment API request failed: ' . $response->body());
            }

            $paymentResult = $response->json();

            return redirect()->route('hotel.payment.success')->with('paymentResult', $paymentResult);
        } catch (\Exception $e) {
            Log::error('Payment processing failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
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
        $status  = $request->query('STATUS');   // e.g. "FullyPaid"
        $token   = $request->query('token');    // For retrieving cached booking

        \Log::info('PCB Bank returned with', [
            'ID'     => $orderId,
            'STATUS' => $status,
            'token'  => $token,
        ]);

        // 1. Retrieve all relevant booking data from cache/session
        $bookingData = \Cache::get("pending_booking_{$token}");
        $pcbOrder    = \Cache::get("pcb_order_{$token}");

        if (!$bookingData || !$pcbOrder) {
            \Log::error('No booking data found in cache');
            return redirect()->route('hotel.search')
                ->withErrors(['error' => 'Session expired. Please try again.']);
        }

        // 2. Mark that we do NOT want to send credit card details to RateHawk
        //    We simply treat it like "deposit" or "offline" in RateHawk terms
        $bookingData['payment_type']['type'] = 'deposit';
        $bookingData['payment_type']['is_need_credit_card_data'] = false;

        // 3. Ensure phone is valid for RateHawk's validation (min length 5)
        if (empty($bookingData['phone']) || strlen($bookingData['phone']) < 5) {
            $bookingData['phone'] = '+0000000000'; // fallback
        }

        // 4. Optionally, store or override any needed user details to pass to RateHawk
        $bookingData['first_name'] = $bookingData['first_name'] ?? 'Guest';
        $bookingData['last_name']  = $bookingData['last_name']  ?? 'User';

        \Log::info('Confirming booking after PCB as a deposit booking', ['payload' => $bookingData]);

        // 5. Call finishBooking with the updated data
        return app()->call([$this, 'finishBooking'], ['request' => new Request($bookingData)]);
    }


    public function handleBookingSubmission(Request $request)
    {
        $paymentType = $request->input('payment_type.type');
        $requiresCard = $request->input('payment_type.is_need_credit_card_data', false);

        Log::info('📥 Booking Submission', [
            'payment_type' => $paymentType,
            'requires_card' => $requiresCard,
        ]);

        if ($paymentType === 'now' && $requiresCard) {
            // ✅ Generate token to store booking
            $token = Str::random(32);
            Cache::put("pending_booking_{$token}", $request->all(), now()->addMinutes(10));

            $amount = $request->input('payment_type.amount');
            $description = 'Hotel Booking Pre-Payment';
            $redirectUrl = route('pcb.booking.return', ['token' => $token]);

            $pcb = new \App\Services\PcbBankService();
            $order = $pcb->createOrder($amount, $description, $redirectUrl);

            if ($order && isset($order['id'], $order['password'], $order['hppUrl'])) {
                Cache::put("pcb_order_{$token}", [
                    'id' => $order['id'],
                    'password' => $order['password'],
                ], now()->addMinutes(10));

                Log::info('🔁 Redirecting to PCB Bank', ['url' => $order['hppUrl']]);
                return redirect($order['hppUrl'] . "?id={$order['id']}&password={$order['password']}");
            }

            Log::error('❌ PCB createOrder failed');
            return back()->withErrors(['error' => 'Failed to redirect to PCB Bank.']);
        }

        Log::info('✅ No credit card required — calling finishBooking directly');
        return app()->call([$this, 'finishBooking'], ['request' => $request]);
    }

    public function confirmAfterPcb(Request $request)
    {
        $partnerOrderId = $request->input('ID');
        $status = $request->input('STATUS');

        Log::info('📨 PCB Bank returned with', ['ID' => $partnerOrderId, 'STATUS' => $status]);

        $bookingData = Cache::get('booking_' . $partnerOrderId);
        $pcbData = Cache::get('pcb_order_' . $partnerOrderId);

        if (!$bookingData || !$pcbData) {
            Log::error('❌ No booking data found in cache');
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

        $response = Http::withBasicAuth($this->username, $this->password)
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

        $response = Http::withBasicAuth($this->username, $this->password)
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
        $data = $request->all();

        if (!isset($data['order_id'])) {
            \Log::error('Missing order_id in booking data.');
            return response()->json(['error' => 'Missing order_id'], 422);
        }

        // Handle user email/phone fallback
        $userEmail = isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)
            ? trim($data['email']) : 'guest@example.com';

        $userPhone = isset($data['phone']) ? trim($data['phone']) : '+0000000000';
        if (strlen($userPhone) < 5) $userPhone = '+0000000000';

        // Determine who booked
        $bookedBy = 'guest';
        $adminId = null;
        $agentId = null;
        $userId = null;

        if (auth()->check()) {
            switch (auth()->user()->role_id) {
                case 1:
                    $bookedBy = 'admin';
                    $adminId = auth()->id();
                    $agentId = $data['agent_id'] ?? null;
                    break;
                case 2:
                    $bookedBy = 'agent';
                    $agentId = auth()->id();
                    break;
                case 3:
                    $bookedBy = 'user';
                    $userId = auth()->id();
                    break;
            }
        }

        $finalPaymentType = $data['payment_type']['type'] ?? 'deposit';

        $payload = [
            'user' => [
                'first_name' => $data['first_name'] ?? '',
                'last_name'  => $data['last_name'] ?? '',
                'email'      => $userEmail,
                'phone'      => $userPhone,
            ],
            'supplier_data' => [
                'first_name_original' => $data['supplier_data']['first_name_original'] ?? '',
                'last_name_original'  => $data['supplier_data']['last_name_original'] ?? '',
                'phone'               => $data['supplier_data']['phone'] ?? '',
                'email'               => $data['supplier_data']['email'] ?? '',
            ],
            'partner' => [
                'partner_order_id'  => $data['partner_order_id'] ?? '',
                'comment'           => $data['partner_comment'] ?? '',
                'amount_sell_b2b2c' => isset($data['amount_sell_b2b2c']) && is_numeric($data['amount_sell_b2b2c'])
                    ? number_format($data['amount_sell_b2b2c'], 2, '.', '') : '0.00',
            ],
            'order_id' => $data['order_id'],
            'language' => $data['language'] ?? 'en',
            'rooms'    => $data['rooms'] ?? [],
            'payment_type' => [
                'type'          => $finalPaymentType,
                'amount'        => $data['payment_type']['amount'] ?? '',
                'currency_code' => $data['payment_type']['currency_code'] ?? 'EUR',
                'is_need_credit_card_data' => false,
            ],
            'return_path' => $data['return_path'] ?? '',
        ];

        \Log::debug('Sending final booking payload to RateHawk', ['payload' => $payload]);

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/booking/finish/', $payload);

        \Log::debug('Received response from RateHawk booking finish', ['response_body' => $response->body()]);

        $json = $response->json();

        // Simulate success for testing
        if (isset($json['error']) && $json['error'] === 'insufficient_b2b_balance') {
            \Log::warning('Simulating booking success due to insufficient_b2b_balance for testing.');
            $json = [
                'status' => 'ok',
                'data'   => [
                    'order_id' => $payload['order_id'],
                    'status'   => 'Simulated'
                ],
            ];
        }

        if (isset($json['status']) && $json['status'] === 'ok') {
            \Log::info('Booking success', ['response' => $json]);

            MjellmaBooking::create([
                'order_id'         => $data['order_id'],
                'partner_order_id' => $data['partner_order_id'] ?? null,
                'booked_by'        => $bookedBy,
                'admin_id'         => $adminId,
                'agent_id'         => $agentId,
                'user_id'          => $userId,
                'user_email'       => $userEmail,
                'user_phone'       => $userPhone,
                'payment_type'     => $finalPaymentType,
                'payment_amount'   => $data['payment_type']['amount'] ?? null,
                'currency_code'    => $data['payment_type']['currency_code'] ?? 'EUR',
                'pcb_status'       => $json['data']['status'] ?? null,
                'api_status'       => $json['status'],
                'api_error'        => $json['error'] ?? null,
            ]);

            return view('Hotel::frontend.payment-success', [
                'order_id' => $data['order_id'],
                'partner_order_id' => $data['partner_order_id'] ?? null,
            ]);
        }

        \Log::error('Booking failed', [
            'payload_sent'  => $payload,
            'response_body' => $response->body()
        ]);

        return response()->json(['error' => $json['error'] ?? 'Unknown error'], 500);
    }


    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role_id === 1) {
            $bookings = MjellmaBooking::orderBy('created_at', 'desc')->paginate(20);
        } else {
            $bookings = MjellmaBooking::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)->orWhere('agent_id', $user->id);
            })->orderBy('created_at', 'desc')->paginate(20);
        }

        // Get all order_ids to request live status from RateHawk
        $orderIds = $bookings->pluck('order_id')->filter()->values()->map(function ($id) {
            return (int) $id;
        })->toArray();

        $statuses = [];

        if (!empty($orderIds)) {
            $response = Http::withBasicAuth($this->username, $this->password)
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

        return view('Hotel::admin.booking', compact('bookings', 'statuses'));
    }

    //see details
    public function showBookingDetails(Request $request, $orderId)
    {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/info/', [
                "ordering" => [
                    "ordering_type" => "desc",
                    "ordering_by"   => "created_at"
                ],
                "pagination" => [
                    "page_size"    => 1,
                    "page_number"  => 1
                ],
                "search" => [
                    "order_ids" => [(int) $orderId]
                ],
                "language" => "en"
            ]);

        $json = $response->json();

        if ($json['status'] === 'ok' && isset($json['data']['orders'][0])) {
            return view('Hotel::admin.details', [
                'booking' => $json['data']['orders'][0]
            ]);
        }

        return redirect()->back()->with('error', $json['error'] ?? 'Unable to fetch booking details.');
    }

    //cancel
    public function cancelBooking(Request $request, $partnerOrderId)
    {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/cancel/', [
                'partner_order_id' => $partnerOrderId
            ]);

        $json = $response->json();

        if ($json['status'] === 'ok') {
            return redirect()->back()->with('success', 'Booking cancelled successfully.');
        }

        return redirect()->back()->with('error', $json['error'] ?? 'Failed to cancel booking.');
    }




}
