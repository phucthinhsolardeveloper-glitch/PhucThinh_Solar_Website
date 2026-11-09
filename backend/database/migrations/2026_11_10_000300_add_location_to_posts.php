<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Projects are placed by province and ward from the current administrative list; "address" keeps only
// the street / village detail. Free-text places already written are matched where they are unambiguous.
return new class extends Migration
{
    private const ALIASES = ['tp.hcm' => 'hồ chí minh', 'tphcm' => 'hồ chí minh', 'hcm' => 'hồ chí minh', 'sài gòn' => 'hồ chí minh',
        'tp. hồ chí minh' => 'hồ chí minh', 'thành phố hồ chí minh' => 'hồ chí minh', 'hà nội' => 'hà nội', 'tp hà nội' => 'hà nội'];

    public function up(): void
    {
        Schema::table('posts', function (Blueprint $t) {
            $t->foreignId('province_id')->nullable()->after('address')->constrained()->nullOnDelete();
            $t->foreignId('ward_id')->nullable()->after('province_id')->constrained()->nullOnDelete();
        });

        $provinces = DB::table('provinces')->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);
        foreach (DB::table('posts')->where('kind', 'project')->whereNotNull('address')->where('address', '!=', '')->get(['id', 'address']) as $post) {
            $parts = array_values(array_filter(array_map('trim', preg_split('/\s*[-,]\s*/u', (string) $post->address) ?: [])));
            if ($parts === []) {
                continue;
            }
            $last = mb_strtolower(array_pop($parts));
            $provinceId = $provinces[self::ALIASES[$last] ?? $last] ?? null;
            if (! $provinceId) {
                continue;
            }
            $wardId = null;
            if (count($parts) === 1) {
                $wardId = DB::table('wards')->where('province_id', $provinceId)->where('name', $parts[0])->value('id');
            }
            DB::table('posts')->where('id', $post->id)->update([
                'province_id' => $provinceId, 'ward_id' => $wardId,
                'address' => $parts === [] || $wardId ? null : implode(', ', $parts),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ward_id');
            $t->dropConstrainedForeignId('province_id');
        });
    }
};
