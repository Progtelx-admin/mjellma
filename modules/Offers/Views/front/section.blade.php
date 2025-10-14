@php
    // expected: $section, optional: $heading, $anchor, $limit, $cols
    $colMap = [1 => 'col-12', 2 => 'col-12 col-md-6', 3 => 'col-12 col-md-6 col-lg-4', 4 => 'col-6 col-md-3'];
    $colClass = $colMap[$cols ?? 3] ?? $colMap[3];

    $cards = isset($limit) && $limit > 0 ? $section->cards->take($limit) : $section->cards;
    $title = $heading ?? $section->title;
    $ctaText = $section->cta_text ?? null;
    $ctaLink = $section->cta_link ?? null ?: $anchor ?? '#';
    $uniqueId = 'offers-' . uniqid();
@endphp

<div class="section-offers py-5">
    <div class="container">
        @if (!empty($title))
            <h2 class="text-center mb-5">{{ $title }}</h2>
        @endif

        @if ($cards->isNotEmpty())
            <!-- Desktop Grid View -->
            <div class="row g-5 justify-content-center d-none d-md-flex offers-grid-view">
                @foreach ($cards as $card)
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
                @endforeach
            </div>

            <!-- Mobile Carousel View -->
            <div class="d-md-none offers-carousel-view">
                <div class="owl-carousel offers-carousel-{{ $uniqueId }}">
                    @foreach ($cards as $card)
                        @php $href = $anchor ?: ($card->link ?: '#'); @endphp
                        <div class="item">
                            <a href="{{ $href }}"
                                class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                                <img src="{{ $card->image_url }}" alt="{{ $card->title ?? $section->title }}"
                                    class="deal-img w-100">
                                @if (($card->show_caption ?? false) && !empty($card->title))
                                    <span class="deal-caption">{{ $card->title }}</span>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center text-muted">No cards yet.</div>
        @endif

        @if (!empty($ctaText))
            <div class="text-center mt-4">
                <a href="{{ $ctaLink }}" class="btn btn-cta px-4 py-2">
                    {{ $ctaText }}
                </a>
            </div>
        @endif
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function() {
            $(".offers-carousel-{{ $uniqueId }}").owlCarousel({
                items: 1,
                loop: true,
                margin: 20,
                nav: false,
                dots: true,
                autoplay: false,
                responsive: {
                    0: {
                        items: 1,
                        nav: false,
                        dots: true
                    }
                }
            });
        });
    </script>
@endpush

<style>
    /* Carousel dots styling for offers */
    .offers-carousel-view .owl-dots {
        text-align: center;
        margin-top: 20px;
    }

    .offers-carousel-view .owl-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ddd !important;
        margin: 0 5px;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .offers-carousel-view .owl-dot.active {
        background: #333 !important;
        width: 30px;
        border-radius: 5px;
    }
</style>
