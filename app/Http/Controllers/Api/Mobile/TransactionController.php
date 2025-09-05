<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\BaseMobileController;
use Illuminate\Http\Request;
use App\Models\Banking\Transaction as TransactionModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\FillsNonNullableColumns;

class TransactionController extends BaseMobileController
{
    use FillsNonNullableColumns;
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Simple approach: just get transactions for this user, regardless of company
        // Remove company scope since mobile users don't have company context
        $query = TransactionModel::withoutGlobalScope('App\Scopes\Company')
            ->where('user_id', $user->id)
            ->orderBy('paid_at', 'desc');

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|uuid',
            'type' => 'required|in:income,expense',
            'amount_minor' => 'required|integer',
            'date' => 'nullable|date',
            'category_name' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $user = $request->user();

        // Simple creation without company requirements
        $tx = new TransactionModel();
        $tx->type = $data['type'];
        $tx->amount = $data['amount_minor'];
        $tx->paid_at = $data['date'] ?? now();
        $tx->notes = $data['notes'] ?? null;
        
        // Fill non-nullable columns with safe defaults first
        $this->fillNonNullableDefaults($tx, (new TransactionModel())->getTable(), null, $user);
        
        // Then explicitly set user_id to ensure it's not overridden
        $tx->user_id = $user->id;

        try {
            $tx->save();
        } catch (\Illuminate\Database\QueryException $e) {
            throw $e;
        }

        return response()->json(['id' => $tx->id, 'client_id' => $data['client_id']], 201);
    }
}
