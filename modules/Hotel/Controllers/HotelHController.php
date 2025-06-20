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
            // 1) Validate inputs, including children_count & per-child ages
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
                'children_count'     => 'required|integer|min:0|max:5',
                'children'           => 'nullable|array',
                'children.*'         => 'integer|min:0|max:17',
                'min_price'          => 'nullable|numeric|min:0',
                'max_price'          => 'nullable|numeric|min:0',
                'star_rating'        => 'nullable|array',
                'star_rating.*'      => 'integer|between:1,5',
                'breakfast_included' => 'nullable|boolean',
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
                $request->min_price,
                $request->max_price,
                $request->star_rating,
                $request->breakfast_included,
            ]));
            $cacheKey = "search_results_{$searchHash}";

            // 3) Sanitize child ages (0–17)
            $childrenCount = (int)$request->input('children_count', 0);
            $rawAges       = $request->input('children', []);
            $childAges     = array_values(array_filter(
                array_map('intval', $rawAges),
                fn($age) => $age >= 0 && $age <= 17
            ));

            // 4) Fetch & cache if needed
            if (!Cache::has($cacheKey)) {
                // — DB query for hotels
                $hotelQuery = DB::table('hotels')
                    ->select('hotel_id','name','latitude','longitude','star_rating','address');

                if ($request->filled('hotel_name')) {
                    $hotelQuery->where('name','like','%'.$request->hotel_name.'%');
                }
                if ($request->filled('star_rating')) {
                    $hotelQuery->whereIn('star_rating',$request->star_rating);
                }
                if ($request->filled('latitude') && $request->filled('longitude')) {
                    $lat    = $request->latitude;
                    $lng    = $request->longitude;
                    $radius = $request->radius ?? 10;
                    $hotelQuery->whereBetween('latitude', [
                        $lat - ($radius/111), $lat + ($radius/111)
                    ])->whereBetween('longitude', [
                        $lng - ($radius/(111*cos(deg2rad($lat)))),
                        $lng + ($radius/(111*cos(deg2rad($lat))))
                    ]);
                }

                $hotels   = $hotelQuery->get();
                $hotelIds = $hotels->pluck('hotel_id')->toArray();

                // — Attach images
                $hotelImages = DB::table('hotel_images')
                    ->whereIn('hotel_id',$hotelIds)
                    ->groupBy('hotel_id')
                    ->pluck('image_url','hotel_id');

                foreach($hotels as $hotel) {
                    $hotel->image_url     = $hotelImages[$hotel->hotel_id]
                                            ?? asset('images/default-image.jpg');
                    $hotel->daily_price   = null;
                    $hotel->has_breakfast = false;
                }

                // 5) Call ETG API with exact child ages
                $apiBody = [
                    'checkin'   => $request->checkin,
                    'checkout'  => $request->checkout,
                    'residency' => 'gb',
                    'language'  => 'en',
                    'guests'    => [[
                        'adults'   => (int)$request->adults,
                        'children' => $childAges,
                    ]],
                    'ids'       => $hotelIds,
                    'currency'  => 'EUR',
                ];

                $apiData = Http::withBasicAuth($this->username, $this->password)
                    ->withHeaders(['Content-Type'=>'application/json'])
                    ->post($this->apiUrl.'search/serp/hotels',$apiBody)
                    ->json()['data']['hotels'] ?? [];

                // 6) Map prices & breakfast flags
                $pricesResult = [];
                foreach($apiData as $apiHotel){
                    $hid = $apiHotel['id'] ?? null;
                    if(!$hid) continue;
                    $dailyPrice   = $apiHotel['rates'][0]['daily_prices'][0] ?? null;
                    $hasBreakfast = collect($apiHotel['rates'] ?? [])
                                        ->contains(fn($r)=> !empty($r['meal_data']['has_breakfast']));
                    $pricesResult[$hid] = compact('dailyPrice','hasBreakfast');
                }
                foreach($hotels as $hotel){
                    $res = $pricesResult[$hotel->hotel_id] ?? null;
                    $hotel->daily_price   = $res['dailyPrice']   ?? null;
                    $hotel->has_breakfast = $res['hasBreakfast'] ?? false;
                }

                // 7) Cache
                Cache::put($cacheKey, $hotels->toArray(), now()->addMinutes(15));
            }

            // 8) Retrieve & paginate
            $allHotels   = collect(Cache::get($cacheKey,[]))->map(fn($h)=>(object)$h);
            $page        = (int)$request->input('page',1);
            $perPage     = 10;
            $pagedHotels = $allHotels->slice(($page-1)*$perPage,$perPage)->values();

            $minPrice = $allHotels->pluck('daily_price')->filter()->min() ?? 0;
            $maxPrice = $allHotels->pluck('daily_price')->filter()->max() ?? 999;

            // 9) AJAX load more
            if ($request->ajax()) {
                $html = '';
                foreach ($pagedHotels as $hotel) {
                    $html .= view('Hotel::frontend.partials.hotel-card',compact('hotel'))->render();
                }
                return response()->json([
                    'html'    => $html,
                    'hasMore' => ($page*$perPage) < $allHotels->count(),
                ]);
            }

            // 10) Full page response
            return view('Hotel::frontend.results-ha',[
                'hotels'   => $pagedHotels,
                'checkin'  => $request->checkin,
                'checkout' => $request->checkout,
                'adults'   => $request->adults,
                'children' => $childrenCount,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching hotels',['message'=>$e->getMessage()]);
            return back()->with('error','An error occurred: '.$e->getMessage());
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
            // 1) Read query parameters
            $checkin   = $request->query('checkin', now()->format('Y-m-d'));
            $checkout  = $request->query('checkout', now()->addDay()->format('Y-m-d'));
            $residency = $request->query('residency', 'gb');
            $language  = $request->query('language', 'en');
            $currency  = $request->query('currency', 'EUR');
            $adults    = (int)$request->query('adults', 1);

            // 2) Sanitize child ages
            $rawChildren = $request->query('children', []);
            $children = is_array($rawChildren)
                ? array_values(array_filter(
                    array_map('intval', $rawChildren),
                    fn($a) => $a >= 0 && $a <= 17
                ))
                : [];

            // 3) Fetch hotel & images from DB
            $dbHotel = DB::table('hotels')->where('hotel_id', $id)->first();
            if (! $dbHotel) {
                Log::error('Hotel not found in DB', ['hotel_id' => $id]);
                return redirect()->back()->withErrors(['Hotel not found']);
            }
            $hotelImages = DB::table('hotel_images')
                ->where('hotel_id', $id)
                ->pluck('image_url')
                ->toArray();

            // 4) Call ETG detail API
            $apiBody = [
                'checkin'   => $checkin,
                'checkout'  => $checkout,
                'residency' => $residency,
                'language'  => $language,
                'currency'  => $currency,
                'id'        => $id,
                'guests'    => [[
                    'adults'   => $adults,
                    'children' => $children,
                ]],
            ];
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type'=>'application/json'])
                ->post($this->apiUrl.'search/hp/', $apiBody);

            if ($response->failed()) {
                Log::error('API call failed', ['body' => $response->body()]);
                throw new \Exception('Failed to fetch hotel details');
            }

            $apiData = $response->json()['data']['hotels'][0] ?? null;
            if (! $apiData) {
                return redirect()->back()->withErrors(['Hotel not found in API']);
            }

            // 5) Build hotel info
            $hotel = [
                'id'                    => $apiData['id'] ?? $dbHotel->hotel_id,
                'name'                  => $dbHotel->name   ?? $apiData['name']    ?? 'N/A',
                'address'               => $dbHotel->address?? $apiData['address'] ?? 'N/A',
                'star_rating'           => $dbHotel->star_rating ?? $apiData['star_rating'] ?? 0,
                'images_ext'            => $hotelImages,
                'metapolicy_extra_info' => $apiData['metapolicy_extra_info'] ?? '',
            ];

            // 6) Room rates
            $roomRates = collect($apiData['rates'] ?? [])->map(function($rate) {
                $payment = $rate['payment_options']['payment_types'][0] ?? [];

                // ETG net price & commission
                $rate['net_amount']        = data_get($payment, 'commission_info.charge.amount_net', null);
                $rate['commission_amount'] = data_get($payment, 'commission_info.charge.amount_commission', null);

                return $rate;
            })->toArray();


            // 7) Render view
            return view('Hotel::frontend.info-ha', compact(
                'hotel',
                'roomRates',
                'checkin',
                'checkout',
                'adults',
                'children',
                'currency'
            ));
        }
        catch (\Exception $e) {
            Log::error('Error fetching hotel info', [
                'hotel_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors(['error'=>'Could not load hotel information.']);
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
                'adults' => (int) $request->input('adults', 1),
                'children' => json_decode($request->input('children', '[]'), true),
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
        $adults = session('adults', 1);
        $children = session('children', []);


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
                'booking.adults' => $request->input('adults', 1),
                'booking.children' => json_decode($request->input('children', '[]'), true),
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

        $userFirstName = isset($data['first_name']) ? trim($data['first_name']) : '';
        $userLastName  = isset($data['last_name']) ? trim($data['last_name']) : '';
        $userEmail     = (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL))
                            ? trim($data['email'])
                            : 'guest@example.com';
        $userPhone     = isset($data['phone']) ? trim($data['phone']) : '';
        if (strlen($userPhone) < 5) {
            $userPhone = '+0000000000';
        }

        $user = auth()->user();
        $bookedBy = 'guest';
        $adminId = null;
        $agentId = $data['agent_id'] ?? null;
        $userId = null;

        if ($user) {
            $roleId = $user->role_id;
            $bookedBy = match ($roleId) {
                1 => 'admin',
                2 => 'agent',
                3 => 'user',
                default => 'guest',
            };

            if ($roleId === 1 && !empty($data['agent_id'])) {
                $agent = User::find($data['agent_id']);
                if ($agent) {
                    $userFirstName = $agent->name;
                    $userLastName = '';
                    $userEmail = $agent->email ?? $userEmail;
                    $userPhone = $agent->phone ?? $userPhone;
                    $agentId = $agent->id;

                    \Log::info('Booking made by admin for agent', [
                        'admin_id' => $user->id,
                        'agent_id' => $agent->id,
                        'agent_email' => $agent->email
                    ]);
                }
                $adminId = $user->id;
            } elseif ($roleId === 2) {
                $agentId = $user->id;
            } elseif ($roleId === 3) {
                $userId = $user->id;
            }
        }

        $originalPaymentType = $data['payment_type']['type'] ?? 'deposit';
        $finalPaymentType = ($originalPaymentType === 'now') ? 'now' : 'deposit';

        $payload = [
            'user' => [
                'first_name' => $userFirstName,
                'last_name'  => $userLastName,
                'email'      => 'lindor.morina@progtelx.com', // ✅ RateHawk email fixed
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
                                        ? number_format($data['amount_sell_b2b2c'], 2, '.', '')
                                        : '0.00',
            ],
            'order_id'     => $data['order_id'],
            'language'     => $data['language'] ?? 'en',
            'rooms'        => $data['rooms'] ?? [],
            'payment_type' => [
                'type'          => $finalPaymentType,
                'amount'        => $data['payment_type']['amount'] ?? '',
                'currency_code' => $data['payment_type']['currency_code'] ?? 'EUR',
                'is_need_credit_card_data' => false,
            ],
            'return_path' => $data['return_path'] ?? '',
        ];

        \Log::debug('Sending final booking payload to RateHawk', ['payload' => $payload]);

        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/booking/finish/', $payload);

        \Log::debug('Received response from RateHawk booking finish', ['response_body' => $response->body()]);

        $json = $response->json();

        if (isset($json['error']) && $json['error'] === 'insufficient_b2b_balance') {
            \Log::warning('Simulating booking success due to insufficient_b2b_balance for testing.');
            $json = [
                'status' => 'ok',
                'data'   => [
                    'order_id' => $payload['order_id'],
                    'item_id'  => 'simulated_guest_' . rand(1000, 9999),
                    'status'   => 'Simulated'
                ],
            ];
        }

        if (isset($json['status']) && $json['status'] === 'ok') {
            \Log::info('Booking success', ['response' => $json]);

            $booking = MjellmaBooking::create([
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

            event(new MjellmaBookingCreatedEvent($booking));

            // 📝 Log notification firing
            \Log::info('🔔 MjellmaBookingCreatedEvent triggered', [
                'order_id' => $booking->order_id,
                'partner_order_id' => $booking->partner_order_id,
                'user_email' => $booking->user_email,
            ]);

            return view('Hotel::frontend.payment-success', [
                'order_id' => $payload['order_id'],
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
        // 1) First, fetch the “order/info” data exactly as before:
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/info/', [
                'ordering' => [
                    'ordering_type' => 'desc',
                    'ordering_by'   => 'created_at',
                ],
                'pagination' => [
                    'page_size'   => 1,
                    'page_number' => 1,
                ],
                'search' => [
                    // this is ETG’s own order_id
                    'order_ids' => [ (int) $orderId ],
                ],
                'language' => 'en',
            ]);

        $json = $response->json();

        if (! ($json['status'] === 'ok' && isset($json['data']['orders'][0])) ) {
            return redirect()->back()->with('error', $json['error'] ?? 'Unable to fetch booking details.');
        }

        // Grab the ETG‐side booking data:
        $bookingInfo = $json['data']['orders'][0];

        /** ───────────────────────────────────────────────────────
         *  2) EXTRACT partner_order_id from the "partner_data" block,
         *     instead of reusing $orderId (which is ETG's internal ID).
         *  ───────────────────────────────────────────────────────
         */
        $partnerOrderId = $bookingInfo['partner_data']['order_id'] ?? null;

        if (empty($partnerOrderId)) {
            // If partner_data->order_id is missing, bail out:
            Log::error("No partner_order_id found for ETG order {$orderId}");
            $finalStatus = 'UNKNOWN';
            $finishData  = [];
        } else {
            // 3) Now call the “finish status” endpoint with the PARTNER ID:
            try {
                $finishResp = Http::withBasicAuth($this->username, $this->password)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($this->apiUrl . 'hotel/order/booking/finish/status/', [
                        'partner_order_id' => $partnerOrderId,
                    ]);

                if ($finishResp->successful()) {
                    $finishData  = $finishResp->json();
                    // ETG returns something like { "status": "CONFIRMED", … }
                    $finalStatus = $finishData['status'] ?? 'UNKNOWN';
                } else {
                    // If ETG returns a non‐200, show that HTTP code
                    $finalStatus = 'ERROR (HTTP ' . $finishResp->status() . ')';
                    $finishData  = $finishResp->body();
                }
            } catch (\Exception $e) {
                Log::error("Error fetching finish status for partner_order_id {$partnerOrderId}: " . $e->getMessage());
                $finalStatus = 'ERROR';
                $finishData  = ['error' => $e->getMessage()];
            }
        }

        // 4) Pass BOTH the “order/info” data and the finish‐status info to the view:
        return view('Hotel::admin.details', [
            'booking'     => $bookingInfo,
            'finalStatus' => $finalStatus,
            'finishData'  => $finishData,
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
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . 'hotel/order/cancel/', [
                'partner_order_id' => $partnerOrderId,
                'user' => [
                    'email' => 'lindor.morina@progtelx.com' // ✅ only your email, no DB email
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
        $response = Http::withBasicAuth($this->username, $this->password)
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

        return view('User::frontend.bookingHistory', [
            'bookings' => $bookings,
            'statuses' => $statuses,
            'statues' => config('booking.statuses'), // Typo kept if your blade still expects 'statues'
        ]);
    }

    public function showBookingInvoice(Request $request, $orderId)
    {
        $response = Http::withBasicAuth($this->username, $this->password)
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
