<?php

namespace Modules\Offers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Modules\Offers\Models\OfferCard;
use Modules\Offers\Models\OfferSection;

class OfferCardController extends Controller
{
    public function index(Request $request, $section_id = null)
    {
        // Accept either: /cards?section_id=1  OR  /card/1/index
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

    /**
     * Accepts either an ID or an implicitly-bound OfferCard (prevents 500s).
     */
    public function edit($card)
    {
        $card = $card instanceof OfferCard ? $card : OfferCard::findOrFail($card);
        $section = $card->section;

        return view('offers::admin.cards.form', compact('section','card'));
    }

    /**
     * Unified create/update via POST /store or POST /store/{id}
     */
    public function store(Request $request, $id = null)
    {
        $rules = [
            'offer_section_id' => 'required|exists:offer_sections,id',
            'title'            => 'nullable|string|max:255',
            'link'             => 'nullable|string|max:2000', // allow #anchors too
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

        // Save image into /public/uploads/offer-cards
        if ($request->hasFile('image')) {
            $dir = 'uploads/offer-cards';
            $abs = public_path($dir);
            if (!is_dir($abs)) {
                @mkdir($abs, 0775, true);
            }

            // delete previous local upload
            if ($card->exists && $card->image_path && str_starts_with($card->image_path, 'uploads/')) {
                $old = public_path($card->image_path);
                if (is_file($old)) @unlink($old);
            }
            // delete legacy file on public disk if any
            if ($card->exists && $card->image_path && Storage::disk('public')->exists($card->image_path)) {
                @Storage::disk('public')->delete($card->image_path);
            }

            $file = $request->file('image');
            $name = Str::random(40).'.'.$file->getClientOriginalExtension();
            $file->move($abs, $name);

            $card->image_path = $dir.'/'.$name; // e.g. uploads/offer-cards/xxx.png
        }

        $card->save();

        return $this->redirectToSectionCards($card->offer_section_id)
            ->with('success', $id ? __('Card updated') : __('Card created'));
    }

    /**
     * Delete a card and its local image (uploads or legacy storage),
     * then redirect to the section's cards list (path-param if available).
     */
    public function destroy(OfferCard $card)
    {
        $sectionId = $card->offer_section_id;
        $path      = $card->image_path;

        if ($path && !filter_var($path, FILTER_VALIDATE_URL)) {
            // local upload under /public/uploads/...
            if (str_starts_with($path, 'uploads/')) {
                $abs = public_path($path);
                if (is_file($abs)) {
                    try { @unlink($abs); } catch (\Throwable $e) { /* ignore */ }
                }
            }
            // legacy on 'public' disk
            if (Storage::disk('public')->exists($path)) {
                try { Storage::disk('public')->delete($path); } catch (\Throwable $e) { /* ignore */ }
            }
        }

        $card->delete();

        return $this->redirectToSectionCards($sectionId)
            ->with('success', __('Card deleted'));
    }

    /**
     * Prefer path-param route (/card/{section_id}/index), else query-param route (/cards?section_id=1),
     * else fallback to sections index.
     */
    protected function redirectToSectionCards(int $sectionId)
    {
        if (Route::has('offers.admin.cards.index.by_section')) {
            return redirect()->route('offers.admin.cards.index.by_section', ['section_id' => $sectionId]);
        }
        if (Route::has('offers.admin.cards.index')) {
            return redirect()->route('offers.admin.cards.index', ['section_id' => $sectionId]);
        }
        return redirect()->route('offers.admin.sections.index');
    }
}
