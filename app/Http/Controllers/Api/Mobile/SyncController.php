<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSyncMapping;
use App\Models\Banking\Transaction as TransactionModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Traits\FillsNonNullableColumns;

class SyncController extends Controller
{
    use FillsNonNullableColumns;
    public function sync(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'records' => 'required|array'
        ]);

        $applied = [];
        $conflicts = [];

        DB::beginTransaction();
        try {
            foreach ($data['records'] as $record) {
                // expect { client_id, op, payload }
                $client_id = $record['client_id'] ?? null;
                $op = $record['op'] ?? 'create';
                $payload = $record['payload'] ?? [];

                if (empty($client_id)) {
                    $conflicts[] = ['client_id' => $client_id, 'reason' => 'invalid_record'];
                    continue;
                }

                // Check existing mapping
                $mapping = MobileSyncMapping::where('client_id', $client_id)->first();

                // Handle operations
                if ($op === 'create') {

                    if ($mapping && $mapping->server_id) {
                        // already applied
                        $applied[] = ['client_id' => $client_id, 'server_id' => $mapping->server_id];
                        continue;
                    }

                    // Create transaction
                    $user = $request->user();
                    $company = $user->companies()->first();

                    $tx = new TransactionModel();
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'company_id') && $company && isset($company->id)) {
                        $tx->company_id = $company->id;
                    }
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'user_id')) {
                        $tx->user_id = $user->id;
                    }
                    $tx->type = $payload['type'] ?? 'income';
                    $tx->amount = $payload['amount_minor'] ?? 0;
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'currency_code')) {
                        $tx->currency_code = $payload['currency'] ?? config('app.currency', 'UGX');
                    }
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'date')) {
                        $tx->date = $payload['date'] ?? now();
                    }
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'description')) {
                        $tx->description = $payload['notes'] ?? null;
                    } elseif (Schema::hasColumn((new TransactionModel())->getTable(), 'notes')) {
                        $tx->notes = $payload['notes'] ?? null;
                    }
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'category_id')) {
                        $tx->category_id = $payload['category_id'] ?? null;
                    }
                    // Fill non-nullable columns with safe defaults for the test DB.
                    $this->fillNonNullableDefaults($tx, (new TransactionModel())->getTable(), $company, $user);

                    // honor client-provided timestamps when present to make sync deterministic
                    if (! empty($payload['client_updated_at'])) {
                        try {
                            $clientTs = Carbon::parse($payload['client_updated_at']);
                            $tx->created_at = $clientTs;
                            $tx->updated_at = $clientTs;
                        } catch (\Exception $e) {
                            // ignore parse errors and let DB set timestamps
                        }
                    }

                    try {
                        $tx->save();
                        // initialize server_version on the saved record
                        if (Schema::hasColumn((new TransactionModel())->getTable(), 'server_version')) {
                            $tx->server_version = 1;
                            $tx->save();
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        throw $e;
                    }

                    // store mapping
                    $mapping = MobileSyncMapping::create([
                        'client_id' => $client_id,
                        'server_id' => $tx->id,
                        'model' => TransactionModel::class,
                        'server_version' => $tx->server_version ?? 1,
                    ]);

                    $applied[] = ['client_id' => $client_id, 'server_id' => $tx->id];

                    continue;
                }

                if ($op === 'update') {
                    // Must have mapping
                    if (! $mapping || ! $mapping->server_id) {
                        $conflicts[] = ['client_id' => $client_id, 'reason' => 'no_mapping'];
                        continue;
                    }

                    $modelClass = $mapping->model ?? TransactionModel::class;
                    // resolve server record without global scopes (company/tenant scopes can hide the row)
                    try {
                        $server = (new $modelClass)->newQueryWithoutScopes()->find($mapping->server_id);
                    } catch (\Throwable $e) {
                        // fallback to default find
                        $server = $modelClass::find($mapping->server_id);
                    }

                    if (! $server) {
                        $conflicts[] = ['client_id' => $client_id, 'reason' => 'missing_server_record'];
                        continue;
                    }

                    // Conflict detection: prefer server_version if client provided it; fall back to timestamps
                    $clientServerVersion = $payload['server_version'] ?? null;
                    $serverVersion = $server->server_version ?? null;

                    if (! is_null($clientServerVersion) && ! is_null($serverVersion)) {
                        if ((int) $serverVersion > (int) $clientServerVersion) {
                            $conflicts[] = ['client_id' => $client_id, 'reason' => 'conflict', 'server_version' => $serverVersion];
                            continue;
                        }
                    } else {
                        // fallback to timestamp-based detection
                        $clientUpdatedAt = isset($payload['client_updated_at']) ? Carbon::parse($payload['client_updated_at']) : null;
                        $serverUpdatedAt = $server->updated_at ? Carbon::parse($server->updated_at) : null;

                        if ($clientUpdatedAt && $serverUpdatedAt && $serverUpdatedAt->greaterThan($clientUpdatedAt)) {
                            $conflicts[] = ['client_id' => $client_id, 'reason' => 'conflict', 'server_updated_at' => $serverUpdatedAt->toDateTimeString()];
                            continue;
                        }
                    }

                    // Apply updates (simple field mapping)
                    $server->type = $payload['type'] ?? $server->type;
                    $server->amount = $payload['amount_minor'] ?? $server->amount;
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'currency_code')) {
                        $server->currency_code = $payload['currency'] ?? $server->currency_code;
                    }
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'date')) {
                        $server->date = $payload['date'] ?? $server->date;
                    }
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'description')) {
                        $server->description = $payload['notes'] ?? $server->description;
                    } elseif (Schema::hasColumn((new TransactionModel())->getTable(), 'notes')) {
                        $server->notes = $payload['notes'] ?? $server->notes;
                    }

                    // If client provided an updated_at timestamp, apply it so future conflict checks observe it
                    if (isset($payload['client_updated_at'])) {
                        try {
                            $server->updated_at = Carbon::parse($payload['client_updated_at']);
                        } catch (\Exception $e) {
                            // ignore parse errors
                        }
                    }

                    // bump server_version to a monotonic increment to avoid timestamp race issues
                    if (Schema::hasColumn((new TransactionModel())->getTable(), 'server_version')) {
                        $server->server_version = (int) ($server->server_version ?? 1) + 1;
                    }

                    $this->fillNonNullableDefaults($server, (new TransactionModel())->getTable(), $request->user()->companies()->first(), $request->user());
                    $server->save();

                    $applied[] = ['client_id' => $client_id, 'server_id' => $server->id, 'op' => 'update', 'server_version' => $server->server_version ?? null];
                    // persist mapping server_version
                    if ($mapping) {
                        $mapping->server_version = $server->server_version ?? null;
                        $mapping->save();
                    }
                    continue;
                }

                if ($op === 'delete') {
                    if (! $mapping || ! $mapping->server_id) {
                        $conflicts[] = ['client_id' => $client_id, 'reason' => 'no_mapping'];
                        continue;
                    }

                    $modelClass = $mapping->model ?? TransactionModel::class;
                    try {
                        $server = (new $modelClass)->newQueryWithoutScopes()->find($mapping->server_id);
                    } catch (\Throwable $e) {
                        $server = $modelClass::find($mapping->server_id);
                    }

                    if (! $server) {
                        $conflicts[] = ['client_id' => $client_id, 'reason' => 'missing_server_record'];
                        continue;
                    }

                    // attempt delete
                    $server->delete();
                    // remove mapping
                    $mapping->delete();

                    $applied[] = ['client_id' => $client_id, 'server_id' => $server->id, 'op' => 'delete'];
                    continue;
                }

                // unknown op
                $conflicts[] = ['client_id' => $client_id, 'reason' => 'unknown_op'];
                continue;
                $user = $request->user();
                $company = $user->companies()->first();

                $tx = new TransactionModel();
                if (Schema::hasColumn((new TransactionModel())->getTable(), 'company_id') && $company && isset($company->id)) {
                    $tx->company_id = $company->id;
                }
                if (Schema::hasColumn((new TransactionModel())->getTable(), 'user_id')) {
                    $tx->user_id = $user->id;
                }
                $tx->type = $payload['type'] ?? 'income';
                $tx->amount = $payload['amount_minor'] ?? 0;
                if (Schema::hasColumn((new TransactionModel())->getTable(), 'currency_code')) {
                    $tx->currency_code = $payload['currency'] ?? config('app.currency', 'UGX');
                }
                if (Schema::hasColumn((new TransactionModel())->getTable(), 'date')) {
                    $tx->date = $payload['date'] ?? now();
                }
                if (Schema::hasColumn((new TransactionModel())->getTable(), 'description')) {
                    $tx->description = $payload['notes'] ?? null;
                } elseif (Schema::hasColumn((new TransactionModel())->getTable(), 'notes')) {
                    $tx->notes = $payload['notes'] ?? null;
                }
                if (Schema::hasColumn((new TransactionModel())->getTable(), 'category_id')) {
                    $tx->category_id = $payload['category_id'] ?? null;
                }
                // Fill non-nullable columns with safe defaults for the test DB.
                $this->fillNonNullableDefaults($tx, (new TransactionModel())->getTable(), $company, $user);

                try {
                    $tx->save();
                } catch (\Illuminate\Database\QueryException $e) {
                    // fallback: rethrow to be handled by outer transaction catch
                    throw $e;
                }

                // store mapping
                $mapping = MobileSyncMapping::create([
                    'client_id' => $client_id,
                    'server_id' => $tx->id,
                    'model' => TransactionModel::class,
                ]);

                $applied[] = ['client_id' => $client_id, 'server_id' => $tx->id];
            }

            DB::commit();

            return response()->json(['applied' => $applied, 'conflicts' => $conflicts]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
