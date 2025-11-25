<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Invoice') }} #{{ data_get($invoice, 'reservation.id') }}</title>
    <style>
        *{
            box-sizing:border-box;
        }
        body{
            font-family: DejaVu Sans, sans-serif;
            color:#111827;
            background:#fff;
            margin:0;
        }
        .document{
            width:100%;
            padding:28px 30px 40px;
        }
        .brand-bar img{
            width:100%;
            display:block;
        }
        .invoice-header{
            display:flex;
            justify-content:space-between;
            align-items:flex-end;
            padding:18px 0;
            border-bottom:1px solid #e5e7eb;
        }
        .invoice-header h1{
            margin:0;
            font-size:28px;
            letter-spacing:.08em;
        }
        .invoice-id{
            color:#e2581c;
            font-size:18px;
            font-weight:700;
        }
        .invoice-meta{
            text-align:right;
            font-size:12px;
            color:#4b5563;
            line-height:1.4;
        }
        .info-table{
            width:100%;
            border-collapse:collapse;
            margin:20px 0;
            font-size:13px;
        }
        .info-table th{
            text-transform:uppercase;
            font-size:11px;
            letter-spacing:.08em;
            color:#9ca3af;
            text-align:left;
            padding-bottom:6px;
        }
        .info-table td{
            vertical-align:top;
            padding-right:25px;
            padding-bottom:10px;
        }
        .info-table td:last-child{
            padding-right:0;
        }
        .timeline{
            display:flex;
            padding:12px 0;
            border-bottom:1px solid #e5e7eb;
            border-top:1px solid #e5e7eb;
            margin-bottom:18px;
        }
        .timeline .point{
            flex:1;
        }
        .timeline .label{
            font-size:11px;
            text-transform:uppercase;
            color:#9ca3af;
            letter-spacing:.08em;
        }
        .timeline .value{
            font-size:14px;
            font-weight:600;
            margin-top:4px;
        }
        .timeline .point div:last-child{
            font-size:12px;
        }
        .detail-table{
            width:100%;
            border-collapse:collapse;
            margin:10px 0 24px;
            font-size:13px;
        }
        .detail-table th{
            text-transform:uppercase;
            font-size:11px;
            letter-spacing:.08em;
            color:#6b7280;
            text-align:left;
            padding:0 0 6px;
        }
        .detail-table td{
            padding:4px 25px 6px 0;
            vertical-align:top;
        }
        .detail-table td:last-child{
            padding-right:0;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:28px;
            font-size:12px;
        }
        table thead th{
            background:#0f172a;
            color:#fff;
            padding:12px 10px;
            text-transform:uppercase;
            letter-spacing:.08em;
            text-align:left;
        }
        table tbody td{
            border-bottom:1px solid #e5e7eb;
            padding:12px 10px;
        }
        table tfoot td{
            padding:10px;
            font-size:13px;
        }
        table tfoot tr:last-child td{
            font-size:15px;
            font-weight:700;
            border-top:2px solid #d1d5db;
            padding-top:12px;
        }
        .highlight{
            color:#f97316;
            font-weight:700;
        }
        .section-title{
            margin-top:24px;
            font-size:13px;
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:.08em;
        }
        .extras-table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
            font-size:12px;
        }
        .extras-table th,
        .extras-table td{
            border:1px solid #e5e7eb;
            padding:8px;
            text-align:left;
        }
        .coverage-table{
            margin-top:10px;
        }
        .coverage-table tbody tr.selected td{
            background:#ecfdf5;
        }
        .coverage-table td.price{
            text-align:right;
            white-space:nowrap;
        }
        .page-break{
            page-break-before:always;
        }
        .terms{
            font-size:11px;
            line-height:1.6;
            margin-top:12px;
            padding-left:18px;
        }
        .signature-block{
            margin-top:20px;
            border-top:1px solid #d1d5db;
            padding-top:12px;
        }
        .signature-label{
            text-transform:uppercase;
            letter-spacing:.08em;
            font-size:11px;
            color:#6b7280;
        }
        .footer-brand img{
            width:100%;
            display:block;
            margin-top:20px;
        }
    </style>
