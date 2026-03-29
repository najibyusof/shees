<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'location_type_id')) {
                $table->foreignId('location_type_id')->nullable()->after('location_id')->constrained('location_types')->nullOnDelete();
            }

            if (! Schema::hasColumn('incidents', 'work_activity_id')) {
                $table->foreignId('work_activity_id')->nullable()->after('work_package_id')->constrained('work_activities')->nullOnDelete();
            }
        });

        DB::table('incidents')
            ->whereNull('location_type_id')
            ->whereNotNull('location_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $locationTypeId = DB::table('incident_locations')
                        ->where('id', $row->location_id)
                        ->value('location_type_id');

                    if ($locationTypeId !== null) {
                        DB::table('incidents')->where('id', $row->id)->update([
                            'location_type_id' => $locationTypeId,
                        ]);
                    }
                }
            });

        DB::table('incidents')
            ->whereNull('work_activity_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $workActivityId = DB::table('incident_work_activity')
                        ->where('incident_id', $row->id)
                        ->orderBy('id')
                        ->value('work_activity_id');

                    if ($workActivityId !== null) {
                        DB::table('incidents')->where('id', $row->id)->update([
                            'work_activity_id' => $workActivityId,
                        ]);
                    }
                }
            });

        $invalidIncidents = DB::table('incidents')
            ->whereNull('incident_reference_number')
            ->orWhere('incident_reference_number', '')
            ->orWhereNull('incident_date')
            ->orWhereNull('incident_time')
            ->orWhereNull('incident_type_id')
            ->orWhereNull('work_package_id')
            ->orWhereNull('classification_id')
            ->orWhereNull('location_type_id')
            ->orWhere(function ($query): void {
                $query->whereNull('location_id')
                    ->where(function ($nested): void {
                        $nested->whereNull('other_location')
                            ->orWhere('other_location', '');
                    });
            })
            ->orWhereNull('incident_description')
            ->orWhere('incident_description', '')
            ->orWhereNull('immediate_response')
            ->orWhere('immediate_response', '')
            ->orWhereNull('work_activity_id')
            ->orWhere(function ($query): void {
                $query->whereNull('subcontractor_id')
                    ->where(function ($nested): void {
                        $nested->whereNull('person_in_charge')
                            ->orWhere('person_in_charge', '')
                            ->orWhereNull('subcontractor_contact_number')
                            ->orWhere('subcontractor_contact_number', '');
                    });
            })
            ->count();

        if ($invalidIncidents > 0) {
            throw new RuntimeException('Cannot enforce strict incident constraints: existing incidents have incomplete mandatory fields. Please backfill incident data before running this migration.');
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['incident_type_id']);
            $table->dropForeign(['work_package_id']);
            $table->dropForeign(['classification_id']);
            $table->dropForeign(['location_type_id']);
            $table->dropForeign(['work_activity_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->string('incident_reference_number')->nullable(false)->change();
            $table->foreignId('incident_type_id')->nullable(false)->change();
            $table->date('incident_date')->nullable(false)->change();
            $table->time('incident_time')->nullable(false)->change();
            $table->foreignId('work_package_id')->nullable(false)->change();
            $table->foreignId('classification_id')->nullable(false)->change();
            $table->foreignId('location_type_id')->nullable(false)->change();
            $table->longText('incident_description')->nullable(false)->change();
            $table->text('immediate_response')->nullable(false)->change();
            $table->foreignId('work_activity_id')->nullable(false)->change();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreign('incident_type_id')->references('id')->on('incident_types')->restrictOnDelete();
            $table->foreign('work_package_id')->references('id')->on('work_packages')->restrictOnDelete();
            $table->foreign('classification_id')->references('id')->on('incident_classifications')->restrictOnDelete();
            $table->foreign('location_type_id')->references('id')->on('location_types')->restrictOnDelete();
            $table->foreign('work_activity_id')->references('id')->on('work_activities')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['incident_type_id']);
            $table->dropForeign(['work_package_id']);
            $table->dropForeign(['classification_id']);
            $table->dropForeign(['location_type_id']);
            $table->dropForeign(['work_activity_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->string('incident_reference_number')->nullable()->change();
            $table->foreignId('incident_type_id')->nullable()->change();
            $table->date('incident_date')->nullable()->change();
            $table->time('incident_time')->nullable()->change();
            $table->foreignId('work_package_id')->nullable()->change();
            $table->foreignId('classification_id')->nullable()->change();
            $table->foreignId('location_type_id')->nullable()->change();
            $table->longText('incident_description')->nullable()->change();
            $table->text('immediate_response')->nullable()->change();
            $table->foreignId('work_activity_id')->nullable()->change();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreign('incident_type_id')->references('id')->on('incident_types')->nullOnDelete();
            $table->foreign('work_package_id')->references('id')->on('work_packages')->nullOnDelete();
            $table->foreign('classification_id')->references('id')->on('incident_classifications')->nullOnDelete();
            $table->foreign('location_type_id')->references('id')->on('location_types')->nullOnDelete();
            $table->foreign('work_activity_id')->references('id')->on('work_activities')->nullOnDelete();
        });
    }
};
