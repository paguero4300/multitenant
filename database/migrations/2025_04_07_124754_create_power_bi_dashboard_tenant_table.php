<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('power_bi_dashboard_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('power_bi_dashboards')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Aseguramos que un dashboard solo se asigne una vez a cada tenant
            $table->unique(['dashboard_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('power_bi_dashboard_tenant');
    }
};
