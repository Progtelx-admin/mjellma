@php
    // expected: $section, optional: $heading, $anchor, $limit, $cols
    $colMap = [1 => 'col-12', 2 => 'col-12 col-md-6', 3 => 'col-12 col-md-6 col-lg-4', 4 => 'col-6 col-md-3'];
    $colClass = $colMap[$cols ?? 3] ?? $colMap[3];

    $cards = isset($limit) && $limit > 0 ? $section->cards->take($limit) : $section->cards;
    $title = $heading ?? $section->title;
    $ctaText = $section->cta_text ?? null;
    $ctaLink = $section->cta_link ?? null ?: $anchor ?? '#';
@endphp

<div class="section-offers py-5">
    <div class="container">
        @if (!empty($title))
            <h2 class="text-center mb-5">{{ $title }}</h2>
        @endif

        <div class="row g-5 justify-content-center">
            @forelse($cards as $card)
                @php $href = $anchor ?: ($card->link ?: '#'); @endphp
                <div class="{{ $colClass }}">
                    <a href="{{ $href }}"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ $card->image_url }}" alt="{{ $card->title ?? $section->title }}"
                            class="deal-img w-100">
                        @if (($card->show_caption ?? false) && !empty($card->title))
                            <span class="deal-caption">{{ $card->title }}</span>
                        @endif
                    </a>
                </div>
            @empty
                <div class="col-12 text-center text-muted">No cards yet.</div>
            @endforelse
        </div>

        @if (!empty($ctaText))
            <div class="text-center mt-4">
                <a href="{{ $ctaLink }}" class="btn btn-cta px-4 py-2">
                    {{ $ctaText }}
                </a>
            </div>
        @endif
    </div>
</div>
