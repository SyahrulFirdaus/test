<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Lead Management (LD-01).
 *
 * Satu baris `leads` = satu calon pelanggan beserta sumber kedatangannya
 * (GCLID/UTM first touch). Lead berasal dari Form landing page layanan atau
 * dari Quotation; satu lead dapat memiliki beberapa quotation lewat
 * `quotation_requests.lead_id`, sehingga data pelanggan dan quotation tidak
 * pernah diduplikasi.
 *
 * Status lead (lead/quotation/deal/batal) dan nilai deal milik tabel ini
 * sendiri — terpisah dari status quotation, pembayaran, dan produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->comment('form | quotation — asal lead pertama kali tercatat');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Kontak
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('service', 80)->nullable();
            $table->text('notes')->nullable();

            // Halaman: tempat form/quotation dikirim, serta halaman masuk & referrer first touch.
            $table->string('source_url', 500)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();

            // Tracking first touch — tidak pernah ditimpa setelah tersimpan.
            $table->string('gclid')->nullable();
            $table->string('gbraid')->nullable();
            $table->string('wbraid')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->timestamp('first_touch_at')->nullable();

            // Pengelolaan
            $table->string('status', 20)->default('lead')->comment('lead | quotation | deal | batal');
            $table->unsignedBigInteger('deal_value')->nullable()->comment('Rupiah, diisi manual oleh admin');

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('source');
            $table->index('email');
            $table->index('whatsapp');
            $table->index('utm_campaign');
        });

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_id');
        });

        Schema::dropIfExists('leads');
    }

    /**
     * Quotation yang sudah ada dijadikan lead berstatus "Quotation".
     *
     * Quotation dari kontak yang sama (akun, atau email bila tanpa akun)
     * digabung ke satu lead. Data tracking dibiarkan kosong — sumber
     * kedatangan data lama memang tidak pernah tercatat, jadi tidak dikarang.
     */
    private function backfill(): void
    {
        $groups = [];

        DB::table('quotation_requests')
            ->orderBy('created_at')
            ->orderBy('id')
            ->select(['id', 'user_id', 'name', 'company', 'email', 'whatsapp', 'created_at'])
            ->chunk(500, function ($quotations) use (&$groups) {
                foreach ($quotations as $quotation) {
                    $key = $quotation->user_id
                        ? 'user:'.$quotation->user_id
                        : 'email:'.mb_strtolower(trim((string) $quotation->email));

                    $groups[$key] ??= ['first' => $quotation, 'ids' => []];
                    $groups[$key]['ids'][] = $quotation->id;
                }
            });

        foreach ($groups as $group) {
            $first = $group['first'];
            $createdAt = $first->created_at ?? Carbon::now();

            $leadId = DB::table('leads')->insertGetId([
                'source' => 'quotation',
                'user_id' => $first->user_id,
                'name' => $first->name,
                'company' => $first->company ?: null,
                'email' => $first->email ? mb_strtolower(trim($first->email)) : null,
                'whatsapp' => $this->phone($first->whatsapp),
                'service' => '3D Printing',
                'status' => 'quotation',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach (array_chunk($group['ids'], 500) as $ids) {
                DB::table('quotation_requests')->whereIn('id', $ids)->update(['lead_id' => $leadId]);
            }
        }
    }

    /** Sama dengan App\Models\Lead::normalizePhone(), ditulis ulang agar migrasi tidak bergantung pada model. */
    private function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }
};