</head>
<body>
    <div class="document">
        <div class="brand-bar">
            @if(data_get($invoice, 'images.header'))
                <img src="{{ data_get($invoice, 'images.header') }}" alt="Invoice Header">
            @endif
        </div>

        <div class="invoice-header">
            <div>
                <h1>Fatura e Rezervimit</h1>
                <div class="invoice-id">#{{ data_get($invoice, 'reservation.id') }}</div>
            </div>
            <div class="invoice-meta">
                <div>Lëshuar më: {{ $generatedAt->format('d.m.Y H:i') }}</div>
                <div>Agjencia: {{ data_get($invoice, 'car.company', 'Rent a Car Orange') }}</div>
                <div>Kontakti: {{ data_get($invoice, 'client.email') }} / {{ data_get($invoice, 'client.phone') }}</div>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <th>Klienti</th>
                <th>Automjeti</th>
                <th>Përmbledhje financiare</th>
            </tr>
            <tr>
                <td>
                    {{ data_get($invoice, 'client.full_name') }}<br>
                    {{ data_get($invoice, 'client.address') }}<br>
                    Patentë shoferi: {{ data_get($invoice, 'client.driving_license') }}
                </td>
                <td>
                    {{ data_get($invoice, 'car.manufacturer') }} {{ data_get($invoice, 'car.model') }}<br>
                    Targa: {{ data_get($invoice, 'car.licence_plate') }}<br>
                    Lloji i karburantit: {{ data_get($invoice, 'car.fuel_type') }}
                </td>
                <td>
                    Nën-total i veturës: {{ number_format((float) data_get($invoice, 'totals.car_subtotal', 0), 2) }} €<br>
                    Nën-total i shtesave: {{ number_format((float) data_get($invoice, 'totals.extras_subtotal', 0), 2) }} €<br>
                    <span class="highlight">Totali përfundimtar: {{ number_format((float) data_get($invoice, 'totals.grand_total', data_get($invoice, 'reservation.grand_total', 0)), 2) }} €</span>
                </td>
            </tr>
        </table>

        <div class="timeline">
            <div class="point">
                <div class="label">Marrja</div>
                <div class="value">{{ data_get($invoice, 'reservation.pickup_datetime') }}</div>
                <div>{{ data_get($invoice, 'locations.pickup.full_address') }}</div>
            </div>
            <div class="point" style="text-align:center;">
                <div class="label">Kohëzgjatja</div>
                <div class="value">{{ data_get($invoice, 'reservation.days') }} ditë</div>
            </div>
            <div class="point" style="text-align:right;">
                <div class="label">Dorëzimi</div>
                <div class="value">{{ data_get($invoice, 'reservation.dropoff_datetime') }}</div>
                <div>{{ data_get($invoice, 'locations.dropoff.full_address') }}</div>
            </div>
        </div>

        @php
            $selectedCoverageId = data_get($invoice, 'selected_collision_damage');
            $selectedCoverage = collect(data_get($invoice, 'collision_damages', []))
                ->firstWhere('id', $selectedCoverageId);
        @endphp
        <table class="detail-table">
            <tr>
                <th>Plani i mbulimit</th>
                <th>Informata të agjencisë</th>
            </tr>
            <tr>
                <td>
                    <strong>{{ data_get($selectedCoverage, 'name', 'Mbulim standard') }}</strong><br>
                    {{ data_get($selectedCoverage, 'description_en', data_get($selectedCoverage, 'description_al')) }}<br>
                    Çmimi / ditë: {{ number_format((float) data_get($selectedCoverage, 'price', 0), 2) }} €
                </td>
                <td>
                    {{ data_get($invoice, 'car.company', 'Rent a Car Prishtina') }}<br>
                    Telefoni: 044 240 383<br>
                    Lokacioni: Prishtina International Airport
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Përshkrimi</th>
                    <th>Ditë</th>
                    <th>Çmimi</th>
                    <th>Totali</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Qira e automjetit</td>
                    <td>{{ data_get($invoice, 'reservation.days') }}</td>
                    <td>{{ number_format((float) data_get($invoice, 'reservation.gross_total', 0), 2) }} €</td>
                    <td>{{ number_format((float) data_get($invoice, 'reservation.gross_total', 0), 2) }} €</td>
                </tr>
                @foreach(data_get($invoice, 'extra_equipments', []) as $extra)
                    <tr>
                        <td>{{ data_get($extra, 'name') }}</td>
                        <td>{{ data_get($extra, 'days', '-') }}</td>
                        <td>{{ number_format((float) data_get($extra, 'price', 0), 2) }} €</td>
                        <td>{{ number_format((float) data_get($extra, 'total', 0), 2) }} €</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" align="right">Nën-total i veturës</td>
                    <td>{{ number_format((float) data_get($invoice, 'totals.car_subtotal', data_get($invoice, 'reservation.gross_total', 0)), 2) }} €</td>
                </tr>
                <tr>
                    <td colspan="3" align="right">Nën-total i shtesave</td>
                    <td>{{ number_format((float) data_get($invoice, 'totals.extras_subtotal', 0), 2) }} €</td>
                </tr>
                <tr>
                    <td colspan="3" align="right">Totali përfundimtar</td>
                    <td class="highlight">{{ number_format((float) data_get($invoice, 'totals.grand_total', data_get($invoice, 'reservation.grand_total', 0)), 2) }} €</td>
                </tr>
            </tfoot>
        </table>

        @if(!empty(data_get($invoice, 'extra_equipments')))
            <div class="section-title">Pajisje shtesë & shërbime</div>
            <table class="extras-table">
                <thead>
                    <tr>
                        <th>Emri</th>
                        <th>Ditë</th>
                        <th>Ditore</th>
                        <th>Çmimi</th>
                        <th>Totali</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(data_get($invoice, 'extra_equipments', []) as $extra)
                        <tr>
                            <td>{{ data_get($extra, 'name') }}</td>
                            <td>{{ data_get($extra, 'days', '-') }}</td>
                            <td>{{ data_get($extra, 'daily') ? 'Po' : 'Jo' }}</td>
                            <td>{{ number_format((float) data_get($extra, 'price', 0), 2) }} €</td>
                            <td>{{ number_format((float) data_get($extra, 'total', 0), 2) }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty(data_get($invoice, 'collision_damages')))
            @php
                $collisionDamages = collect(data_get($invoice, 'collision_damages', []));
                if ($collisionDamages->isNotEmpty()) {
                    $collisionDamages = $collisionDamages->sortByDesc(function ($item) use ($selectedCoverageId) {
                        return $item['id'] == $selectedCoverageId ? 1 : 0;
                    })->values();
                }
            @endphp
            <div class="section-title">Opsionet e mbulimit të dëmeve</div>
            <table class="coverage-table">
                <thead>
                    <tr>
                        <th>Emri</th>
                        <th>Çmimi ditor</th>
                        <th>Përshkrimi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collisionDamages as $damage)
                        @php
                            $isSelectedCoverage = $damage['id'] == $selectedCoverageId;
                        @endphp
                        <tr class="{{ $isSelectedCoverage ? 'selected' : '' }}">
                            <td>
                                {{ data_get($damage, 'name') }}
                                @if($isSelectedCoverage)
                                    <span style="font-size:11px; color:#047857; font-weight:600;">(E zgjedhur)</span>
                                @endif
                            </td>
                            <td class="price">{{ number_format((float) data_get($damage, 'price', 0), 2) }} €</td>
                            <td>{{ data_get($damage, 'description_en', data_get($damage, 'description_al')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="signature-block">
            <div class="signature-label">Nënshkrimi i klientit</div>
            <div style="height:50px; margin-top:8px;">
                @if(data_get($invoice, 'signature.exists') && data_get($invoice, 'signature.image_url'))
                    <img src="{{ data_get($invoice, 'signature.image_url') }}" alt="Signature" style="max-height:50px;">
                @else
                    <span style="color:#d1d5db;">Nënshkrimi në pritje</span>
                @endif
            </div>
        </div>

        @if(!empty(data_get($invoice, 'terms_and_conditions')))
            <div class="page-break"></div>
            <div class="section-title">Kushtet & rregullat</div>
            <ol class="terms">
                @foreach(data_get($invoice, 'terms_and_conditions', []) as $term)
                    <li>{{ data_get($term, 'text') }}</li>
                @endforeach
            </ol>
        @endif

        <div class="footer-brand">
            @if(data_get($invoice, 'images.footer'))
                <img src="{{ data_get($invoice, 'images.footer') }}" alt="Invoice Footer">
            @endif
        </div>
    </div>
</body>
</html>

