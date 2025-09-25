@php
    $slug = $slug ?? '';
    $heading = $heading ?? null;
    $anchor = $anchor ?? null;
    $cols = min(max((int) ($cols ?? 3), 1), 4);
    $limit = max((int) ($limit ?? 0), 0);

    $q = http_build_query(compact('heading', 'anchor', 'cols', 'limit'));
    $endpoint = route('offers.public.section', $slug) . ($q ? '?' . $q : '');
    $domId = 'offers-section-' . md5($endpoint . microtime(true));
@endphp

<div id="{{ $domId }}"></div>
<script>
    (function() {
        var el = document.getElementById(@json($domId));
        var url = @json($endpoint);
        fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                return r.text();
            })
            .then(function(html) {
                el.innerHTML = html;
            })
            .catch(function(err) {
                console.error('Offers section load failed:', err);
                el.innerHTML = '<div class="text-center text-muted">Unable to load offers.</div>';
            });
    })();
</script>
