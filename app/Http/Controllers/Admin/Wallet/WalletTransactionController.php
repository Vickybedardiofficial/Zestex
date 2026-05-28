<?php

namespace App\Http\Controllers\Admin\Wallet;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WalletTransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => (string) $request->string('search', ''),
            'type' => (string) $request->string('type', 'all'),
            'status' => (string) $request->string('status', 'all'),
            'direction' => (string) $request->string('direction', 'all'),
            'date_from' => (string) $request->string('date_from', ''),
            'date_to' => (string) $request->string('date_to', ''),
        ];

        $transactions = WalletTransaction::query()
            ->with(['wallet.user'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%")
                        ->orWhere('metadata->reference_id', 'like', "%{$search}%")
                        ->orWhere('metadata->wallet_number', 'like', "%{$search}%")
                        ->orWhereHas('wallet.user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['type'] !== 'all', fn($q) => $q->where('transaction_type', $filters['type']))
            ->when($filters['status'] !== 'all', fn($q) => $q->where('status', $filters['status']))
            ->when($filters['direction'] !== 'all', fn($q) => $q->where('direction', $filters['direction']))
            ->when($filters['date_from'] !== '', fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate(20);

        return view('admin::wallet-transactions.index.index', [
            'transactions' => $transactions,
            'filters' => $filters,
        ]);
    }

    public function show(int $transactionId)
    {
        $transactionData = WalletTransaction::with(['wallet.user'])->findOrFail($transactionId);
        $metadata = is_array($transactionData->metadata) ? $transactionData->metadata : [];

        return view('admin::wallet-transactions.show.index', [
            'transactionData' => $transactionData,
            'metadata' => $metadata,
        ]);
    }
}

