@extends('admin.layouts.app')
@section('content')
    @include('admin.message')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ isset($section) ? 'Edit' : 'New' }} Offer Section</h1>
        <a href="{{ route('offers.admin.sections.index') }}" class="btn btn-light">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('offers.admin.sections.store', isset($section) ? $section->id : 0) }}">
                @csrf
                {{-- Hotel-style: no PUT, store/{id} handles both create (id=0) and update (id>0) --}}

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title</label>
                        <input name="title" class="form-control" required
                            value="{{ old('title', $section->title ?? '') }}">
                        @error('title')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Slug</label>
                        <input name="slug" class="form-control" required value="{{ old('slug', $section->slug ?? '') }}">
                        @error('slug')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">CTA Text</label>
                        <input name="cta_text" class="form-control" value="{{ old('cta_text', $section->cta_text ?? '') }}">
                        @error('cta_text')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">CTA Link</label>
                        <input name="cta_link" class="form-control" value="{{ old('cta_link', $section->cta_link ?? '') }}">
                        @error('cta_link')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                            value="{{ old('sort_order', $section->sort_order ?? 0) }}">
                        @error('sort_order')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                {{ old('is_active', $section->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('offers.admin.sections.index') }}" class="btn btn-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
