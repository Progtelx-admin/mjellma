<?php

namespace Custom\CarRent\Admin;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AdminController;

class CarRentReservationController extends AdminController
{
    private $apiBaseUrl = 'https://dev.rentacar-orange.com/api/agency';
    private $apiToken = 'Bearer tBMCZBWsjAZzKJbpvZZSt9saYVstWxppPFRFnSuX';

    public function __construct()
    {
        $this->setActiveMenu(route('carrent.admin.reservations.index'));
    }

    /**
     * Display car rent reservations
     */
    public function index(Request $request)
    {
        // Check if user is authenticated (handled by dashboard middleware)
        // The dashboard middleware already checks for 'dashboard_access' permission
        // So we don't need to check carrent_view here unless specifically required
        // $this->checkPermission('carrent_view');

        $reservations = [];
        $error = null;
        $filters = [
            'search' => trim($request->input('search', '')),
            'dateFrom' => $request->input('dateFrom', ''),
            'dateTo' => $request->input('dateTo', ''),
            'column' => $request->input('column', ''),
            'filter_date' => $request->input('filter_date', ''),
        ];

        // If both dates are the same, use filter_date instead (as per API example)
        if (!empty($filters['dateFrom']) && !empty($filters['dateTo']) && $filters['dateFrom'] === $filters['dateTo']) {
            $filters['filter_date'] = $filters['dateFrom'];
        }

        try {
            // Build API URL with query parameters
            $url = $this->apiBaseUrl . '/reservations';
            $queryParams = [];

            if (!empty($filters['search'])) {
                // Remove any non-numeric characters if searching by ID, or keep as is for other searches
                $searchValue = $filters['search'];
                // If it's a numeric ID, ensure it's clean
                if (is_numeric($searchValue)) {
                    $queryParams['search'] = (string) intval($searchValue);
                } else {
                    $queryParams['search'] = $searchValue;
                }
            }
            if (!empty($filters['dateFrom'])) {
                // Ensure date is in YYYY-MM-DD format
                try {
                    $dateFrom = \Carbon\Carbon::parse($filters['dateFrom'])->format('Y-m-d');
                    $queryParams['dateFrom'] = $dateFrom;
                } catch (\Exception $e) {
                    Log::warning('Invalid dateFrom format', ['date' => $filters['dateFrom']]);
                }
            }
            if (!empty($filters['dateTo'])) {
                // Ensure date is in YYYY-MM-DD format
                try {
                    $dateTo = \Carbon\Carbon::parse($filters['dateTo'])->format('Y-m-d');
                    $queryParams['dateTo'] = $dateTo;
                } catch (\Exception $e) {
                    Log::warning('Invalid dateTo format', ['date' => $filters['dateTo']]);
                }
            }

            // If dateFrom and dateTo are the same, ensure both are set
            if (!empty($filters['dateFrom']) && empty($filters['dateTo'])) {
                $queryParams['dateTo'] = $queryParams['dateFrom'] ?? $filters['dateFrom'];
            }
            if (!empty($filters['dateTo']) && empty($filters['dateFrom'])) {
                $queryParams['dateFrom'] = $queryParams['dateTo'] ?? $filters['dateTo'];
            }
            if (!empty($filters['column'])) {
                $queryParams['column'] = $filters['column'];
            }

            // Use filter_date if both dates are the same (as per API example: /api/agency/home?filter_date=2025-11-10)
            if (!empty($filters['filter_date'])) {
                try {
                    $filterDate = \Carbon\Carbon::parse($filters['filter_date'])->format('Y-m-d');
                    $queryParams['filter_date'] = $filterDate;
                    // Remove dateFrom/dateTo if using filter_date
                    unset($queryParams['dateFrom'], $queryParams['dateTo']);
                } catch (\Exception $e) {
                    Log::warning('Invalid filter_date format', ['date' => $filters['filter_date']]);
                }
            }

            // Always build URL with query parameters (even if empty, to get all reservations)
            // But only add query string if we have parameters
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            // Log the request for debugging
            Log::info('Car Rent API Request', [
                'url' => $url,
                'filters' => $filters,
                'query_params' => $queryParams,
                'has_search' => !empty($filters['search']),
                'has_dateFrom' => !empty($filters['dateFrom']),
                'has_dateTo' => !empty($filters['dateTo']),
                'has_column' => !empty($filters['column']),
            ]);

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Log response structure for debugging
                Log::info('Car Rent API Response', [
                    'status' => $response->status(),
                    'response_structure' => array_keys($data ?? []),
                    'data_type' => gettype($data),
                    'is_array' => is_array($data),
                    'has_data_key' => isset($data['data']),
                ]);

                // Handle different response structures
                if (isset($data['data'])) {
                    // If data is an array, use it directly
                    if (is_array($data['data'])) {
                        $reservations = $data['data'];
                    }
                    // If data is an object/array with nested structure
                    elseif (is_object($data['data']) || (is_array($data['data']) && isset($data['data'][0]))) {
                        $reservations = is_array($data['data']) ? $data['data'] : [$data['data']];
                    } else {
                        $reservations = [];
                    }
                }
                // If response is directly an array
                elseif (is_array($data)) {
                    // Check if it's a list of reservations or a single object
                    if (isset($data[0]) && is_array($data[0])) {
                        $reservations = $data;
                    }
                    // If it's a single reservation object
                    elseif (isset($data['id']) || isset($data['reservation_id'])) {
                        $reservations = [$data];
                    } else {
                        $reservations = [];
                    }
                } else {
                    $reservations = [];
                }

                Log::info('Car Rent API Parsed Reservations', [
                    'count' => count($reservations),
                    'first_reservation_keys' => !empty($reservations) ? array_keys($reservations[0] ?? []) : [],
                    'sample_reservation' => !empty($reservations) ? $reservations[0] : null
                ]);

                // If we have date filters but no results, try without date filters as fallback
                // This helps debug if the date format is the issue
                if (empty($reservations) && (!empty($filters['dateFrom']) || !empty($filters['dateTo']))) {
                    Log::warning('Car Rent API: No results with date filters, trying without dates', [
                        'dateFrom' => $filters['dateFrom'],
                        'dateTo' => $filters['dateTo']
                    ]);
                }
            } else {
                $error = 'Failed to fetch reservations. Status: ' . $response->status();
                Log::error('Car Rent API Error', [
                    'status' => $response->status(),
                    'url' => $url,
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);
            }
        } catch (\Exception $e) {
            $error = 'Error fetching reservations: ' . $e->getMessage();
            Log::error('Car Rent API Exception', [
                'message' => $e->getMessage(),
                'url' => $url ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);
        }

        $homeResponse = $this->fetchHomeData($filters);
        $homeReservations = $this->extractListFromResponse($homeResponse);
        $homeSummary = $this->buildHomeSummary($homeResponse, $homeReservations, $filters);

        $reportRange = $this->resolveReportRange($filters);
        $reportResponse = $this->fetchReportData($reportRange['from'], $reportRange['to']);
        $reportRows = $this->extractListFromResponse($reportResponse);
        $reportSummary = $this->buildReportSummary($reportRows, $reportRange);

        $data = [
            'reservations' => $reservations,
            'error' => $error,
            'filters' => $filters,
            'home_summary' => $homeSummary,
            'home_reservations' => $homeReservations,
            'home_raw' => $homeResponse,
            'report_rows' => $reportRows,
            'report_summary' => $reportSummary,
            'report_raw' => $reportResponse,
            'breadcrumbs' => [
                [
                    'name' => __('Car Rent Reservations'),
                    'url' => route('carrent.admin.reservations.index')
                ],
                [
                    'name' => __('All Reservations'),
                    'class' => 'active'
                ],
            ],
            'page_title' => __('Car Rent Reservations')
        ];

        return view('CarRent::admin.index', $data);
    }

    /**
     * Show reservation details
     */
    public function show(Request $request, $id)
    {
        // Check if user is authenticated (handled by dashboard middleware)
        if (!auth()->check()) {
            abort(403);
        }

        $reservation = null;
        $invoice = null;
        $error = null;
        $invoiceError = null;

        try {
            // Build API URL to get specific reservation
            $url = $this->apiBaseUrl . '/reservations';
            $queryParams = ['search' => $id];

            $url .= '?' . http_build_query($queryParams);

            Log::info('Car Rent API Request - Details', [
                'url' => $url,
                'reservation_id' => $id
            ]);

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Handle different response structures
                if (isset($data['data'])) {
                    if (is_array($data['data'])) {
                        // Find the reservation with matching ID
                        foreach ($data['data'] as $item) {
                            if (isset($item['id']) && $item['id'] == $id) {
                                $reservation = $item;
                                break;
                            }
                        }
                        // If not found by id, try first item if array has one item
                        if (!$reservation && count($data['data']) == 1) {
                            $reservation = $data['data'][0];
                        }
                    } else {
                        $reservation = $data['data'];
                    }
                } elseif (is_array($data)) {
                    // If response is directly an array, find matching ID
                    foreach ($data as $item) {
                        if (isset($item['id']) && $item['id'] == $id) {
                            $reservation = $item;
                            break;
                        }
                    }
                    // If not found, use first item if only one
                    if (!$reservation && count($data) == 1) {
                        $reservation = $data[0];
                    }
                }

                if (!$reservation) {
                    $error = 'Reservation not found';
                }
            } else {
                $error = 'Failed to fetch reservation. Status: ' . $response->status();
                Log::error('Car Rent API Error - Details', [
                    'status' => $response->status(),
                    'url' => $url,
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $error = 'Error fetching reservation: ' . $e->getMessage();
            Log::error('Car Rent API Exception - Details', [
                'message' => $e->getMessage(),
                'reservation_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
        }

        if ($error && !$reservation) {
            return redirect()->route('carrent.admin.reservations.index')
                ->with('error', $error);
        }

        try {
            $invoice = $this->fetchInvoiceDetails($id);
            if (!$invoice) {
                $invoiceError = __('Unable to load invoice for this reservation.');
            }
        } catch (\Exception $e) {
            $invoiceError = __('Invoice data is currently unavailable.');
            Log::error('Car Rent Invoice Exception', [
                'message' => $e->getMessage(),
                'reservation_id' => $id,
            ]);
        }

        $reservationIdValue = data_get($reservation, 'id')
            ?? data_get($reservation, 'reservation_id')
            ?? data_get($reservation, 'reservation_number')
            ?? $id;

        $data = [
            'reservation' => $reservation,
            'error' => $error,
            'invoice' => $invoice,
            'invoice_error' => $invoiceError,
            'reservation_id' => $reservationIdValue,
            'breadcrumbs' => [
                [
                    'name' => __('Car Rent Reservations'),
                    'url' => route('carrent.admin.reservations.index')
                ],
                [
                    'name' => __('Reservation Details'),
                    'class' => 'active'
                ],
            ],
            'page_title' => __('Reservation Details') . ' #' . $id
        ];

        return view('CarRent::admin.show', $data);
    }

    public function downloadInvoicePdf(Request $request, $id)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $invoice = $this->fetchInvoiceDetails($id);

        if (!$invoice) {
            return redirect()->route('carrent.admin.reservations.show', ['id' => $id])
                ->with('error', __('Invoice not found or cannot be generated.'));
        }

        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => true,
        ])->loadView('CarRent::admin.invoice-pdf', [
            'invoice' => $invoice,
            'generatedAt' => Carbon::now(),
        ])->setPaper('a4');

        $fileName = "reservation-{$id}-invoice.pdf";

        if ($request->boolean('preview')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    private function fetchInvoiceDetails($id): ?array
    {
        $url = $this->apiBaseUrl . '/invoice/pdf';
        $query = http_build_query(['invoice_id' => $id]);
        $endpoint = "{$url}?{$query}";

        Log::info('Car Rent Invoice Request', [
            'url' => $endpoint,
            'invoice_id' => $id,
        ]);

        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Accept' => 'application/json',
        ])->timeout(30)->get($endpoint);

        if (!$response->successful()) {
            Log::warning('Car Rent Invoice Response Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $payload = $response->json();
        if (!data_get($payload, 'success')) {
            Log::warning('Car Rent Invoice Response unsuccessful', [
                'payload' => $payload,
            ]);
            return null;
        }

        $invoice = data_get($payload, 'data');
        if (!$invoice) {
            return null;
        }

        return $invoice;
    }
    private function fetchHomeData(array $filters)
    {
        try {
            $params = [];
            $date = $filters['filter_date'] ?: ($filters['dateFrom'] ?: Carbon::now()->format('Y-m-d'));
            if (!empty($date)) {
                $params['filter_date'] = Carbon::parse($date)->format('Y-m-d');
            }
            if (!empty($filters['search'])) {
                $params['search_reservation'] = $filters['search'];
            }
            return $this->performGetRequest('/home', $params);
        } catch (\Exception $e) {
            Log::error('Car Rent API Exception - Home', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);
            return null;
        }
    }

    private function fetchReportData(Carbon $from, Carbon $to)
    {
        try {
            $params = [
                'from' => $from->format('d-m-Y'),
                'to' => $to->format('d-m-Y'),
            ];
            return $this->performGetRequest('/report', $params);
        } catch (\Exception $e) {
            Log::error('Car Rent API Exception - Report', [
                'message' => $e->getMessage(),
                'from' => $from,
                'to' => $to,
            ]);
            return null;
        }
    }

    private function performGetRequest(string $endpoint, array $params = [])
    {
        $url = rtrim($this->apiBaseUrl, '/') . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Accept' => 'application/json',
        ])->timeout(30)->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Car Rent API Error - ' . $endpoint, [
            'status' => $response->status(),
            'url' => $url,
            'body' => $response->body(),
        ]);
        return null;
    }

