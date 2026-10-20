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
