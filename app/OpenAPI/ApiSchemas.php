<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="integer", example=7),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified", type="boolean", example=true),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="IncidentRelationOption",
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="name", type="string", example="Vehicle collision"),
 *     @OA\Property(property="code", type="string", nullable=true, example="VEH_COLLISION")
 * )
 *
 * @OA\Schema(
 *     schema="IncidentLocationDetail",
 *     @OA\Property(property="id", type="integer", example=11),
 *     @OA\Property(property="name", type="string", example="Loading Dock A"),
 *     @OA\Property(property="code", type="string", nullable=true, example="LD-A"),
 *     @OA\Property(property="type", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Warehouse")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="IncidentAttachment",
 *     @OA\Property(property="id", type="integer", example=9001),
 *     @OA\Property(property="url", type="string", nullable=true, example="http://localhost/storage/incidents/photo.jpg"),
 *     @OA\Property(property="attachment_type_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="attachment_type", type="string", nullable=true, example="Photo"),
 *     @OA\Property(property="attachment_category_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="attachment_category", type="string", nullable=true, example="Evidence"),
 *     @OA\Property(property="filename", type="string", nullable=true, example="photo_1.jpg"),
 *     @OA\Property(property="original_name", type="string", nullable=true, example="forklift-damage.jpg"),
 *     @OA\Property(property="path", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="mime_type", type="string", nullable=true, example="image/jpeg"),
 *     @OA\Property(property="size", type="integer", nullable=true, example=240512),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentCommentReply",
 *     @OA\Property(property="id", type="integer", example=501),
 *     @OA\Property(property="reply", type="string", example="Witness statement attached."),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=7),
 *         @OA\Property(property="name", type="string", example="John Doe")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentComment",
 *     @OA\Property(property="id", type="integer", example=301),
 *     @OA\Property(property="comment_type", type="string", nullable=true, example="clarification"),
 *     @OA\Property(property="comment", type="string", example="Please clarify the sequence of events."),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=7),
 *         @OA\Property(property="name", type="string", example="John Doe")
 *     ),
 *     @OA\Property(property="replies", type="array", @OA\Items(ref="#/components/schemas/IncidentCommentReply")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Incident",
 *     required={"id", "title", "status", "reported_by"},
 *     @OA\Property(property="id", type="integer", example=128),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="incident_reference_number", type="string", nullable=true, example="INC-2026-00128"),
 *     @OA\Property(property="title", type="string", example="Forklift collision near loading dock"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="incident_description", type="string", nullable=true),
 *     @OA\Property(property="incident_type_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="incident_type", ref="#/components/schemas/IncidentRelationOption"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="location_id", type="integer", nullable=true, example=11),
 *     @OA\Property(property="location_detail", ref="#/components/schemas/IncidentLocationDetail"),
 *     @OA\Property(property="other_location", type="string", nullable=true),
 *     @OA\Property(property="datetime", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="incident_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="incident_time", type="string", example="13:45:00", nullable=true),
 *     @OA\Property(property="classification", type="string", nullable=true, example="Major"),
 *     @OA\Property(property="classification_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="classification_detail", ref="#/components/schemas/IncidentRelationOption"),
 *     @OA\Property(property="reclassification_id", type="integer", nullable=true, example=3),
 *     @OA\Property(property="reclassification_detail", ref="#/components/schemas/IncidentRelationOption"),
 *     @OA\Property(property="status", type="string", example="draft_submitted"),
 *     @OA\Property(property="status_id", type="integer", nullable=true, example=4),
 *     @OA\Property(property="status_detail", ref="#/components/schemas/IncidentRelationOption"),
 *     @OA\Property(property="work_package_id", type="integer", nullable=true, example=3),
 *     @OA\Property(property="work_package", ref="#/components/schemas/IncidentRelationOption"),
 *     @OA\Property(property="immediate_response", type="string", nullable=true),
 *     @OA\Property(property="subcontractor_id", type="integer", nullable=true, example=9),
 *     @OA\Property(property="subcontractor", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=9),
 *         @OA\Property(property="name", type="string", example="ABC Contractor"),
 *         @OA\Property(property="contact_person", type="string", nullable=true),
 *         @OA\Property(property="contact_number", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="person_in_charge", type="string", nullable=true),
 *     @OA\Property(property="subcontractor_contact_number", type="string", nullable=true),
 *     @OA\Property(property="gps_location", type="string", nullable=true),
 *     @OA\Property(property="activity_during_incident", type="string", nullable=true),
 *     @OA\Property(property="type_of_accident", type="string", nullable=true),
 *     @OA\Property(property="basic_effect", type="string", nullable=true),
 *     @OA\Property(property="conclusion", type="string", nullable=true),
 *     @OA\Property(property="close_remark", type="string", nullable=true),
 *     @OA\Property(property="rootcause_id", type="integer", nullable=true, example=6),
 *     @OA\Property(property="root_cause", ref="#/components/schemas/IncidentRelationOption"),
 *     @OA\Property(property="other_rootcause", type="string", nullable=true),
 *     @OA\Property(property="reported_by", type="integer", example=7),
 *     @OA\Property(property="reporter", ref="#/components/schemas/User"),
 *     @OA\Property(property="attachments", type="array", @OA\Items(ref="#/components/schemas/IncidentAttachment")),
 *     @OA\Property(property="chronologies", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="victims", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="witnesses", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="investigation_team_members", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="damages", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="immediate_actions", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="planned_actions", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="immediate_causes", type="array", @OA\Items(ref="#/components/schemas/IncidentRelationOption")),
 *     @OA\Property(property="contributing_factors", type="array", @OA\Items(ref="#/components/schemas/IncidentRelationOption")),
 *     @OA\Property(property="work_activities", type="array", @OA\Items(ref="#/components/schemas/IncidentRelationOption")),
 *     @OA\Property(property="external_parties", type="array", @OA\Items(ref="#/components/schemas/IncidentRelationOption")),
 *     @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/IncidentComment")),
 *     @OA\Property(property="comments_count", type="integer", nullable=true, example=4),
 *     @OA\Property(property="rejection_reason", type="string", nullable=true),
 *     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="approved_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="rejected_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="IncidentAttachmentInput",
 *     example={"attachment_type_id": 1, "attachment_category_id": 2, "filename": "forklift-damage.jpg", "path": "incidents/tmp/forklift-damage.jpg", "description": "Forklift front guard damage", "temporary_id": "4a31dc75-8798-4d1f-9d74-44bb2f6f0d8f", "local_created_at": "2026-03-31T13:50:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="attachment_type_id", type="integer", nullable=true),
 *     @OA\Property(property="attachment_category_id", type="integer", nullable=true),
 *     @OA\Property(property="filename", type="string", nullable=true),
 *     @OA\Property(property="path", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentChronologyInput",
 *     example={"event_date": "2026-03-31", "event_time": "13:35", "events": "Forklift reversed into stacked materials while repositioning a pallet.", "sort_order": 1, "temporary_id": "f44f9d76-b94c-4fd6-8e35-df66d842635d", "local_created_at": "2026-03-31T13:36:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="event_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="event_time", type="string", example="13:45", nullable=true),
 *     @OA\Property(property="events", type="string", nullable=true),
 *     @OA\Property(property="sort_order", type="integer", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentVictimInput",
 *     example={"victim_type_id": 1, "name": "Rahman Bin Ali", "identification": "EMP-2291", "occupation": "Forklift Operator", "age": 34, "nationality": "Malaysian", "working_experience": "6 years", "nature_of_injury": "Bruised shoulder", "body_injured": "Left shoulder", "treatment": "First aid on site", "temporary_id": "5abf8fb7-6560-4eec-a944-8b59f91e8040", "local_created_at": "2026-03-31T13:52:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="victim_type_id", type="integer", nullable=true),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="identification", type="string", nullable=true),
 *     @OA\Property(property="occupation", type="string", nullable=true),
 *     @OA\Property(property="age", type="integer", nullable=true),
 *     @OA\Property(property="nationality", type="string", nullable=true),
 *     @OA\Property(property="working_experience", type="string", nullable=true),
 *     @OA\Property(property="nature_of_injury", type="string", nullable=true),
 *     @OA\Property(property="body_injured", type="string", nullable=true),
 *     @OA\Property(property="treatment", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentWitnessInput",
 *     example={"name": "Nur Aisyah", "designation": "Storekeeper", "identification": "EMP-1032", "temporary_id": "b6fdbe64-a85d-4a45-a9b6-e4f43334178c", "local_created_at": "2026-03-31T13:53:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="designation", type="string", nullable=true),
 *     @OA\Property(property="identification", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentInvestigationTeamMemberInput",
 *     example={"name": "Daniel Wong", "designation": "Safety Officer", "contact_number": "+60123456789", "company": "Main Contractor", "temporary_id": "5d5dc8db-fbf3-4ee8-b910-4603119f8c62", "local_created_at": "2026-03-31T15:00:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="designation", type="string", nullable=true),
 *     @OA\Property(property="contact_number", type="string", nullable=true),
 *     @OA\Property(property="company", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentDamageInput",
 *     example={"damage_type_id": 3, "estimate_cost": 4200.50, "temporary_id": "f3ce399d-e78b-4535-8317-c7ff658965ca", "local_created_at": "2026-03-31T14:05:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="damage_type_id", type="integer", nullable=true),
 *     @OA\Property(property="estimate_cost", type="number", format="float", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentImmediateActionInput",
 *     example={"action_taken": "Isolated the area and stopped forklift operation.", "company": "Main Contractor", "temporary_id": "af7309f3-4af8-40bb-b67a-d70c2f67fe96", "local_created_at": "2026-03-31T13:55:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="action_taken", type="string", nullable=true),
 *     @OA\Property(property="company", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentPlannedActionInput",
 *     example={"action_taken": "Replace damaged guard and refresh reversing-zone controls.", "expected_date": "2026-04-03", "actual_date": null, "temporary_id": "48771fd2-c236-4a1b-bbf8-9a7d8a5f4f7d", "local_created_at": "2026-03-31T16:10:00Z"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="action_taken", type="string", nullable=true),
 *     @OA\Property(property="expected_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="actual_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentStoreRequest",
 *     required={"title", "incident_type_id", "incident_date", "incident_time", "work_package_id", "location_type_id", "classification_id", "incident_description", "immediate_response", "work_activity_id", "attachments"},
 *     @OA\Property(property="title", type="string", example="Forklift collision near loading dock"),
 *     @OA\Property(property="incident_type_id", type="integer", example=2),
 *     @OA\Property(property="incident_date", type="string", format="date", example="2026-03-31"),
 *     @OA\Property(property="incident_time", type="string", example="13:45"),
 *     @OA\Property(property="status_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="work_package_id", type="integer", example=3),
 *     @OA\Property(property="location_type_id", type="integer", example=1),
 *     @OA\Property(property="location_id", type="integer", nullable=true, example=11),
 *     @OA\Property(property="other_location", type="string", nullable=true),
 *     @OA\Property(property="classification_id", type="integer", example=2),
 *     @OA\Property(property="reclassification_id", type="integer", nullable=true),
 *     @OA\Property(property="incident_description", type="string"),
 *     @OA\Property(property="immediate_response", type="string"),
 *     @OA\Property(property="subcontractor_id", type="integer", nullable=true),
 *     @OA\Property(property="person_in_charge", type="string", nullable=true),
 *     @OA\Property(property="subcontractor_contact_number", type="string", nullable=true),
 *     @OA\Property(property="gps_location", type="string", nullable=true),
 *     @OA\Property(property="activity_during_incident", type="string", nullable=true),
 *     @OA\Property(property="type_of_accident", type="string", nullable=true),
 *     @OA\Property(property="basic_effect", type="string", nullable=true),
 *     @OA\Property(property="conclusion", type="string", nullable=true),
 *     @OA\Property(property="close_remark", type="string", nullable=true),
 *     @OA\Property(property="rootcause_id", type="integer", nullable=true),
 *     @OA\Property(property="other_rootcause", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="work_activity_id", type="integer", example=4),
 *     @OA\Property(property="work_activity_ids", type="array", example={4, 7}, @OA\Items(type="integer")),
 *     @OA\Property(property="attachments", type="array", minItems=1, example={{"attachment_type_id": 1, "attachment_category_id": 2, "filename": "forklift-damage.jpg", "path": "incidents/tmp/forklift-damage.jpg", "description": "Forklift front guard damage", "temporary_id": "4a31dc75-8798-4d1f-9d74-44bb2f6f0d8f", "local_created_at": "2026-03-31T13:50:00Z"}}, @OA\Items(ref="#/components/schemas/IncidentAttachmentInput")),
 *     @OA\Property(property="chronologies", type="array", example={{"event_date": "2026-03-31", "event_time": "13:35", "events": "Forklift reversed into stacked materials while repositioning a pallet.", "sort_order": 1, "temporary_id": "f44f9d76-b94c-4fd6-8e35-df66d842635d", "local_created_at": "2026-03-31T13:36:00Z"}}, @OA\Items(ref="#/components/schemas/IncidentChronologyInput")),
 *     @OA\Property(property="victims", type="array", example={{"victim_type_id": 1, "name": "Rahman Bin Ali", "identification": "EMP-2291", "occupation": "Forklift Operator", "age": 34, "nationality": "Malaysian", "working_experience": "6 years", "nature_of_injury": "Bruised shoulder", "body_injured": "Left shoulder", "treatment": "First aid on site", "temporary_id": "5abf8fb7-6560-4eec-a944-8b59f91e8040", "local_created_at": "2026-03-31T13:52:00Z"}}, @OA\Items(ref="#/components/schemas/IncidentVictimInput")),
 *     @OA\Property(property="witnesses", type="array", @OA\Items(ref="#/components/schemas/IncidentWitnessInput")),
 *     @OA\Property(property="investigation_team_members", type="array", @OA\Items(ref="#/components/schemas/IncidentInvestigationTeamMemberInput")),
 *     @OA\Property(property="damages", type="array", @OA\Items(ref="#/components/schemas/IncidentDamageInput")),
 *     @OA\Property(property="immediate_actions", type="array", example={{"action_taken": "Isolated the area and stopped forklift operation.", "company": "Main Contractor", "temporary_id": "af7309f3-4af8-40bb-b67a-d70c2f67fe96", "local_created_at": "2026-03-31T13:55:00Z"}}, @OA\Items(ref="#/components/schemas/IncidentImmediateActionInput")),
 *     @OA\Property(property="planned_actions", type="array", example={{"action_taken": "Replace damaged guard and refresh reversing-zone controls.", "expected_date": "2026-04-03", "actual_date": null, "temporary_id": "48771fd2-c236-4a1b-bbf8-9a7d8a5f4f7d", "local_created_at": "2026-03-31T16:10:00Z"}}, @OA\Items(ref="#/components/schemas/IncidentPlannedActionInput")),
 *     @OA\Property(property="remove_attachment_ids", type="array", example={9001}, @OA\Items(type="integer")),
 *     @OA\Property(property="immediate_cause_ids", type="array", example={2, 5}, @OA\Items(type="integer")),
 *     @OA\Property(property="contributing_factor_ids", type="array", example={3, 8}, @OA\Items(type="integer")),
 *     @OA\Property(property="external_party_ids", type="array", example={1}, @OA\Items(type="integer"))
 * )
 *
 * @OA\Schema(
 *     schema="IncidentUpdateRequest",
 *     @OA\Property(property="incident_reference_number", type="string", nullable=true),
 *     @OA\Property(property="title", type="string", nullable=true),
 *     @OA\Property(property="incident_type_id", type="integer", nullable=true),
 *     @OA\Property(property="incident_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="incident_time", type="string", nullable=true, example="13:45"),
 *     @OA\Property(property="status_id", type="integer", nullable=true),
 *     @OA\Property(property="work_package_id", type="integer", nullable=true),
 *     @OA\Property(property="location_type_id", type="integer", nullable=true),
 *     @OA\Property(property="location_id", type="integer", nullable=true),
 *     @OA\Property(property="other_location", type="string", nullable=true),
 *     @OA\Property(property="classification_id", type="integer", nullable=true),
 *     @OA\Property(property="reclassification_id", type="integer", nullable=true),
 *     @OA\Property(property="work_activity_id", type="integer", nullable=true),
 *     @OA\Property(property="incident_description", type="string", nullable=true),
 *     @OA\Property(property="immediate_response", type="string", nullable=true),
 *     @OA\Property(property="subcontractor_id", type="integer", nullable=true),
 *     @OA\Property(property="person_in_charge", type="string", nullable=true),
 *     @OA\Property(property="subcontractor_contact_number", type="string", nullable=true),
 *     @OA\Property(property="gps_location", type="string", nullable=true),
 *     @OA\Property(property="activity_during_incident", type="string", nullable=true),
 *     @OA\Property(property="type_of_accident", type="string", nullable=true),
 *     @OA\Property(property="basic_effect", type="string", nullable=true),
 *     @OA\Property(property="conclusion", type="string", nullable=true),
 *     @OA\Property(property="close_remark", type="string", nullable=true),
 *     @OA\Property(property="rootcause_id", type="integer", nullable=true),
 *     @OA\Property(property="other_rootcause", type="string", nullable=true),
 *     @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="chronologies", type="array", example={{"id": 12, "event_date": "2026-03-31", "event_time": "13:35", "events": "Forklift reversed into stacked materials while repositioning a pallet.", "sort_order": 1}}, @OA\Items(ref="#/components/schemas/IncidentChronologyInput")),
 *     @OA\Property(property="victims", type="array", example={{"id": 21, "victim_type_id": 1, "name": "Rahman Bin Ali", "nature_of_injury": "Bruised shoulder", "treatment": "First aid on site"}}, @OA\Items(ref="#/components/schemas/IncidentVictimInput")),
 *     @OA\Property(property="witnesses", type="array", @OA\Items(ref="#/components/schemas/IncidentWitnessInput")),
 *     @OA\Property(property="investigation_team_members", type="array", @OA\Items(ref="#/components/schemas/IncidentInvestigationTeamMemberInput")),
 *     @OA\Property(property="damages", type="array", @OA\Items(ref="#/components/schemas/IncidentDamageInput")),
 *     @OA\Property(property="immediate_actions", type="array", example={{"id": 31, "action_taken": "Isolated the area and stopped forklift operation.", "company": "Main Contractor"}}, @OA\Items(ref="#/components/schemas/IncidentImmediateActionInput")),
 *     @OA\Property(property="planned_actions", type="array", example={{"id": 41, "action_taken": "Replace damaged guard and refresh reversing-zone controls.", "expected_date": "2026-04-03"}}, @OA\Items(ref="#/components/schemas/IncidentPlannedActionInput")),
 *     @OA\Property(property="attachments", type="array", example={{"id": 9001, "attachment_type_id": 1, "attachment_category_id": 2, "filename": "forklift-damage.jpg", "description": "Replacement evidence photo"}}, @OA\Items(ref="#/components/schemas/IncidentAttachmentInput")),
 *     @OA\Property(property="remove_attachment_ids", type="array", example={9001}, @OA\Items(type="integer")),
 *     @OA\Property(property="immediate_cause_ids", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="contributing_factor_ids", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="work_activity_ids", type="array", example={4, 7}, @OA\Items(type="integer")),
 *     @OA\Property(property="external_party_ids", type="array", example={1}, @OA\Items(type="integer"))
 * )
 *
 * @OA\Schema(
 *     schema="IncidentTransitionResponse",
 *     @OA\Property(property="message", type="string", example="Incident transitioned successfully."),
 *     @OA\Property(property="incident_id", type="integer", example=128),
 *     @OA\Property(property="status", type="string", example="draft_submitted"),
 *     @OA\Property(property="status_label", type="string", example="Draft Submitted")
 * )
 *
 * @OA\Schema(
 *     schema="IncidentCommentCreateResponse",
 *     @OA\Property(property="message", type="string", example="Comment added."),
 *     @OA\Property(property="comment_id", type="integer", example=301)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentReplyCreateResponse",
 *     @OA\Property(property="message", type="string", example="Reply added."),
 *     @OA\Property(property="reply_id", type="integer", example=501)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentCommentResolutionResponse",
 *     @OA\Property(property="message", type="string", example="Comment resolved."),
 *     @OA\Property(property="comment_id", type="integer", example=301),
 *     @OA\Property(property="is_resolved", type="boolean", example=true),
 *     @OA\Property(property="resolved_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="resolved_by", type="integer", nullable=true, example=7)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentAllowedTransition",
 *     @OA\Property(property="status", type="string", example="draft_reviewed"),
 *     @OA\Property(property="label", type="string", example="Draft Reviewed"),
 *     @OA\Property(property="blocked_by_unresolved_critical_comments", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="IncidentAllowedTransitionsResponse",
 *     @OA\Property(property="transitions", type="array", @OA\Items(ref="#/components/schemas/IncidentAllowedTransition"))
 * )
 *
 * @OA\Schema(
 *     schema="Training",
 *     required={"id", "title"},
 *     @OA\Property(property="id", type="integer", example=11),
 *     @OA\Property(property="title", type="string", example="Fire Safety 101"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="starts_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="ends_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Inspection",
 *     required={"id", "inspection_checklist_id", "status"},
 *     @OA\Property(property="id", type="integer", example=52),
 *     @OA\Property(property="inspection_checklist_id", type="integer", example=4),
 *     @OA\Property(property="status", type="string", example="in_progress"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="performed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Audit",
 *     required={"id", "site_name", "status"},
 *     @OA\Property(property="id", type="integer", example=21),
 *     @OA\Property(property="reference_no", type="string", nullable=true, example="AUD-2026-0021"),
 *     @OA\Property(property="site_name", type="string", example="Main Construction Yard"),
 *     @OA\Property(property="area", type="string", nullable=true),
 *     @OA\Property(property="audit_type", type="string", nullable=true, example="internal"),
 *     @OA\Property(property="status", type="string", example="scheduled"),
 *     @OA\Property(property="scheduled_for", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="conducted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Worker",
 *     required={"id", "employee_code", "full_name"},
 *     @OA\Property(property="id", type="integer", example=90),
 *     @OA\Property(property="employee_code", type="string", example="WRK-0090"),
 *     @OA\Property(property="full_name", type="string", example="Jane Smith"),
 *     @OA\Property(property="department", type="string", nullable=true),
 *     @OA\Property(property="position", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", nullable=true, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ApiSchemas
{
    // Shared schema carrier.
}
