<div class="row">
    <div class="col-lg-3 col-md-12">
        @include('Hotel::frontend.layouts.search.filter-search')
    </div>
    <div class="col-lg-9 col-md-12">
        <div class="bravo-list-item">
            <div class="topbar-search">
                <h2 class="text result-count">
                    @if ($rows->total() > 1)
                        {{ __(':count hotels found', ['count' => $rows->total()]) }}
                    @else
                        {{ __(':count hotel found', ['count' => $rows->total()]) }}
                    @endif
                </h2>
                <div class="control bc-form-order">
                    @include('Layout::global.search.orderby', ['routeName' => 'tour.search'])
                </div>
            </div>
            <div class="ajax-search-result">
                @include('Hotel::frontend.ajax.search-result')
            </div>
        </div>
    </div>
</div>
