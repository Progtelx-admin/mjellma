@php
    use Modules\Offers\Models\OfferSection;

    // Defaults from include()
    $anchor = $anchor ?? null;
    $cols = min(max((int) ($cols ?? 3), 1), 4);
    $limit = max((int) ($limit ?? 0), 0);

    // If caller didn't pass a $sections collection, build the query here
if (!isset($sections)) {
    $q = OfferSection::active()
        ->with(['cards' => fn($qq) => $qq->active()->orderBy('sort_order')])
        ->orderBy('sort_order');

    // Filters
    if (isset($order_equals)) {
        $q->where('sort_order', (int) $order_equals);
    } elseif (!empty($only_first)) {
        $q->limit(1);
    }

    $sections = $q->get();
} else {
    // If a collection was injected, apply in-memory filter (just in case)
    if (isset($order_equals)) {
        $sections = $sections->where('sort_order', (int) $order_equals)->values();
    } elseif (!empty($only_first)) {
        $first = $sections->sortBy('sort_order')->first();
            $sections = $first ? collect([$first]) : collect();
        }
    }
@endphp

@foreach ($sections as $section)
    @include('offers::front.section', [
        'section' => $section,
        'heading' => $section->title,
        'anchor' => $anchor,
        'cols' => $cols,
        'limit' => $limit,
    ])
@endforeach
