@if ($paginator->hasPages())
    <nav aria-label="{{__('Payment order pages')}}">
        <ul class="car-orders-pagination">
            @if($paginator->onFirstPage())
                <li class="disabled"><span>{{__('Previous')}}</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{__('Previous')}}</a></li>
            @endif
            @foreach($elements as $element)
                @if(is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @else
                    @foreach($element as $page => $url)
                        @if($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">{{__('Next')}}</a></li>
            @else
                <li class="disabled"><span>{{__('Next')}}</span></li>
            @endif
        </ul>
    </nav>
@endif
