<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_entrants', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('name');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('identity_type')->nullable()->after('contact_phone');
            $table->string('identity_number')->nullable()->after('identity_type');
            $table->string('identity_document_path')->nullable()->after('identity_number');
            $table->string('kyc_status')->default('pending')->after('identity_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_entrants', function (Blueprint $table) {
            $table->dropColumn([
                'contact_name',
                'contact_phone',
                'identity_type',
                'identity_number',
                'identity_document_path',
                'kyc_status',
            ]);
        });
    }
};
