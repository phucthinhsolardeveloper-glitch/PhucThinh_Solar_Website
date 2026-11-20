<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which top-level product categories a brand belongs to. The category page shows the logos of its related brands in the
 * header, also for brands that have no product there yet. Set in Admin → Thương hiệu → Danh mục liên quan; the first set
 * below follows what each brand makes (panels, inverters, storage).
 */
return new class extends Migration
{
    private const SEED = [
        'tam-pin-nang-luong-mat-troi' => ['Canadian Solar', 'AESOLAR', 'TCL Solar', 'Astronergy', 'LONGi', 'Sharp'],
        'bien-tan-inverter' => ['Sungrow', 'Huawei', 'Growatt', 'GoodWe', 'SOFAR', 'Deye', 'SolaX', 'Sigenergy', 'Anker SOLIX'],
        'pin-luu-tru' => ['Sungrow', 'Huawei', 'Growatt', 'GoodWe', 'Deye', 'SolaX', 'Sigenergy', 'Anker SOLIX'],
    ];

    public function up(): void
    {
        Schema::create('brand_category', function (Blueprint $t) {
            $t->unsignedBigInteger('brand_id');
            $t->unsignedBigInteger('category_id');
            $t->primary(['brand_id', 'category_id']);
        });
        foreach (self::SEED as $slug => $names) {
            $category = DB::table('categories')->where('slug', $slug)->whereNull('parent_id')->value('id');
            if (! $category) {
                continue;
            }
            foreach (DB::table('brands')->whereIn('name', $names)->pluck('id') as $brand) {
                DB::table('brand_category')->insertOrIgnore(['brand_id' => $brand, 'category_id' => $category]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_category');
    }
};
