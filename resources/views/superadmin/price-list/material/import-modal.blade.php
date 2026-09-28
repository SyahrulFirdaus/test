{{--
    Modal "Import Material" — memakai modal Import Excel bersama.

    Diharapkan:
      $technology  App\Models\PrintTechnology
      $label       nama teknologi pada teks, mis. "FDM"
--}}
@include('superadmin.price-list.excel.import-modal', [
    'title' => 'Import Material '.$label,
    'routePrefix' => 'superadmin.price-list.materials.excel',
    'routeParams' => [$technology],
])
