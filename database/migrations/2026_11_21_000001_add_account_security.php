<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('two_factor_secret')->nullable()->after('remember_token');
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
                $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            });
        }

        if (! Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event', 80)->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('login_devices')) {
            Schema::create('login_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('fingerprint', 64);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('first_seen_at');
                $table->dateTime('last_seen_at');
                $table->unique(['user_id', 'fingerprint']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_devices');
        Schema::dropIfExists('security_audit_logs');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'password_changed_at',
        ]));
    }
};
