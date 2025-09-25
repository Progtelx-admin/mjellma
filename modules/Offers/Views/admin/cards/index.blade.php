@extends('admin.layouts.app')

@section('content')
    @include('admin.message')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Cards – {{ $section->title }}</h1>
            <div class="text-muted small">Section slug: <code>{{ $section->slug }}</code></div>
        </div>
        <div>
            <a href="{{ route('offers.admin.sections.index') }}" class="btn btn-light">Back to Sections</a>

            {{-- Create card (query-param style) --}}
            <a href="{{ route('offers.admin.cards.create', ['section_id' => $section->id]) }}" class="btn btn-primary">
                New Card
            </a>
            {{-- If you also added the path-param route, you could use:
                 route('offers.admin.cards.create.by_section', ['section_id' => $section->id]) --}}
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:80px">Order</th>
                        <th style="width:90px">Image</th>
                        <th>Title</th>
                        <th>Link</th>
                        <th style="width:120px">Caption?</th>
                        <th style="width:100px">Active?</th>
                        <th style="width:160px" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $card->sort_order }}</span>
                            </td>
                            <td>
                                @if (!empty($card->image_url))
                                    <img src="{{ $card->image_url }}" alt="img" style="height:60px" class="rounded">
                                @endif
                            </td>
                            <td>{{ $card->title }}</td>
                            <td class="text-truncate" style="max-width:320px;">
                                @if ($card->link)
                                    <a href="{{ $card->link }}" target="_blank" rel="noopener">{{ $card->link }}</a>
                                @endif
                            </td>
                            <td>
                                @if ($card->show_caption)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-light text-dark">No</span>
                                @endif
                            </td>
                            <td>
                                @if ($card->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Off</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('offers.admin.cards.edit', $card) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>
                                <form action="{{ route('offers.admin.cards.destroy', $card) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Delete this card?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No cards in this section yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($cards, 'links'))
            <div class="card-footer">
                {{ $cards->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