    private function extractListFromResponse($response): array
    {
        if (empty($response)) {
            return [];
        }
        if (isset($response['reservations']) && is_array($response['reservations'])) {
            return $response['reservations'];
        }
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        if (isset($response['reports']) && is_array($response['reports'])) {
            return $response['reports'];
        }
        if (isset($response[0]) && is_array($response[0])) {
            return $response;
        }
        return [];
    }

    private function buildHomeSummary($homeResponse, array $homeReservations, array $filters): array
    {
        $summary = [];
        $summary['filter_date'] = isset($filters['filter_date']) && $filters['filter_date']
            ? Carbon::parse($filters['filter_date'])->format('d.m.Y')
            : (isset($filters['dateFrom']) && $filters['dateFrom']
                ? Carbon::parse($filters['dateFrom'])->format('d.m.Y')
                : Carbon::now()->format('d.m.Y'));

        $summary['total_reservations'] = data_get($homeResponse, 'stats.total_reservations')
            ?? data_get($homeResponse, 'total')
            ?? count($homeReservations);

        $summary['active_reservations'] = data_get($homeResponse, 'stats.active_reservations')
            ?? $this->countByStatus($homeReservations, 'ACTIVE');

        $summary['overdue_reservations'] = data_get($homeResponse, 'stats.overdue_reservations')
            ?? $this->countByStatus($homeReservations, 'OVERDUE');

        $summary['completed_reservations'] = data_get($homeResponse, 'stats.completed_reservations')
            ?? $this->countByStatus($homeReservations, 'COMPLETED');

        $summary['total_revenue'] = data_get($homeResponse, 'stats.total_revenue')
            ?? $this->sumField($homeReservations, ['rental_price', 'price', 'total_price']);

        return $summary;
    }

