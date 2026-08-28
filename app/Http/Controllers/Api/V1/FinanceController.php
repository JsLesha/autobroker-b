<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinanceLine;
use App\Models\Invoice;
use App\Models\Lot;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    public function lines(Request $request, Lot $lot): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('finance.read'), 403);

        return response()->json($lot->financeLines()->orderBy('id')->get());
    }

    public function upsertLine(Request $request, Lot $lot): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('finance.update'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_paid' => ['sometimes', 'boolean'],
            'is_ag' => ['sometimes', 'boolean'],
            'finance_checked' => ['sometimes', 'boolean'],
            'logist_checked' => ['sometimes', 'boolean'],
        ]);

        $line = FinanceLine::query()->updateOrCreate(
            ['lot_id' => $lot->id, 'code' => $data['code']],
            $data,
        );

        return response()->json($line);
    }

    public function invoices(Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);

        return response()->json($lot->invoices);
    }

    public function storeInvoice(Request $request, Lot $lot): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('finance.update'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $invoice = Invoice::query()->create([
            'lot_id' => $lot->id,
            'number' => 'INV-'.$lot->id.'-'.now()->format('YmdHis'),
            'status' => 'draft',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'preview_token' => Str::random(64),
            'preview_expires_at' => now()->addDays(7),
        ]);

        return response()->json($invoice, 201);
    }

    public function preview(string $token): JsonResponse
    {
        $invoice = Invoice::query()->where('preview_token', $token)->firstOrFail();

        if ($invoice->preview_expires_at?->isPast()) {
            abort(410, 'Ссылка недействительна.');
        }

        return response()->json($invoice->load('lot'));
    }

    public function decidePreview(Request $request, string $token): JsonResponse
    {
        $invoice = Invoice::query()->where('preview_token', $token)->firstOrFail();
        $decision = $request->validate(['decision' => ['required', 'in:accept,reject']]);

        if ($decision['decision'] === 'accept') {
            $invoice->forceFill(['accepted_at' => now(), 'status' => 'accepted'])->save();
        } else {
            $invoice->forceFill(['rejected_at' => now(), 'status' => 'rejected'])->save();
        }

        return response()->json($invoice);
    }

    public function storePayment(Request $request, Lot $lot): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('finance.update'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'type' => ['sometimes', 'in:incoming,outgoing'],
            'method' => ['nullable', 'string', 'max:32'],
            'comment' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:32'],
        ]);

        $payment = Payment::query()->create([
            'lot_id' => $lot->id,
            'amount' => $data['amount'],
            'type' => $data['type'] ?? 'incoming',
            'method' => $data['method'] ?? null,
            'comment' => $data['comment'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $request->user()->id,
        ]);

        return response()->json($payment, 201);
    }
}
