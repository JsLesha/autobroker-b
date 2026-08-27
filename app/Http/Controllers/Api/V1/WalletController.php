<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EripTransaction;
use App\Models\LedgerAccount;
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

    public function confirmErip(Request $request, EripTransaction $erip): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('wallets.update'), 403);

        $erip->update(['status' => 'confirmed']);

        return response()->json($erip);
    }
}
