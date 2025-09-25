@extends('admin.layouts.app')

@section('content')
    @include('admin.message')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Offer Sections</h1>
        <a href="{{ route('offers.admin.sections.create') }}" class="btn btn-primary">New Section</a>
    </div>

    <form method="POST" action="{{ route('offers.admin.sections.bulkEdit') }}">
        @csrf

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width:40px">
                                <input type="checkbox"
                                    onclick="document.querySelectorAll('input[name*=ids]').forEach(cb=>cb.checked=this.checked)">
                            </th>
                            <th style="width:70px">ID</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th style="width:120px">Active</th>
                            <th style="width:100px">Order</th>
                            <th class="text-end" style="width:280px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $s)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $s->id }}"></td>
                                <td>{{ $s->id }}</td>
                                <td>{{ $s->title }}</td>
                                <td><code>{{ $s->slug }}</code></td>
                                <td>{{ $s->is_active ? 'Yes' : 'No' }}</td>
                                <td>{{ $s->sort_order }}</td>
                                <td class="text-end">
                                    {{-- Cards list --}}
                                    @if (Route::has('offers.admin.cards.index.by_section'))
                                        <a href="{{ route('offers.admin.cards.index.by_section', ['section_id' => $s->id]) }}"
                                            class="btn btn-sm btn-primary">
                                            Cards
                                        </a>
                                    @else
                                        <a href="{{ route('offers.admin.cards.index', ['section_id' => $s->id]) }}"
                                            class="btn btn-sm btn-primary">
                                            Cards
                                        </a>
                                    @endif

                                    {{-- New card --}}
                                    @if (Route::has('offers.admin.cards.create.by_section'))
                                        <a href="{{ route('offers.admin.cards.create.by_section', ['section_id' => $s->id]) }}"
                                            class="btn btn-sm btn-success">
                                            New Card
                                        </a>
                                    @else
                                        <a href="{{ route('offers.admin.cards.create', ['section_id' => $s->id]) }}"
                                            class="btn btn-sm btn-success">
                                            New Card
                                        </a>
                                    @endif

                                    <a href="{{ route('offers.admin.sections.edit', $s->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>

                                    <form action="{{ route('offers.admin.sections.destroy', $s->id) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Delete this section?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No sections yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($sections, 'links'))
                <div class="card-footer">
                    {{ $sections->links() }}
                </div>
            @endif
        </div>

        <div class="mt-3 d-flex gap-2">
            <select name="action" class="form-select w-auto">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
            </select>
            <button class="btn btn-secondary">Apply</button>
        </div>
    </form>
@endsection
