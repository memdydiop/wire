<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            
            // Relation avec l'expéditeur
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('email')->index();
            $table->string('token', 64)->nullable()->unique();
            $table->string('role', 50)->default('user');
            
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Index composés pour les requêtes fréquentes
            $table->index(['email', 'expires_at']);
            $table->index(['token', 'expires_at', 'accepted_at']);
            $table->index(['sent_by', 'created_at']);
            
            // Index partiel pour les invitations en attente
            $table->index(['expires_at'], 'idx_pending_invitations')
                  ->whereNull('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};