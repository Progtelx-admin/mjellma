@if(empty($hide_form_search))
    <div class="g-form-control">
        <ul class="nav nav-tabs" role="tablist">
            @if(!empty($service_types))
                @php $number = 0; @endphp
                @foreach ($service_types as $service_type)
                    @php
                        $allServices = get_bookable_services();
                        if(empty($allServices[$service_type])) continue;
                        $module = new $allServices[$service_type];
                    @endphp
                    <li role="bravo_{{$service_type}}">
                        <a href="#bravo_{{$service_type}}" class="@if($number == 0) active @endif" aria-controls="bravo_{{$service_type}}" role="tab" data-toggle="tab">
                            <i class="{{ $module->getServiceIconFeatured() }}"></i>
                            {{ !empty($modelBlock["title_for_".$service_type]) ? $modelBlock["title_for_".$service_type] : $module->getModelName() }}
                        </a>
                    </li>
                    @php $number++; @endphp
                @endforeach
            @endif
        </ul>
        <div class="tab-content">
            @if(!empty($service_types))
                @php $number = 0; @endphp
                @foreach ($service_types as $service_type)
                    @php
                        $allServices = get_bookable_services();
                        if(empty($allServices[$service_type])) continue;
                        $module = new $allServices[$service_type];
                    @endphp
                    <div role="tabpanel" class="tab-pane @if($number == 0) active @endif" id="bravo_{{$service_type}}">
                        @include(ucfirst($service_type).'::frontend.layouts.search.form-search')
                    </div>
                    @php $number++; @endphp
                @endforeach
            @endif
            <div role="tabpanel" class="tab-pane active" id="thomalex">
                <div id="thomalex-widget" data-widget="https://MjellmaTravel.resvoyage.com/widget/index?widgetId=b6f09e37-6e72-43cc-9da6-583d693a12fb&lang=en-US" style="height:550px;"></div>
                <script src="https://MjellmaTravel.resvoyage.com/scripts/thomalex-integration.js"></script>
            </div>
        </div> <!-- Closing for tab-content -->
    </div> <!-- Closing for g-form-control -->
@endif
