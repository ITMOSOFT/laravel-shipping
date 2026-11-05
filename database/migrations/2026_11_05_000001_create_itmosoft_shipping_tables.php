<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $p = config('itmosoft-shipping.table_prefix', 'shipping_');
        Schema::create($p.'methods', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('code')->unique(); $t->boolean('is_active')->default(true); $t->json('settings')->nullable(); $t->timestamps(); });
        Schema::create($p.'zones', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps(); });
        Schema::create($p.'zone_rules', function (Blueprint $t) use ($p) { $t->id(); $t->foreignId('zone_id')->constrained($p.'zones')->cascadeOnDelete(); $t->string('type'); $t->string('value'); $t->timestamps(); $t->index(['type', 'value']); });
        Schema::create($p.'rates', function (Blueprint $t) use ($p) { $t->id(); $t->foreignId('shipping_method_id')->constrained($p.'methods')->cascadeOnDelete(); $t->foreignId('zone_id')->constrained($p.'zones')->cascadeOnDelete(); $t->string('type'); $t->decimal('amount', 12, 4); $t->string('currency', 3); $t->decimal('min_value', 12, 4)->nullable(); $t->decimal('max_value', 12, 4)->nullable(); $t->timestamps(); });
        Schema::create($p.'shipments', function (Blueprint $t) use ($p) { $t->id(); $t->unsignedBigInteger('order_id')->index(); $t->foreignId('shipping_method_id')->nullable()->constrained($p.'methods')->nullOnDelete(); $t->string('status')->default('pending'); $t->string('tracking_number')->nullable()->index(); $t->json('recipient'); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create($p.'shipment_events', function (Blueprint $t) use ($p) { $t->id(); $t->foreignId('shipment_id')->constrained($p.'shipments')->cascadeOnDelete(); $t->string('status'); $t->text('description')->nullable(); $t->json('metadata')->nullable(); $t->timestamps(); });
    }
    public function down(): void
    {
        $p = config('itmosoft-shipping.table_prefix', 'shipping_');
        foreach (['shipment_events','shipments','rates','zone_rules','zones','methods'] as $table) Schema::dropIfExists($p.$table);
    }
};
