<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\InternalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalRequestController extends Controller
{
    public function index()
    {
        return InternalRequest::with('department', 'requester', 'approver', 'items.product', 'approvals.approver')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'integer'],
            'assigned_approver_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', 'string', 'max:50'],
            'justification' => ['nullable', 'string'],
            'needed_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $internalRequest = InternalRequest::create([
                'request_number' => 'REQ-' . now()->format('YmdHis'),
                'department_id' => $data['department_id'] ?? null,
                'requested_by' => $request->user()?->id,
                'assigned_approver_id' => $data['assigned_approver_id'] ?? User::whereIn('role', ['director', 'administrador'])->value('id'),
                'priority' => $data['priority'],
                'status' => 'pendente',
                'justification' => $data['justification'] ?? null,
                'needed_at' => $data['needed_at'] ?? null,
                'submitted_at' => now(),
            ]);

            $internalRequest->items()->createMany($data['items']);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'Criou pedido interno',
                'entity' => 'InternalRequest',
                'entity_id' => $internalRequest->id,
                'metadata' => ['request_number' => $internalRequest->request_number],
            ]);

            return $internalRequest->load('department', 'requester', 'approver', 'items.product');
        });
    }

    public function show(InternalRequest $internalRequest)
    {
        return $internalRequest->load('department', 'requester', 'approver', 'items.product', 'approvals.approver');
    }

    public function update(Request $request, InternalRequest $internalRequest)
    {
        $internalRequest->update($request->validate([
            'department_id' => ['nullable', 'integer'],
            'assigned_approver_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'justification' => ['nullable', 'string'],
            'needed_at' => ['nullable', 'date'],
        ]));

        return $internalRequest->load('department', 'requester', 'approver', 'items.product', 'approvals.approver');
    }

    public function approve(Request $request, InternalRequest $internalRequest)
    {
        return $this->decide($request, $internalRequest, 'aprovado');
    }

    public function reject(Request $request, InternalRequest $internalRequest)
    {
        return $this->decide($request, $internalRequest, 'rejeitado');
    }

    public function destroy(InternalRequest $internalRequest)
    {
        $internalRequest->delete();

        return response()->noContent();
    }

    private function decide(Request $request, InternalRequest $internalRequest, string $decision)
    {
        $data = $request->validate(['comment' => ['nullable', 'string']]);
        $user = $request->user();

        if (
            $internalRequest->assigned_approver_id &&
            $internalRequest->assigned_approver_id !== $user?->id &&
            ! in_array($user?->role, ['administrador', 'director'], true)
        ) {
            abort(403, 'Este pedido esta atribuido a outro responsavel de aprovacao.');
        }

        Approval::create([
            'internal_request_id' => $internalRequest->id,
            'approved_by' => $request->user()?->id,
            'decision' => $decision,
            'comment' => $data['comment'] ?? null,
            'decided_at' => now(),
        ]);

        $internalRequest->update(['status' => $decision]);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => $decision === 'aprovado' ? 'Aprovou pedido interno' : 'Rejeitou pedido interno',
            'entity' => 'InternalRequest',
            'entity_id' => $internalRequest->id,
            'metadata' => ['request_number' => $internalRequest->request_number, 'comment' => $data['comment'] ?? null],
        ]);

        return $internalRequest->load('department', 'requester', 'approver', 'items.product', 'approvals.approver');
    }
}
