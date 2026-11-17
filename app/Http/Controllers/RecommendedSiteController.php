<?php

namespace App\Http\Controllers;

use App\Models\RecommendedSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RecommendedSiteController extends Controller
{
    /**
     * Display a listing of active recommended sites for the public dashboard.
     */
    public function index(): JsonResponse
    {
        $sites = RecommendedSite::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $sites,
        ]);
    }

    /**
     * Display a full listing of recommended sites for admin management.
     */
    public function adminIndex(): JsonResponse
    {
        $sites = RecommendedSite::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $sites,
        ]);
    }

    /**
     * Store a newly created recommended site.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSite($request);

        $validated['cta_text'] = !empty($validated['cta_text']) ? $validated['cta_text'] : 'Kunjungi website';
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $site = RecommendedSite::create($validated);

        return response()->json([
            'data' => $site,
            'message' => 'Rekomendasi website berhasil ditambahkan.',
        ], 201);
    }

    /**
     * Update the specified recommended site.
     */
    public function update(Request $request, RecommendedSite $site): JsonResponse
    {
        $validated = $this->validateSite($request);

        $validated['cta_text'] = !empty($validated['cta_text']) ? $validated['cta_text'] : 'Kunjungi website';
        if ($request->has('is_featured')) {
            $validated['is_featured'] = $request->boolean('is_featured');
        }
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $site->update($validated);

        return response()->json([
            'data' => $site->fresh(),
            'message' => 'Rekomendasi website berhasil diperbarui.',
        ]);
    }

    /**
     * Toggle the active status of the specified recommended site.
     */
    public function toggleActive(RecommendedSite $site): JsonResponse
    {
        $site->is_active = !$site->is_active;
        $site->save();

        return response()->json([
            'data' => $site,
            'is_active' => $site->is_active,
            'message' => $site->is_active ? 'Website berhasil diaktifkan.' : 'Website berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Remove the specified recommended site from storage.
     */
    public function destroy(RecommendedSite $site): Response
    {
        $site->delete();

        return response()->noContent();
    }

    /**
     * Validate incoming site payload.
     */
    protected function validateSite(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'badge' => ['nullable', 'string', 'max:40'],
            'poster_name' => ['required', 'string', 'max:80'],
            'poster_subtitle' => ['nullable', 'string', 'max:60'],
            'poster_image_url' => ['required', 'string', 'starts_with:http://,https://,/images/'],
            'background_image_url' => ['nullable', 'string', 'starts_with:http://,https://,/images/'],
            'url' => ['required', 'url'],
            'cta_text' => ['nullable', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:600'],
            'chips' => ['nullable', 'array'],
            'chips.*' => ['string', 'max:30'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}

