<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EripTransaction;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function accounts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.read'), 403);

        $accounts = LedgerAccount::query()->orderBy('id')->get()->map(fn (LedgerAccount $a) => [
            ...$a->toArray(),
            'balance' => $a->balance(),
        ]);

        return response()->json($accounts);
    }

    public function transfer(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.update'), 403);

        $data = $request->validate([
            'from_account_id' => ['required', 'exists:ledger_accounts,id'],
            'to_account_id' => ['required', 'exists:ledger_accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string'],
        ]);

        $batch = $this->ledger->transfer(
            LedgerAccount::query()->findOrFail($data['from_account_id']),
            LedgerAccount::query()->findOrFail($data['to_account_id']),
            (string) $data['amount'],
            $data['memo'] ?? 'transfer',
            $request->user()->id,
        );

        return response()->json(['batch_id' => $batch, 'checksum' => $this->ledger->checksum()], 201);
    }

    public function entries(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.read'), 403);

        $query = LedgerEntry::query()->with('account')->orderByDesc('id');
        if ($account = $request->integer('account_id')) {
            $query->where('account_id', $account);
        }
        if ($type = $request->string('type')->toString()) {
            $query->whereHas('account', fn ($q) => $q->where('type', $type));
        }

        return response()->json($query->paginate($request->integer('limit') ?: 50));
    }

    public function checksum(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.read'), 403);

        return response()->json($this->ledger->checksum());
    }

    public function erip(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.read'), 403);

        return response()->json(EripTransaction::query()->orderByDesc('id')->paginate(40));
    }

    public function storeErip(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.update'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'lot_id' => ['nullable', 'exists:lots,id'],
            'external_id' => ['nullable', 'string', 'max:64'],
        ]);

        $erip = EripTransaction::query()->create([
            'lot_id' => $data['lot_id'] ?? null,
            'external_id' => $data['external_id'] ?? ('ERIP-'.now()->format('YmdHis')),
            'status' => 'pending',
            'amount' => $data['amount'],
            'currency' => 'BYN',
        ]);

        return response()->json($erip, 201);
    }

    public function confirmErip(Request $request, EripTransaction $erip): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.update'), 403);

        $erip->update(['status' => 'confirmed']);

        return response()->json($erip);
    }
}
