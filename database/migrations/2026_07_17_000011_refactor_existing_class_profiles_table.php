<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('class_profiles')) {
            return;
        }

        $legacySchema = Schema::hasColumn('class_profiles', 'user_id')
            || Schema::hasColumn('class_profiles', 'class_code')
            || Schema::hasColumn('class_profiles', 'student_number')
            || Schema::hasColumn('class_profiles', 'student_name');

        if (!Schema::hasColumn('class_profiles', 'student_id')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->string('student_id', 50)->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('class_profiles', 'class')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->string('class', 10)->nullable()->after('student_id');
            });
        }

        if (Schema::hasColumn('class_profiles', 'user_id')) {
            DB::statement("UPDATE class_profiles SET student_id = user_id WHERE student_id IS NULL OR student_id = ''");
        }
        if (Schema::hasColumn('class_profiles', 'class_code')) {
            DB::statement("UPDATE class_profiles SET class = class_code WHERE class IS NULL OR class = ''");
        }

        if ($legacySchema) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->unique('student_id', 'class_profiles_student_id_unique');
                $table->index('class', 'class_profiles_class_index');
            });
        }

        if (Schema::hasColumn('class_profiles', 'user_id')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
        if (Schema::hasColumn('class_profiles', 'class_code')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->dropColumn('class_code');
            });
        }
        if (Schema::hasColumn('class_profiles', 'student_number')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->dropColumn('student_number');
            });
        }
        if (Schema::hasColumn('class_profiles', 'student_name')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->dropColumn('student_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('class_profiles')) {
            return;
        }

        if (!Schema::hasColumn('class_profiles', 'user_id')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->string('user_id', 50)->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('class_profiles', 'class_code')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->string('class_code', 2)->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('class_profiles', 'student_number')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->unsignedSmallInteger('student_number')->nullable()->after('class_code');
            });
        }

        if (!Schema::hasColumn('class_profiles', 'student_name')) {
            Schema::table('class_profiles', function (Blueprint $table) {
                $table->string('student_name', 100)->nullable()->after('student_number');
            });
        }
    }
};
