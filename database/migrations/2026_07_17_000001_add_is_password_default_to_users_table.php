<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_password_default')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_password_default')->default(true)->after('password');
            });
        }

        set_time_limit(0);

        DB::table('users')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'is_password_default' => Hash::check(User::DEFAULT_PASSWORD, $user->password),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_password_default')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_password_default');
            });
        }
    }
};
