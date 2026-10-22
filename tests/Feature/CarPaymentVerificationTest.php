<?php

namespace Tests\Feature;

use App\Services\PcbBankService;
use App\Models\CarPaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Car\Controllers\CarController;
use Tests\TestCase;

class CarPaymentVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_10_19_000001_create_car_payment_orders_table.php'))->up();
        (require database_path('migrations/2026_10_20_000001_add_car_order_filter_columns.php'))->up();
    }

    public function test_car_landing_redirects_to_homepage(): void
    {
        $controller = new class extends CarController {
            public function __construct() {}
        };

        $this->assertSame(url('/'), $controller->index()->getTargetUrl());
    }

    /** @dataProvider paymentCases */
    public function test_booking_requires_verified_bank_payment($returnStatus, $details, $expected)
    {
        $token = str_repeat('a', 32);
        CarPaymentOrder::create([
            'token_hash' => hash('sha256', $token), 'booking_data' => ['reservation_data' => []],
            'bank_order_id' => '123', 'bank_password' => 'test', 'amount' => '120.00', 'currency' => 'EUR',
        ]);
        $bank = \Mockery::mock(PcbBankService::class);
        $bank->shouldReceive('getOrderDetails')->once()->with('123', 'test')->andReturn($details);
        $this->app->instance(PcbBankService::class, $bank);
        $controller = new class extends CarController {
            public $completed = false;
            public function __construct() {}
            protected function completeCarBookingAfterPayment($bookingData, $pcbOrder, $token)
            {
                $this->completed = true;
                return response('verified');
            }
        };
        $controller->handlePcbReturn(Request::create('/car/pcb/return', 'GET', [
            'ID' => '123', 'STATUS' => $returnStatus, 'token' => $token,
        ]));
        $this->assertSame($expected, $controller->completed);
    }

    public function test_reservation_claim_cannot_be_repeated(): void
    {
        $order = CarPaymentOrder::create([
            'token_hash' => hash('sha256', 'test'), 'booking_data' => ['email' => 'secret@example.com'],
            'bank_password' => 'secret-password', 'amount' => '120.00', 'currency' => 'EUR',
            'payment_status' => 'paid',
        ]);
        $this->assertTrue($order->claimReservation());
        $this->assertFalse($order->claimReservation());
        $stored = DB::table('car_payment_orders')->first();
        $this->assertStringNotContainsString('secret@example.com', $stored->booking_data);
        $this->assertStringNotContainsString('secret-password', $stored->bank_password);
        $this->assertSame('secret-password', $order->fresh()->bank_password);
    }

    public function test_completed_callback_restores_confirmation_without_another_bank_call(): void
    {
        $token = str_repeat('b', 32);
        CarPaymentOrder::create([
            'token_hash' => hash('sha256', $token), 'booking_data' => ['reservation_data' => ['car_id' => 27]],
            'bank_order_id' => '123', 'bank_password' => 'test', 'amount' => '120.00', 'currency' => 'EUR',
            'payment_status' => 'paid', 'reservation_status' => 'completed',
            'reservation_response' => ['id' => 456],
        ]);
        $bank = \Mockery::mock(PcbBankService::class);
        $bank->shouldNotReceive('getOrderDetails');
        $this->app->instance(PcbBankService::class, $bank);
        $controller = new class extends CarController { public function __construct() {} };
        $response = $controller->handlePcbReturn(Request::create('/car/pcb/return', 'GET', ['token' => $token]));
        $this->assertSame(route('car.checkout.confirm'), $response->getTargetUrl());
        $this->assertSame(456, session('car_booking_complete.api_response.id'));
    }

    /** @dataProvider reservationCases */
    public function test_external_reservation_is_saved_and_never_posted_twice(array $apiResponse, string $expected): void
    {
        Mail::fake();
        $token = str_repeat('c', 32);
        $booking = [
            'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Test', 'email' => 'test@example.com',
            'birthday' => '28.01.1997', 'driving_license' => 'test', 'phone' => 'test', 'address' => 'test',
            'plz' => '10000', 'city' => 'test', 'country' => 'test',
            'reservation_data' => ['pickup_datetime' => '2026-10-20 10:00', 'dropoff_datetime' => '2026-10-23 10:00',
                'franch' => 0, 'car_id' => 27, 'pickup_place' => 4, 'dropoff_location' => 4],
        ];
        $order = CarPaymentOrder::create([
            'token_hash' => hash('sha256', $token), 'booking_data' => $booking,
            'bank_order_id' => '123', 'bank_password' => 'test', 'amount' => '120.00', 'currency' => 'EUR',
        ]);
        $bank = \Mockery::mock(PcbBankService::class);
        $bank->shouldReceive('getOrderDetails')->times($expected === 'completed' ? 1 : 2)
            ->andReturn(['id' => 123, 'status' => 'FullyPaid', 'amount' => 120, 'currency' => 'EUR']);
        $this->app->instance(PcbBankService::class, $bank);
        $controller = new class extends CarController {
            public $posts = 0;
            public $result;
            public function __construct() {}
            protected function makeApiRequest($endpoint, $method = 'GET', $data = [])
            {
                if ($endpoint !== 'store-checkout' || $method !== 'POST') {
                    throw new \RuntimeException('Unexpected API call');
                }
                $this->posts++;
                return $this->result;
            }
        };
        $controller->result = $apiResponse;
        $request = Request::create('/car/pcb/return', 'GET', ['ID' => '123', 'STATUS' => 'FullyPaid', 'token' => $token]);
        $controller->handlePcbReturn($request);
        $this->assertSame($expected, $order->fresh()->reservation_status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $controller->handlePcbReturn($request);
        $this->assertSame(1, $controller->posts);
    }

    public static function reservationCases(): array
    {
        return [
            'confirmed' => [['success' => true, 'status' => 200, 'data' => ['success' => true, 'reservation_id' => 456]], 'completed'],
            'explicit API failure with HTTP 200' => [['success' => true, 'status' => 200, 'data' => ['success' => false]], 'review_required'],
            'unrecognized response' => [['success' => true, 'status' => 200, 'data' => ['message' => 'unknown']], 'review_required'],
            'timeout' => [['success' => false, 'error' => 'timeout'], 'review_required'],
        ];
    }

    public function test_dashboard_filters_search_and_rental_dates(): void
    {
        $order = CarPaymentOrder::create([
            'token_hash' => hash('sha256', 'filters'), 'bank_order_id' => 'BANK-123',
            'amount' => '120.00', 'currency' => 'EUR',
            'booking_data' => ['reservation_data' => ['pickup_datetime' => '2026-10-21 10:00', 'dropoff_datetime' => '2026-10-24 10:00']],
            'reservation_response' => ['reservation_id' => 'RES-456'],
        ]);
        foreach (['BANK-123', 'RES-456', '#'.$order->id] as $search) {
            $this->assertSame(1, CarPaymentOrder::dashboardFilters(['search' => $search])->count());
        }
        $dates = ['dateFrom' => '2026-10-24', 'dateTo' => '2026-10-24'];
        $this->assertSame(1, CarPaymentOrder::dashboardFilters($dates + ['column' => 'returns'])->count());
        $this->assertSame(0, CarPaymentOrder::dashboardFilters($dates + ['column' => 'pickups'])->count());
        $this->assertSame(1, CarPaymentOrder::dashboardFilters($dates)->count());
        $this->assertSame(0, CarPaymentOrder::dashboardFilters($dates + ['search' => 'unknown'])->count());
    }

    public function test_local_details_use_saved_customer_and_vehicle_information(): void
    {
        $order = CarPaymentOrder::create([
            'token_hash' => hash('sha256', 'details'), 'bank_order_id' => 'BANK-DETAILS',
            'bank_password' => 'private-password', 'amount' => '120.00', 'currency' => 'EUR', 'payment_status' => 'paid',
            'booking_data' => ['first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'test@example.com',
                'reservation_data' => ['car_id' => 27, 'pickup_place' => 4, 'dropoff_location' => 4,
                    'pickup_datetime' => '2026-10-21 10:00', 'dropoff_datetime' => '2026-10-24 10:00'],
                'api_response' => ['car' => ['car_manufacturer' => ['name' => 'Seat'], 'car_model' => ['name' => 'Touran']]]],
        ]);
        $details = $order->fresh()->reservationDetails();
        $this->assertSame('Test Customer', $details['client_name']);
        $this->assertSame('Seat', $details['vehicle_manufacturer']);
        $this->assertSame('Touran', $details['vehicle_model']);
        $this->assertSame(120.0, $details['paid_amount']);
        $this->assertStringNotContainsString('private-password', json_encode($details));
    }

    public function test_local_details_require_authentication(): void
    {
        \Illuminate\Support\Facades\Auth::shouldReceive('check')->once()->andReturn(false);
        $controller = new \Custom\CarRent\Admin\CarRentReservationController();
        try {
            $controller->showLocal(Request::create('/'), 1);
            $this->fail('Unauthenticated access was allowed');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_local_summary_excludes_demos_and_separates_paid_review_orders(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-20 12:00:00'));
        foreach ([['REAL-1', 'completed', 'EUR'], ['DEMO-1', 'completed', 'EUR'],
            ['REAL-2', 'review_required', 'EUR'], ['REAL-3', 'completed', 'USD']] as $i => [$bankId, $status, $currency]) {
            CarPaymentOrder::create([
                'token_hash' => hash('sha256', 'summary-'.$i), 'bank_order_id' => $bankId,
                'amount' => '120.00', 'currency' => $currency, 'payment_status' => 'paid', 'reservation_status' => $status,
                'verified_at' => now(), 'booking_data' => ['reservation_data' => [
                    'pickup_datetime' => '2026-10-20 10:00', 'dropoff_datetime' => '2026-10-23 10:00']],
            ]);
        }
        $summary = app(\App\Services\CarOrderSummary::class)->local([]);
        $this->assertSame(2, $summary['total_reservations']);
        $this->assertSame(2, $summary['active_reservations']);
        $this->assertNull($summary['overdue_reservations']);
        $this->assertEquals(120, $summary['total_revenue']);
        $this->assertEquals(120, $summary['review_amount']);
        $filtered = app(\App\Services\CarOrderSummary::class)->local(['dateFrom' => '2026-10-23', 'column' => 'returns']);
        $this->assertSame(2, $filtered['total_reservations']);
        $this->assertEquals(0, $filtered['total_revenue']);
        $this->travelBack();
    }

    public static function paymentCases(): array
    {
        $paid = ['id' => 123, 'status' => 'FullyPaid', 'amount' => 120, 'currency' => 'EUR'];
        return [
            'bank overrides refused browser status' => ['Refused', $paid, true],
            'browser cannot claim payment' => ['FullyPaid', array_merge($paid, ['status' => 'Refused']), false],
            'bank unavailable' => ['FullyPaid', null, false],
            'wrong amount' => ['FullyPaid', array_merge($paid, ['amount' => 1]), false],
            'wrong currency' => ['FullyPaid', array_merge($paid, ['currency' => 'USD']), false],
            'wrong order' => ['FullyPaid', array_merge($paid, ['id' => 999]), false],
        ];
    }
}