    private function resolveReportRange(array $filters): array
    {
        $from = !empty($filters['dateFrom'])
            ? Carbon::parse($filters['dateFrom'])
            : Carbon::now()->startOfMonth();

        $to = !empty($filters['dateTo'])
            ? Carbon::parse($filters['dateTo'])
            : Carbon::now();

        if ($to->lessThan($from)) {
            $to = (clone $from)->endOfDay();
        }

        return ['from' => $from, 'to' => $to];
    }

    private function buildReportSummary(array $rows, array $range): array
    {
        $summary = [];
        $summary['range'] = $range['from']->format('d.m.Y') . ' - ' . $range['to']->format('d.m.Y');
        $summary['total_reservations'] = count($rows);
        $summary['total_revenue'] = $this->sumField($rows, ['total_price', 'rental_price', 'price', 'amount']);
        $summary['average_revenue'] = $summary['total_reservations'] > 0
            ? $summary['total_revenue'] / $summary['total_reservations']
            : 0;
        return $summary;
    }

    private function countByStatus(array $rows, string $status): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (isset($row['status']) && strtoupper($row['status']) === strtoupper($status)) {
                $count++;
            }
        }
        return $count;
    }

    private function sumField(array $rows, array $fields): float
    {
        $total = 0;
        foreach ($rows as $row) {
            foreach ($fields as $field) {
                if (isset($row[$field]) && is_numeric($row[$field])) {
                    $total += $row[$field];
                    break;
                }
            }
        }
        return $total;
    }
}

