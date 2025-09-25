<?php

namespace Modules\Offers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Modules\Offers\Models\OfferCard;
use Modules\Offers\Models\OfferSection;

class OfferCardController extends Controller
{
    public function index(Request $request, $section_id = null)
    {
        // accept either /cards?section_id=1 or /card/1/index
        $sectionId = $section_id ?? (int) $request->query('section_id');
        $section   = OfferSection::findOrFail($sectionId);

        $cards = OfferCard::where('offer_section_id', $section->id)
            ->orderBy('sort_order')
            ->paginate(30);

        return view('offers::admin.cards.index', compact('section','cards'));
    }

    public function create(Request $request, $section_id = null)
    {
        $sectionId = $section_id ?? (int) $request->query('section_id');
        $section   = OfferSection::findOrFail($sectionId);

        $card = new OfferCard(['offer_section_id' => $section->id]);
        return view('offers::admin.cards.form', compact('section','card'));
    }

    public function edit(OfferCard $card)
    {
        $section = $card->section;
        return view('offers::admin.cards.form', compact('section','card'));
    }

    public function store(Request $request, $id = null)
    {
        $rules = [
            'offer_section_id' => 'required|exists:offer_sections,id',
            'title'            => 'nullable|string|max:255',
            'link'             => 'nullable|string|max:2000',
            'image'            => 'nullable|image|max:4096',
            'sort_order'       => 'nullable|integer|min:0',
            'show_caption'     => 'nullable|boolean',
            'is_active'        => 'nullable|boolean',
        ];
        $data = $request->validate($rules);

        $card = $id ? OfferCard::findOrFail($id) : new OfferCard();

        $card->offer_section_id = (int) $data['offer_section_id'];
        $card->title            = $data['title']        ?? null;
        $card->link             = $data['link']         ?? null;
        $card->sort_order       = $data['sort_order']   ?? 0;
        $card->show_caption     = $request->boolean('show_caption');
        $card->is_active        = $request->boolean('is_active', true);

        // save into /public/uploads/offer-cards (as you already implemented)
        if ($request->hasFile('image')) {
            $dir = 'uploads/offer-cards';
            $abs = public_path($dir);
            if (!is_dir($abs)) @mkdir($abs, 0775, true);

            if ($card->exists && $card->image_path && str_starts_with($card->image_path, 'uploads/')) {
                $old = public_path($card->image_path);
                if (is_file($old)) @unlink($old);
            }
            if ($card->exists && $card->image_path && Storage::disk('public')->exists($card->image_path)) {
                @Storage::disk('public')->delete($card->image_path);
            }

            $file = $request->file('image');
            $name = Str::random(40).'.'.$file->getClientOriginalExtension();
            $file->move($abs, $name);

            $card->image_path = $dir.'/'.$name;
        }

        $card->save();

        // Redirect to the path-param version so URLs look like /card/{section_id}/index
        return redirect()
            ->route('offers.admin.cards.index.by_section', ['section_id' => $card->offer_section_id])
            ->with('success', $id ? __('Card updated') : __('Card created'));
    }

    public function destroy(OfferCard $card)
    {
        if ($card->image_path && str_starts_with($card->image_path, 'uploads/')) {
            $fp = public_path($card->image_path);
            if (is_file($fp)) @unlink($fp);
        }
        if ($card->image_path && Storage::disk('public')->exists($card->image_path)) {
            @Storage::disk('public')->delete($card->image_path);
        }

        $sectionId = $card->offer_section_id;
        $card->delete();

        return redirect()
            ->route('offers.admin.cards.index.by_section', ['section_id' => $sectionId])
            ->with('success', __('Card deleted'));
    }
}
