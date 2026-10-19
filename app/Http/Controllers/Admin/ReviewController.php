<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Testimonial;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menu Ulasan Google: ulasan yang tampil di bagian "Kata Klien" halaman Home,
 * beserta tautan "Lihat semua ulasan di Google".
 *
 * Ulasan disalin manual dari Google Business Profile (tanpa API berbayar):
 * nama pengulas, bintang, isi, dan tanggal ulasannya. Tanggal ditampilkan
 * relatif ("2 minggu lalu") sehingga tidak pernah basi.
 */
class ReviewController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(): View
    {
        return view('admin.reviews.index', [
            'reviews' => Testimonial::query()->ordered()->get(),
            'profile' => CompanyProfile::current(),
        ]);
    }

    public function create(): View
    {
        return view('admin.reviews.form', ['review' => new Testimonial([
            'rating' => 5,
            'source' => 'Google Review',
            'is_active' => true,
            'reviewed_at' => now(),
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] ??= (int) Testimonial::max('sort_order') + 1;

        $review = Testimonial::create($data);

        $this->activity->log(
            action: ActivityAction::REVIEW_CREATE,
            description: 'Menambahkan ulasan dari "'.$review->name.'".',
            subject: $review,
            new: $this->snapshot($review),
        );

        return redirect()->route(staff_route_name('reviews.index'))->with('status', 'Ulasan berhasil ditambahkan.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.reviews.form', ['review' => $testimonial]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $before = $this->snapshot($testimonial);

        $testimonial->update($this->validated($request));

        $this->activity->logChanges(
            action: ActivityAction::REVIEW_UPDATE,
            before: $before,
            after: $this->snapshot($testimonial->refresh()),
            description: 'Memperbarui ulasan dari "'.$testimonial->name.'".',
            subject: $testimonial,
        );

        return redirect()->route(staff_route_name('reviews.index'))->with('status', 'Ulasan berhasil diperbarui.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $removed = $this->snapshot($testimonial);
        $name = $testimonial->name;

        $testimonial->delete();

        $this->activity->log(
            action: ActivityAction::REVIEW_DELETE,
            description: 'Menghapus ulasan dari "'.$name.'".',
            old: $removed,
            subjectLabel: $name,
        );

        return redirect()->route(staff_route_name('reviews.index'))->with('status', 'Ulasan berhasil dihapus.');
    }

    /** Tautan "Lihat semua ulasan di Google". */
    public function updateLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'google_reviews_url' => ['nullable', 'url:https', 'max:500', 'regex:#^https://([a-z0-9-]+\.)*(google\.[a-z.]+|goo\.gl|g\.page|maps\.app\.goo\.gl)(/|$)#i'],
        ], [
            'google_reviews_url.url' => 'Masukkan URL lengkap yang diawali https://.',
            'google_reviews_url.regex' => 'Gunakan tautan dari Google (google.com, g.page, atau maps.app.goo.gl).',
        ]);

        $profile = CompanyProfile::current();
        abort_if($profile === null, 404);

        $before = $profile->google_reviews_url;
        $profile->update(['google_reviews_url' => $data['google_reviews_url'] ?? null]);

        $this->activity->log(
            action: ActivityAction::REVIEW_UPDATE,
            description: 'Mengubah tautan "Lihat semua ulasan di Google".',
            old: ['google_reviews_url' => $before],
            new: ['google_reviews_url' => $profile->google_reviews_url],
            subjectLabel: 'Tautan ulasan Google',
        );

        return back()->with('status', 'Tautan ulasan Google berhasil disimpan.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'quote' => ['required', 'string', 'max:3000'],
            'reviewed_at' => ['required', 'date', 'before_or_equal:today'],
            'source' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama pengulas wajib diisi.',
            'quote.required' => 'Isi ulasan wajib diisi.',
            'reviewed_at.required' => 'Tanggal ulasan wajib diisi.',
            'reviewed_at.before_or_equal' => 'Tanggal ulasan tidak boleh di masa depan.',
            'rating.between' => 'Bintang antara 1 sampai 5.',
        ]);

        $data['source'] = filled($data['source'] ?? null) ? $data['source'] : 'Google Review';
        $data['is_active'] = $request->boolean('is_active');
        // Tanggal kini menjadi sumber "x waktu lalu"; teks tetap lama tidak dipakai lagi.
        $data['reviewed_label'] = null;

        return $data;
    }

    /** @return array<string, mixed> */
    private function snapshot(Testimonial $review): array
    {
        return [
            'name' => $review->name,
            'rating' => $review->rating,
            'reviewed_at' => $review->reviewed_at?->toDateString(),
            'is_active' => $review->is_active ? 'Tampil' : 'Disembunyikan',
            'sort_order' => $review->sort_order,
        ];
    }
}
