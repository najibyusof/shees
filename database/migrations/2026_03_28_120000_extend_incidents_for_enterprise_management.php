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
        $this->createReferenceTables();
        $this->extendIncidentTables();
        $this->createIncidentChildTables();
        $this->seedReferenceData();
        $this->backfillExistingIncidents();
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_external_party');
        Schema::dropIfExists('incident_work_activity');
        Schema::dropIfExists('incident_contributing_factor');
        Schema::dropIfExists('incident_immediate_cause');

        Schema::dropIfExists('incident_planned_actions');
        Schema::dropIfExists('incident_immediate_actions');
        Schema::dropIfExists('incident_damages');
        Schema::dropIfExists('incident_investigation_team_members');
        Schema::dropIfExists('incident_witnesses');
        Schema::dropIfExists('incident_victims');
        Schema::dropIfExists('incident_chronologies');
        Schema::dropIfExists('incident_comment_replies');

        Schema::table('incident_comments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique(['temporary_id']);
            $table->dropColumn(['comment_type', 'temporary_id', 'local_created_at']);
        });

        Schema::table('incident_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attachment_type_id');
            $table->dropConstrainedForeignId('attachment_category_id');
            $table->dropSoftDeletes();
            $table->dropUnique(['temporary_id']);
            $table->dropColumn(['filename', 'description', 'temporary_id', 'local_created_at']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('incident_type_id');
            $table->dropConstrainedForeignId('status_id');
            $table->dropConstrainedForeignId('work_package_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('classification_id');
            $table->dropConstrainedForeignId('reclassification_id');
            $table->dropConstrainedForeignId('subcontractor_id');
            $table->dropConstrainedForeignId('rootcause_id');
            $table->dropUnique(['incident_reference_number']);
            $table->dropColumn([
                'incident_reference_number',
                'incident_date',
                'incident_time',
                'other_location',
                'incident_description',
                'immediate_response',
                'person_in_charge',
                'subcontractor_contact_number',
                'gps_location',
                'activity_during_incident',
                'type_of_accident',
                'basic_effect',
                'conclusion',
                'close_remark',
                'other_rootcause',
            ]);
        });

        Schema::dropIfExists('external_parties');
        Schema::dropIfExists('subcontractors');
        Schema::dropIfExists('work_packages');
        Schema::dropIfExists('attachment_categories');
        Schema::dropIfExists('attachment_types');
        Schema::dropIfExists('work_activities');
        Schema::dropIfExists('factor_types');
        Schema::dropIfExists('cause_types');
        Schema::dropIfExists('damage_types');
        Schema::dropIfExists('victim_types');
        Schema::dropIfExists('incident_locations');
        Schema::dropIfExists('location_types');
        Schema::dropIfExists('incident_statuses');
        Schema::dropIfExists('incident_classifications');
        Schema::dropIfExists('incident_types');
    }

    private function createReferenceTables(): void
    {
        Schema::create('incident_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('incident_classifications', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('incident_statuses', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('location_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('incident_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_type_id')->nullable()->constrained('location_types')->nullOnDelete();
            $this->addLookupColumns($table);
        });

        Schema::create('victim_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('damage_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('cause_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('factor_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('work_activities', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('attachment_types', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('attachment_categories', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('work_packages', function (Blueprint $table) {
            $table->id();
            $this->addLookupColumns($table);
        });

        Schema::create('subcontractors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_parties', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function extendIncidentTables(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('incident_reference_number')->nullable()->unique()->after('temporary_id');
            $table->foreignId('incident_type_id')->nullable()->after('title')->constrained('incident_types')->nullOnDelete();
            $table->date('incident_date')->nullable()->after('datetime');
            $table->time('incident_time')->nullable()->after('incident_date');
            $table->foreignId('status_id')->nullable()->after('status')->constrained('incident_statuses')->nullOnDelete();
            $table->foreignId('work_package_id')->nullable()->after('status_id')->constrained('work_packages')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('location')->constrained('incident_locations')->nullOnDelete();
            $table->string('other_location')->nullable()->after('location_id');
            $table->foreignId('classification_id')->nullable()->after('classification')->constrained('incident_classifications')->nullOnDelete();
            $table->foreignId('reclassification_id')->nullable()->after('classification_id')->constrained('incident_classifications')->nullOnDelete();
            $table->longText('incident_description')->nullable()->after('description');
            $table->text('immediate_response')->nullable()->after('incident_description');
            $table->foreignId('subcontractor_id')->nullable()->after('immediate_response')->constrained('subcontractors')->nullOnDelete();
            $table->string('person_in_charge')->nullable()->after('subcontractor_id');
            $table->string('subcontractor_contact_number', 50)->nullable()->after('person_in_charge');
            $table->string('gps_location', 120)->nullable()->after('subcontractor_contact_number');
            $table->text('activity_during_incident')->nullable()->after('gps_location');
            $table->string('type_of_accident')->nullable()->after('activity_during_incident');
            $table->text('basic_effect')->nullable()->after('type_of_accident');
            $table->text('conclusion')->nullable()->after('basic_effect');
            $table->text('close_remark')->nullable()->after('conclusion');
            $table->foreignId('rootcause_id')->nullable()->after('close_remark')->constrained('cause_types')->nullOnDelete();
            $table->string('other_rootcause')->nullable()->after('rootcause_id');
        });

        Schema::table('incident_attachments', function (Blueprint $table) {
            $table->foreignId('attachment_type_id')->nullable()->after('incident_id')->constrained('attachment_types')->nullOnDelete();
            $table->foreignId('attachment_category_id')->nullable()->after('attachment_type_id')->constrained('attachment_categories')->nullOnDelete();
            $table->string('filename')->nullable()->after('original_name');
            $table->text('description')->nullable()->after('path');
            $this->addOfflineSyncColumns($table);
            $table->softDeletes();
        });

        Schema::table('incident_comments', function (Blueprint $table) {
            $table->string('comment_type', 50)->default('general')->after('user_id');
            $this->addOfflineSyncColumns($table);
            $table->softDeletes();
        });
    }

    private function createIncidentChildTables(): void
    {
        Schema::create('incident_comment_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_comment_id')->constrained('incident_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reply');
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_chronologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->text('events');
            $table->unsignedInteger('sort_order')->default(0);
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_victims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('victim_type_id')->nullable()->constrained('victim_types')->nullOnDelete();
            $table->string('name');
            $table->string('identification')->nullable();
            $table->string('occupation')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('nationality')->nullable();
            $table->string('working_experience')->nullable();
            $table->string('nature_of_injury')->nullable();
            $table->string('body_injured')->nullable();
            $table->text('treatment')->nullable();
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_witnesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('identification')->nullable();
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_investigation_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('company')->nullable();
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('damage_type_id')->nullable()->constrained('damage_types')->nullOnDelete();
            $table->decimal('estimate_cost', 15, 2)->default(0);
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_immediate_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->text('action_taken');
            $table->string('company')->nullable();
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_planned_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->text('action_taken');
            $table->date('expected_date')->nullable();
            $table->date('actual_date')->nullable();
            $this->addOfflineSyncColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_immediate_cause', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cause_type_id')->constrained('cause_types')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['incident_id', 'cause_type_id']);
        });

        Schema::create('incident_contributing_factor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('factor_type_id')->constrained('factor_types')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['incident_id', 'factor_type_id']);
        });

        Schema::create('incident_work_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_activity_id')->constrained('work_activities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['incident_id', 'work_activity_id']);
        });

        Schema::create('incident_external_party', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_party_id')->constrained('external_parties')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['incident_id', 'external_party_id']);
        });
    }

    private function seedReferenceData(): void
    {
        $this->seedLookupTable('incident_types', [
            ['code' => 'injury', 'name' => 'Injury'],
            ['code' => 'near_miss', 'name' => 'Near Miss'],
            ['code' => 'property_damage', 'name' => 'Property Damage'],
            ['code' => 'environmental', 'name' => 'Environmental'],
            ['code' => 'fire', 'name' => 'Fire'],
            ['code' => 'security', 'name' => 'Security'],
        ]);

        $this->seedLookupTable('incident_classifications', [
            ['code' => 'minor', 'name' => 'Minor'],
            ['code' => 'moderate', 'name' => 'Moderate'],
            ['code' => 'major', 'name' => 'Major'],
            ['code' => 'critical', 'name' => 'Critical'],
        ]);

        $this->seedLookupTable('incident_statuses', [
            ['code' => 'draft', 'name' => 'Draft'],
            ['code' => 'submitted', 'name' => 'Submitted'],
            ['code' => 'under_review', 'name' => 'Under Review'],
            ['code' => 'approved', 'name' => 'Approved'],
            ['code' => 'rejected', 'name' => 'Rejected'],
            ['code' => 'closed', 'name' => 'Closed'],
        ]);

        $this->seedLookupTable('location_types', [
            ['code' => 'site', 'name' => 'Site'],
            ['code' => 'office', 'name' => 'Office'],
            ['code' => 'plant', 'name' => 'Plant'],
            ['code' => 'warehouse', 'name' => 'Warehouse'],
            ['code' => 'transit', 'name' => 'Transit'],
        ]);

        $this->seedLookupTable('victim_types', [
            ['code' => 'employee', 'name' => 'Employee'],
            ['code' => 'subcontractor', 'name' => 'Subcontractor'],
            ['code' => 'visitor', 'name' => 'Visitor'],
            ['code' => 'public', 'name' => 'Public'],
        ]);

        $this->seedLookupTable('damage_types', [
            ['code' => 'property', 'name' => 'Property Damage'],
            ['code' => 'equipment', 'name' => 'Equipment Damage'],
            ['code' => 'vehicle', 'name' => 'Vehicle Damage'],
            ['code' => 'environment', 'name' => 'Environmental Impact'],
            ['code' => 'document', 'name' => 'Document Loss'],
        ]);

        $this->seedLookupTable('cause_types', [
            ['code' => 'unsafe_act', 'name' => 'Unsafe Act'],
            ['code' => 'unsafe_condition', 'name' => 'Unsafe Condition'],
            ['code' => 'equipment_failure', 'name' => 'Equipment Failure'],
            ['code' => 'procedural_gap', 'name' => 'Procedural Gap'],
            ['code' => 'supervision_gap', 'name' => 'Supervision Gap'],
        ]);

        $this->seedLookupTable('factor_types', [
            ['code' => 'fatigue', 'name' => 'Fatigue'],
            ['code' => 'weather', 'name' => 'Weather'],
            ['code' => 'training_gap', 'name' => 'Training Gap'],
            ['code' => 'communication', 'name' => 'Communication Breakdown'],
            ['code' => 'housekeeping', 'name' => 'Housekeeping'],
        ]);

        $this->seedLookupTable('work_activities', [
            ['code' => 'lifting', 'name' => 'Lifting Operation'],
            ['code' => 'excavation', 'name' => 'Excavation'],
            ['code' => 'hot_work', 'name' => 'Hot Work'],
            ['code' => 'electrical', 'name' => 'Electrical Work'],
            ['code' => 'confined_space', 'name' => 'Confined Space'],
        ]);

        $this->seedLookupTable('attachment_types', [
            ['code' => 'photo', 'name' => 'Photo'],
            ['code' => 'video', 'name' => 'Video'],
            ['code' => 'document', 'name' => 'Document'],
            ['code' => 'sketch', 'name' => 'Sketch'],
        ]);

        $this->seedLookupTable('attachment_categories', [
            ['code' => 'evidence', 'name' => 'Evidence'],
            ['code' => 'report', 'name' => 'Report'],
            ['code' => 'permit', 'name' => 'Permit'],
            ['code' => 'medical', 'name' => 'Medical'],
        ]);
    }

    private function backfillExistingIncidents(): void
    {
        $siteLocationTypeId = DB::table('location_types')->where('code', 'site')->value('id');

        DB::table('incidents')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->select('location')
            ->distinct()
            ->orderBy('location')
            ->get()
            ->each(function (object $row, int $index) use ($siteLocationTypeId) {
                DB::table('incident_locations')->updateOrInsert(
                    ['name' => $row->location],
                    [
                        'location_type_id' => $siteLocationTypeId,
                        'code' => 'location_'.($index + 1),
                        'description' => 'Migrated from legacy incident locations',
                        'sort_order' => $index,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });

        $classificationIds = DB::table('incident_classifications')->pluck('id', 'name');
        $statusIds = DB::table('incident_statuses')->pluck('id', 'code');
        $locationIds = DB::table('incident_locations')->pluck('id', 'name');

        DB::table('incidents')->orderBy('id')->get()->each(function (object $incident) use ($classificationIds, $statusIds, $locationIds) {
            $dateTime = $incident->datetime ? \Illuminate\Support\Carbon::parse($incident->datetime) : now();
            $statusCode = Str::of((string) ($incident->status ?? 'draft'))->trim()->lower()->replace(' ', '_')->value();
            $referenceNumber = 'INC-'.str_pad((string) $incident->id, 6, '0', STR_PAD_LEFT);

            DB::table('incidents')
                ->where('id', $incident->id)
                ->update([
                    'incident_reference_number' => $incident->incident_reference_number ?: $referenceNumber,
                    'incident_date' => $incident->incident_date ?: $dateTime->toDateString(),
                    'incident_time' => $incident->incident_time ?: $dateTime->format('H:i:s'),
                    'status_id' => $incident->status_id ?: ($statusIds[$statusCode] ?? $statusIds['draft'] ?? null),
                    'location_id' => $incident->location_id ?: ($locationIds[$incident->location] ?? null),
                    'other_location' => $incident->other_location ?: $incident->location,
                    'classification_id' => $incident->classification_id ?: ($classificationIds[$incident->classification] ?? null),
                    'incident_description' => $incident->incident_description ?: $incident->description,
                    'updated_at' => now(),
                ]);
        });

        DB::table('incident_attachments')
            ->whereNull('filename')
            ->update(['filename' => DB::raw('original_name')]);
    }

    private function addLookupColumns(Blueprint $table): void
    {
        $table->string('code')->unique();
        $table->string('name');
        $table->text('description')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $this->addOfflineSyncColumns($table);
        $table->timestamps();
        $table->softDeletes();
    }

    private function addOfflineSyncColumns(Blueprint $table): void
    {
        $table->string('temporary_id', 36)->nullable()->unique();
        $table->timestamp('local_created_at')->nullable();
    }

    private function seedLookupTable(string $table, array $rows): void
    {
        $now = now();

        DB::table($table)->upsert(
            array_map(function (array $row, int $index) use ($now) {
                return [
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $rows, array_keys($rows)),
            ['code'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at']
        );
    }
};
