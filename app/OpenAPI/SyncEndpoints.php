<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SyncRequest",
 *     title="Sync Request",
 *     description="Offline synchronization request payload",
 *     required={"device_id", "data"},
 *     @OA\Property(
 *         property="device_id",
 *         type="string",
 *         example="abc-123-def-456",
 *         description="Unique device identifier for tracking offline changes"
 *     ),
 *     @OA\Property(
 *         property="last_synced_at",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-27T08:00:00Z",
 *         description="Timestamp of the last successful sync. Server returns only changes after this time."
 *     ),
 *     @OA\Property(
 *         property="conflict_strategy",
 *         type="string",
 *         enum={"last_updated_wins", "client_wins", "server_wins"},
 *         example="last_updated_wins",
 *         description="Strategy for resolving conflicts when both client and server have modified the same record"
 *     ),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         description="Offline-modified data organized by entity type",
 *         @OA\Property(
 *             property="incidents",
 *             type="array",
 *             description="New or modified incidents",
 *             @OA\Items(
 *                 type="object",
 *                 required={"temporary_id", "title"},
 *                 @OA\Property(property="temporary_id", type="string", example="temp_uuid_123", description="Unique ID assigned client-side for unsynced records"),
 *                 @OA\Property(property="id", type="integer", example=5, description="Server ID if this is an update to existing record"),
 *                 @OA\Property(property="title", type="string", example="Safety incident in warehouse"),
 *                 @OA\Property(property="description", type="string", example="Details of the incident"),
 *                 @OA\Property(property="status", type="string", example="reported"),
 *                 @OA\Property(property="classification", type="string", example="high_risk"),
 *                 @OA\Property(property="location", type="string", example="Warehouse Building A"),
 *                 @OA\Property(property="datetime", type="string", format="date-time", example="2026-03-27T14:30:00Z"),
 *                 @OA\Property(property="local_created_at", type="string", format="date-time", example="2026-03-27T14:20:00Z", description="When record was created on client"),
 *                 @OA\Property(property="local_updated_at", type="string", format="date-time", example="2026-03-28T10:15:00Z", description="When record was last modified on client")
 *             )
 *         ),
 *         @OA\Property(
 *             property="attendance_logs",
 *             type="array",
 *             description="New or modified attendance logs",
 *             @OA\Items(type="object")
 *         ),
 *         @OA\Property(
 *             property="inspections",
 *             type="array",
 *             description="New or modified inspections",
 *             @OA\Items(type="object")
 *         ),
 *         @OA\Property(
 *             property="training",
 *             type="array",
 *             description="New or modified training records",
 *             @OA\Items(type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SyncResponse",
 *     title="Sync Response",
 *     description="Server response with synchronized data and conflicts",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Sync completed successfully."
 *     ),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         description="Synchronized data from server",
 *         @OA\Property(
 *             property="server_time",
 *             type="string",
 *             format="date-time",
 *             example="2026-03-28T10:30:00Z",
 *             description="Current server timestamp"
 *         ),
 *         @OA\Property(
 *             property="conflicts",
 *             type="array",
 *             description="List of conflicts that occurred during sync",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="entity_type", type="string", example="incidents"),
 *                 @OA\Property(property="record_id", type="integer", example=5),
 *                 @OA\Property(property="temporary_id", type="string", example="temp_uuid_123"),
 *                 @OA\Property(property="conflict_reason", type="string", example="Both client and server modified the record"),
 *                 @OA\Property(property="client_data", type="object", description="Data from client"),
 *                 @OA\Property(property="server_data", type="object", description="Current data on server"),
 *                 @OA\Property(property="resolution", type="string", enum={"client_wins", "server_wins", "merged"}, example="last_updated_wins")
 *             )
 *         ),
 *         @OA\Property(
 *             property="synced_records",
 *             type="object",
 *             description="Records synced successfully",
 *             @OA\Property(
 *                 property="incidents",
 *                 type="array",
 *                 description="Synced incidents with server IDs",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="temporary_id", type="string", example="temp_uuid_123"),
 *                     @OA\Property(property="id", type="integer", example=5, description="Assigned server ID"),
 *                     @OA\Property(property="created_at", type="string", format="date-time"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="attendance_logs",
 *                 type="array"
 *             ),
 *             @OA\Property(
 *                 property="inspections",
 *                 type="array"
 *             ),
 *             @OA\Property(
 *                 property="training",
 *                 type="array"
 *             )
 *         ),
 *         @OA\Property(
 *             property="server_updates",
 *             type="object",
 *             description="All server records changed since last sync",
 *             @OA\Property(
 *                 property="incidents",
 *                 type="array",
 *                 description="Server-side incident updates"
 *             ),
 *             @OA\Property(
 *                 property="attendance_logs",
 *                 type="array"
 *             ),
 *             @OA\Property(
 *                 property="inspections",
 *                 type="array"
 *             ),
 *             @OA\Property(
 *                 property="training",
 *                 type="array"
 *             )
 *         )
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="server_time", type="string", format="date-time"),
 *         @OA\Property(property="conflict_count", type="integer", example=0),
 *         @OA\Property(property="conflict_strategy", type="string", example="last_updated_wins")
 *     )
 * )
 */

/**
 * @OA\Tag(
 *     name="Sync",
 *     description="Offline synchronization endpoints for mobile and field applications"
 * )
 */

