<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        DB::table('roles')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $role) {
                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['slug' => Str::slug($role->name)]);
            });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
