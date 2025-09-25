@php
    use Modules\Offers\Models\OfferSection;

    if (!isset($sections)) {
        $sections = OfferSection::active()
            ->orderBy('sort_order')
            ->with(['cards' => fn($q) => $q->active()->orderBy('sort_order')])
            ->get();
    }

    $anchor = $anchor ?? null;
    $cols = min(max((int) ($cols ?? 3), 1), 4);
    $limit = max((int) ($limit ?? 0), 0);
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
