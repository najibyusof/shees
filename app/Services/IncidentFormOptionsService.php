<?php

namespace App\Services;

use App\Models\AttachmentCategory;
use App\Models\AttachmentType;
use App\Models\CauseType;
use App\Models\DamageType;
use App\Models\ExternalParty;
use App\Models\FactorType;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Subcontractor;
use App\Models\VictimType;
use App\Models\WorkActivity;
use App\Models\WorkPackage;

class IncidentFormOptionsService
{
    public function forForm(): array
    {
        return [
            'incidentTypes' => IncidentType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'classifications' => IncidentClassification::query()->active()->ordered()->get(['id', 'name', 'code']),
            'statuses' => IncidentStatus::query()->active()->ordered()->get(['id', 'name', 'code']),
            'locationTypes' => LocationType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'locations' => IncidentLocation::query()->with('locationType:id,name')->active()->ordered()->get(['id', 'location_type_id', 'name', 'code']),
            'workPackages' => WorkPackage::query()->active()->ordered()->get(['id', 'name', 'code']),
            'subcontractors' => Subcontractor::query()->active()->ordered()->get(['id', 'name', 'code', 'contact_person', 'contact_number']),
            'victimTypes' => VictimType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'damageTypes' => DamageType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'causeTypes' => CauseType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'factorTypes' => FactorType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'workActivities' => WorkActivity::query()->active()->ordered()->get(['id', 'name', 'code']),
            'attachmentTypes' => AttachmentType::query()->active()->ordered()->get(['id', 'name', 'code']),
            'attachmentCategories' => AttachmentCategory::query()->active()->ordered()->get(['id', 'name', 'code']),
            'externalParties' => ExternalParty::query()->active()->ordered()->get(['id', 'name', 'code']),
        ];
    }
}