/**
 * @OA\Post(
 *     path="/api/sync",
 *     operationId="syncData",
 *     tags={"Sync"},
 *     summary="Synchronize offline data",
 *     description="Synchronize data from offline mobile/field app. Supports bidirectional sync with conflict resolution.",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Offline data to sync",
 *         @OA\JsonContent(ref="#/components/schemas/SyncRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Sync completed successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SyncResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * SYNC ENDPOINT DOCUMENTATION AND EXPLANATION
 * =============================================
 *
 * The /api/sync endpoint is designed for offline-first mobile and field applications.
 * It enables bidirectional data synchronization with automatic conflict resolution.
 *
 * KEY CONCEPTS:
 *
 * 1. TEMPORARY_ID (Offline Record Identification)
 *    - When a user creates/modifies records offline, they don't have server IDs yet
 *    - Client assigns a temporary_id (UUID format) to track the record locally
 *    - When sync occurs, server persists the record and returns the assigned server ID
 *    - Client maps temporary_id -> server id for future operations
 *    - Example: "temporary_id": "a7f2c3e5-4d...-9h8k2l1m"
 *
 * 2. LOCAL TIMESTAMPS
 *    - local_created_at: When the record was first created on the client device
 *    - local_updated_at: When the record was last modified on the client device
 *    - These help detect conflicts when both client and server modify the same record
 *
 * 3. CONFLICT RESOLUTION STRATEGIES
 *
 *    a) last_updated_wins (Default)
 *       - Compare timestamps: whichever was modified last wins
 *       - Usage: Most scenarios where latest change is most important
 *       - Example: Staff updating their profile, incident status updates
 *
 *    b) client_wins
 *       - Client data always takes precedence over server data
 *       - Usage: When client is authoritative (e.g., mobile inspection app)
 *       - Example: Field inspection data is captured on-site
 *
 *    c) server_wins
 *       - Server data always takes precedence over client data
 *       - Usage: When server is authoritative (e.g., approval systems)
 *       - Example: Manager approvals should not be overridden by fieldworker data
 *
 * 4. CONFLICT HANDLING EXAMPLE
 *
 *    Scenario: User A creates incident offline (local_created_at: 10:00)
 *              User B creates same incident on server (created_at: 10:05)
 *              User A syncs with client modification (local_updated_at: 10:15)
 *
 *    Conflict Resolution:
 *    - last_updated_wins: Client wins (10:15 > 10:05)
 *    - client_wins: Client wins (always)
 *    - server_wins: Server wins (always)
 *
 *    Response includes:
 *    - conflicts[]: Array of conflict records
 *    - synced_records[]: Successfully synced records with assigned server IDs
 *    - server_updates[]: All records modified on server since last_synced_at
 *
 * 5. SYNC FLOW
 *
 *    Step 1: Client collects offline changes
 *            - New incidents: { temporary_id: "uuid", title: "...", local_created_at: "..." }
 *            - Modifications: { id: 5, temporary_id: null, ..., local_updated_at: "..." }
 *
 *    Step 2: Client sends sync request
 *            - Includes last_synced_at to get only new server changes
 *            - Includes conflict_strategy preference
 *
 *    Step 3: Server processes
 *            - Validates and persists client changes
 *            - Detects conflicts based on timestamps
 *            - Resolves conflicts using specified strategy
 *            - Queries all server changes since last_synced_at
 *
 *    Step 4: Server returns response
 *            - synced_records: With assigned server IDs from step 1
 *            - server_updates: All server changes for client to apply locally
 *            - conflicts: Any unresolved conflicts for user intervention
 *
 * 6. USAGE EXAMPLE (Mobile App)
 *
 *    // User creates incident offline
 *    incidents.add({
 *        temporary_id: generateUuid(),
 *        title: "Warehouse spill",
 *        local_created_at: new Date().toISOString(),
 *        ...
 *    })
 *
 *    // Later when connection restored
 *    const response = await api.post('/sync', {
 *        device_id: getDeviceId(),
 *        last_synced_at: localStorage.getItem('lastSynced'),
 *        conflict_strategy: 'last_updated_wins',
 *        data: {
 *            incidents: [...],
 *            inspections: [...]
 *        }
 *    })
 *
 *    // Map temporary_id to server id
 *    response.data.synced_records.incidents.forEach(record => {
 *        const localRecord = incidents.find(r => r.temporary_id === record.temporary_id)
 *        localRecord.id = record.id  // Now has server ID
 *        localRecord.temporary_id = null
 *    })
 *
 *    // Apply server updates
 *    response.data.server_updates.incidents.forEach(record => {
 *        const existing = incidents.find(r => r.id === record.id)
 *        if (existing) {
 *            Object.assign(existing, record)
 *        } else {
 *            incidents.add(record)
 *        }
 *    })
 *
 *    localStorage.setItem('lastSynced', response.data.server_time)
 *
 * 7. ERROR HANDLING
 *
 *    - Validation errors return 422 with field-level errors
 *    - Authorization errors return 403 (user not permitted to sync data)
 *    - Conflicts are returned in response, not treated as errors
 *    - Client should display conflicts for manual resolution
 *
 */
class SyncEndpoints
{
    // This class is used for sync endpoint documentation only
}
