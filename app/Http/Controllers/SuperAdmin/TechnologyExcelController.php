<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Services\PriceList\Excel\SpreadsheetDataset;
use App\Services\PriceList\Excel\TechnologyDataset;

/** Import & Export Excel menu Price List → Teknologi. */
class TechnologyExcelController extends DatasetExcelController
{
    protected function dataset(): SpreadsheetDataset
    {
        return app(TechnologyDataset::class);
    }

    protected function indexUrl(): string
    {
        return route('superadmin.price-list.technologies.index');
    }

    protected function routePrefix(): string
    {
        return 'superadmin.price-list.technologies.excel';
    }

    protected function group(): string
    {
        return 'Teknologi';
    }
}
