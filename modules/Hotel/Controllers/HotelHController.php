<?php

namespace Modules\Hotel\Controllers;

use App\Http\Controllers\Controller;
use Modules\Hotel\Models\HotelH;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use DB;
use Illuminate\Support\Facades\Cache;

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

            // Basic DB query only
            $hotelQuery = DB::table('hotels')
                ->select('hotel_id', 'name', 'latitude', 'longitude', 'star_rating', 'address');

            // Filtering: name, star rating, location...
            if ($request->filled('hotel_name')) {
                $hotelQuery->where('name', 'like', '%' . $request->hotel_name . '%');
            }
            if ($request->filled('star_rating')) {
                $hotelQuery->whereIn('star_rating', $request->star_rating);
            }

            // Example bounding-box geolocation filter (optional)
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

                // Add distance calculation if desired
            }

            // We'll just get all results (or do offset/limit if you have infinite scroll)
            $hotels = $hotelQuery->limit(30)->get();

            // We also fetch images from DB, if available
            $hotelIds = $hotels->pluck('hotel_id')->toArray();
            $hotelImages = DB::table('hotel_images')
                ->whereIn('hotel_id', $hotelIds)
                ->groupBy('hotel_id')
                ->pluck('image_url', 'hotel_id');

            // Attach images to each hotel
            foreach ($hotels as $hotel) {
                $hotel->image_url = $hotelImages[$hotel->hotel_id]
                    ?? asset('images/default-image.jpg');
                // We'll set daily_price = null for now
                $hotel->daily_price = null;
                $hotel->has_breakfast = false;
            }

            // If we want to do any server-side filtering (breakfast_included, min_price, max_price),
            // we must do that AFTER we fetch actual rates. Instead, we do it in fetchHotelPrices()
            // or in the front-end.

            // Return the Blade with the basic hotels
            return view('Hotel::frontend.results-ha', [
                'hotels'    => $hotels,
                'checkin'   => $request->checkin,
                'checkout'  => $request->checkout,
                'adults'    => $request->adults,
                'children'  => $request->children ?? [],
                'minPrice'  => 0,   // We can set defaults for now
                'maxPrice'  => 999, // ...
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching hotels', ['message' => $e->getMessage()]);
            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    // 2) New method: fetchHotelPrices => returns pricing in JSON
    public function fetchHotelPrices(Request $request)
    {
        try {
            $request->validate([
                'ids'      => 'required|array',
                'ids.*'    => 'integer',
                'checkin'  => 'required|date',
                'checkout' => 'required|date|after:checkin',
                'adults'   => 'required|integer|min:1',
            ]);

            $hotelIds = $request->ids;

            // Check for cached API results
            $cacheKey = 'hotel_prices_' . implode('_', $hotelIds) . '_' . $request->checkin . '_' . $request->checkout;
            $apiData = Cache::remember($cacheKey, 300, function () use ($request, $hotelIds) {
                // Make the external API call
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

                $response = Http::withBasicAuth($this->username, $this->password)
                                ->withHeaders(['Content-Type' => 'application/json'])
                                ->post($this->apiUrl . 'search/serp/hotels', $apiBody);

                return $response->json()['data']['hotels'] ?? [];
            });

            // Build a response array keyed by hotel_id => {daily_price, has_breakfast, ...}
            $pricesResult = [];

            foreach ($apiData as $apiHotel) {
                $hotelId = $apiHotel['id'] ?? null;
                if (!$hotelId) {
                    continue;
                }

                // Get the first daily price from the array (if available)
                $dailyPrice = $apiHotel['rates'][0]['daily_prices'][0] ?? null;

                // Default to null if no price is available
                if (!$dailyPrice) {
                    $dailyPrice = null;
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

                $pricesResult[$hotelId] = [
                    'daily_price'   => $dailyPrice,
                    'has_breakfast' => $hasBreakfast,
                ];
            }

            return response()->json([
                'success' => true,
                'prices'  => $pricesResult,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching hotel prices', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
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
            // Validate the request
            $request->validate([
                'book_hash' => 'required|string',
                'partner_order_id' => 'required|string',
                'user_ip' => 'required|ip',
            ]);

            $bookHash = $request->input('book_hash');
            $partnerOrderId = $request->input('partner_order_id');
            $userIp = $request->input('user_ip');

            // Prepare the API payload
            $apiBody = [
                'partner_order_id' => $partnerOrderId,
                'book_hash' => $bookHash,
                'language' => 'en',
                'user_ip' => $userIp,
            ];

            Log::info('Requesting booking with API payload', ['payload' => $apiBody]);

            // Call the Booking API
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/form/', $apiBody);

            Log::info('Booking API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                throw new \Exception('Booking API request failed: ' . $response->body());
            }

            $bookingData = $response->json();

            // Check if booking was successful and has relevant data
            if (isset($bookingData['status']) && $bookingData['status'] === 'ok') {
                // Store the bookingData in session
                session(['bookingData' => $bookingData['data']]);

                // Redirect to booking confirmation, passing book_hash as a route param
                return redirect()->route('hotel.booking.confirmation', ['book_hash' => $bookHash]);
            } else {
                throw new \Exception('Booking failed: ' . ($bookingData['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Booking failed', [
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

    public function finishBooking(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'partner_order_id' => 'required|string',
                'user.email' => 'required|email',
                'user.phone' => 'required|string',
                'supplier_data.first_name_original' => 'required|string',
                'supplier_data.last_name_original' => 'required|string',
                'supplier_data.phone' => 'required|string',
                'supplier_data.email' => 'required|email',
                'rooms' => 'required|array',
                'rooms.*.guests' => 'required|array',
                'rooms.*.guests.*.first_name' => 'required|string',
                'rooms.*.guests.*.last_name' => 'required|string',
                'payment_type.type' => 'required|string',
                'payment_type.amount' => 'required|string',
                'payment_type.currency_code' => 'required|string',
            ]);

            // Extract data from the request
            $data = $request->all();

            // Prepare the API payload
            $apiPayload = [
                'user' => $data['user'],
                'supplier_data' => $data['supplier_data'],
                'partner' => [
                    'partner_order_id' => $data['partner_order_id'],
                ],
                'language' => 'en',
                'rooms' => $data['rooms'],
                'payment_type' => $data['payment_type'],
            ];

            // Call the API
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . 'hotel/order/booking/finish/', $apiPayload);

            if ($response->failed()) {
                throw new \Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            // Handle the response
            if ($responseData['status'] === 'ok') {
                return redirect()->route('hotel.payment.success')->with('success', 'Booking completed successfully!');
            } else {
                throw new \Exception($responseData['error'] ?? 'Unknown error occurred.');
            }
        } catch (\Exception $e) {
            Log::error('Booking finish failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

}
