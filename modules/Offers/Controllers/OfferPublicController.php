<?php

namespace Modules\Offers\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Offers\Models\OfferSection;

class OfferPublicController extends Controller
{
    // Render ALL active sections (sorted), with cards sorted
    public function index(Request $request)
    {
        $sections = OfferSection::active()
            ->orderBy('sort_order')
            ->with(['cards' => fn($q) => $q->active()->orderBy('sort_order')])
            ->get();

        $anchor = $request->query('anchor');          // e.g. "#hotel-search-form"
        $cols   = min(max((int)$request->query('cols', 3), 1), 4);
        $limit  = max((int)$request->query('limit', 0), 0); // 0 = all cards

        return view('offers::public.index', compact('sections','anchor','cols','limit'));
    }

    // Render a SINGLE section by slug (sorted), for async or embedding
    public function section(string $slug, Request $request)
    {
        $section = OfferSection::active()
            ->where('slug', $slug)
            ->with(['cards' => fn($q) => $q->active()->orderBy('sort_order')])
            ->firstOrFail();

        $heading = $request->query('heading');
        $anchor  = $request->query('anchor');
        $limit   = max(0, (int)$request->query('limit', 0));
        $cols    = min(max((int)$request->query('cols', 3), 1), 4);

        return view('offers::front.section', compact('section','heading','anchor','limit','cols'));
    }
}
