<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackagingItemRequest;
use App\Models\PackagingItem;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** CRUD packaging pada Price List — belum dipakai menghitung biaya Calculator. */
class PackagingItemController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(): View
    {
        return view('superadmin.price-list.packaging.form', ['packagingItem' => new PackagingItem]);
    }

    public function store(StorePackagingItemRequest $request): RedirectResponse
    {
        $item = PackagingItem::create($request->validated());

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menambahkan packaging "'.$item->item.'" pada Price List.',
            subject: $item,
            new: $this->snapshot($item),
            subjectLabel: $item->item,
        );

        return redirect()
            ->route('superadmin.price-list.packaging.index')
            ->with('status', 'Packaging berhasil ditambahkan.');
    }

    public function edit(PackagingItem $packagingItem): View
    {
        return view('superadmin.price-list.packaging.form', ['packagingItem' => $packagingItem]);
    }

    public function update(StorePackagingItemRequest $request, PackagingItem $packagingItem): RedirectResponse
    {
        $before = $this->snapshot($packagingItem);

        $packagingItem->update($request->validated());

        $this->activity->logChanges(
            action: ActivityAction::PRICE_LIST_UPDATE,
            before: $before,
            after: $this->snapshot($packagingItem->refresh()),
            description: 'Memperbarui packaging "'.$packagingItem->item.'" pada Price List.',
            subject: $packagingItem,
            subjectLabel: $packagingItem->item,
        );

        return redirect()
            ->route('superadmin.price-list.packaging.index')
            ->with('status', 'Packaging berhasil diperbarui.');
    }

    public function destroy(PackagingItem $packagingItem): RedirectResponse
    {
        $removed = $this->snapshot($packagingItem);
        $label = $packagingItem->item;

        $packagingItem->delete();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menghapus packaging "'.$label.'" dari Price List.',
            old: $removed,
            subjectLabel: $label,
        );

        return redirect()
            ->route('superadmin.price-list.packaging.index')
            ->with('status', 'Packaging berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function snapshot(PackagingItem $item): array
    {
        return [
            'item' => $item->item,
            'ukuran' => $item->ukuran,
            'dimensi' => $item->dimensi,
            'price' => (float) $item->price,
            'price_unit' => $item->price_unit,
        ];
    }
}
