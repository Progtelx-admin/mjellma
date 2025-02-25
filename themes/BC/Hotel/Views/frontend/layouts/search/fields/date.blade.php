<div class="form-group">
    <i class="field-icon icofont-wall-clock"></i>
    <div class="form-content">
        <div class="form-date-search-hotel">
            <div class="date-wrapper">
                <div class="check-in-wrapper">
                    <label>{{ $field['title'] }}</label>
                    <div class="render check-in-render">
                        {{ request()->query('start', display_date(strtotime('today'))) }}
                    </div>
                    <span> - </span>
                    <div class="render check-out-render">
                        {{ request()->query('end', display_date(strtotime('+1 day'))) }}
                    </div>
                </div>
            </div>
            <input type="hidden" class="check-in-input" id="checkin" name="start"
                value="{{ request()->query('start', display_date(strtotime('today'))) }}">
            <input type="hidden" class="check-out-input" id="checkout" name="end"
                value="{{ request()->query('end', display_date(strtotime('+1 day'))) }}">
            <input type="text" class="check-in-out"
                value="{{ request()->query('date', date('Y-m-d') . ' - ' . date('Y-m-d', strtotime('+1 day'))) }}">
        </div>
    </div>
</div>
