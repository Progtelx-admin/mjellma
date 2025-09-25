{{-- shim so @include('offers::widgets.async-section') works in themes --}}
@include('offers::front.async-section', [
    'slug' => $slug ?? '',
    'heading' => $heading ?? null,
    'anchor' => $anchor ?? null,
    'cols' => $cols ?? 3,
    'limit' => $limit ?? 0,
])
