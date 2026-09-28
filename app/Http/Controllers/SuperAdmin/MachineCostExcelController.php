<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Services\PriceList\Excel\MachineCostDataset;
use App\Services\PriceList\Excel\SpreadsheetDataset;

/** Import & Export Excel menu Price List → Machine Cost. */
class MachineCostExcelController extends DatasetExcelController
{
    protected function dataset(): SpreadsheetDataset
    {
        return app(MachineCostDataset::class);
    }

    protected function indexUrl(): string
    {
        return route('superadmin.price-list.machine-cost.index');
    }

    protected function routePrefix(): string
    {
        return 'superadmin.price-list.machine-cost.excel';
    }

    protected function group(): string
    {
        return 'Machine Cost';
    }
}
