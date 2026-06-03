<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PurchaseRequestService
{
    public function create(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {

            $purchaseRequest = PurchaseRequest::create([
                'requested_by' => $user->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'request_type' => $data['request_type'],
                'expected_budget' => $data['expected_budget'],
                'reason' => $data['reason'],
            ]);

            foreach ($data['items'] as $item) {

                $purchaseRequest->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                ]);
            }

            return $purchaseRequest->load([
                'items.product',
                'supplier',
                'requester'
            ]);
        });
    }
}