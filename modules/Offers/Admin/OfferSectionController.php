<?php

namespace Modules\Offers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Offers\Models\OfferSection;

class OfferSectionController extends Controller
{
    public function index()
    {
        $sections = OfferSection::orderBy('sort_order')->paginate(20);
        return view('offers::admin.sections.index', compact('sections'));
    }

    public function create()
    {
        return view('offers::admin.sections.form');
    }

    public function edit($id)
    {
        $section = OfferSection::findOrFail($id);
        return view('offers::admin.sections.form', ['section' => $section]);
    }

    // Hotel-style: store/{id} handles both create (id=0) and update (id>0)
    public function store(Request $request, $id)
    {
        $rules = [
            'title'      => ['required','string','max:255'],
            'slug'       => ['required','alpha_dash','max:255', Rule::unique('offer_sections','slug')],
            'cta_text'   => ['nullable','string','max:255'],
            'cta_link'   => ['nullable','string','max:2048'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active'  => ['sometimes','boolean'],
        ];

        $section = null;
        if ($id && $id !== '0') {
            $section = OfferSection::findOrFail($id);
            // On update, ignore the current section id on unique slug
            $rules['slug'] = ['required','alpha_dash','max:255', Rule::unique('offer_sections','slug')->ignore($section->id)];
        }

        $v = $request->validate($rules);
        $v['is_active'] = $request->boolean('is_active');

        if ($section) {
            $section->update($v);
            $msg = 'Section updated.';
        } else {
            OfferSection::create($v);
            $msg = 'Section created.';
        }

        return redirect()->route('offers.admin.sections.index')->with('success', $msg);
    }

    // Optional endpoints to mimic Hotel signatures
    public function bulkEdit(Request $request)
    {
        $ids = (array)$request->input('ids', []);
        $action = $request->input('action');

        if (!$ids || !$action) {
            return back()->with('error','Please select at least one item and an action.');
        }

        switch ($action) {
            case 'delete':
                OfferSection::whereIn('id',$ids)->delete();
                break;
            case 'activate':
                OfferSection::whereIn('id',$ids)->update(['is_active'=>1]);
                break;
            case 'deactivate':
                OfferSection::whereIn('id',$ids)->update(['is_active'=>0]);
                break;
        }

        return back()->with('success','Bulk action applied.');
    }

    // Stubbed to mirror Hotel; update if you add SoftDeletes
    public function recovery()
    {
        // If you add SoftDeletes to OfferSection, list trashed here.
        return redirect()->route('offers.admin.sections.index');
    }

    // For select2 AJAX (id/text)
    public function getForSelect2(Request $request)
    {
        $q = trim($request->input('q',''));
        $items = OfferSection::when($q, fn($qq) => $qq->where('title','like',"%{$q}%"))
            ->orderBy('title')
            ->limit(20)
            ->get(['id','title as text']);

        return response()->json(['results'=>$items]);
    }
}
