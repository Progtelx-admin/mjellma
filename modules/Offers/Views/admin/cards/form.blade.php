@extends('admin.layouts.app')

@section('content')
    @include('admin.message')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $card->exists ? 'Edit' : 'New' }} Offer Card</h1>
        <a href="{{ route('offers.admin.cards.index', ['section_id' => $section->id]) }}" class="btn btn-light">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data"
                action="{{ $card->exists ? route('offers.admin.cards.update', $card) : route('offers.admin.cards.store') }}">
                @csrf
                @if ($card->exists)
                    @method('PUT')
                @endif

                <input type="hidden" name="offer_section_id" value="{{ $section->id }}" />

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title</label>
                        <input name="title" class="form-control" value="{{ old('title', $card->title) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Link (URL or #anchor)</label>
                        <input name="link" class="form-control" value="{{ old('link', $card->link) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                    @if (!empty($card->image_url))
                        <div class="mt-2">
                            <img src="{{ $card->image_url }}" alt="preview" style="max-height:120px">
                        </div>
                    @endif
                    @error('image')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                            value="{{ old('sort_order', $card->sort_order ?? 0) }}">
                    </div>

                    <div class="col-md-3 mb-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="show_caption" name="show_caption"
                                value="1" {{ old('show_caption', $card->show_caption) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_caption">Show Caption</label>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                {{ old('is_active', $card->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection
