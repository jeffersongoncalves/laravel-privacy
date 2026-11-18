<?php

namespace JeffersonGoncalves\Privacy\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('document')->nullable();
            $table->timestamps();
        });

        Schema::create('alpha_tombstones', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('model_id');
            $table->string('column');
            $table->string('fingerprint');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('alpha_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('columns')->nullable();
            $table->string('purpose')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('previous_hash')->nullable();
            $table->string('hash');
        });

        Schema::create('alpha_consents', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('anonymous_id')->nullable();
            $table->string('purpose');
            $table->string('version')->nullable();
            $table->string('action');
            $table->string('source')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}
