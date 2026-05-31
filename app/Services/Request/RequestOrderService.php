<?php

namespace App\Services\Requests;

use App\Models\RequestOrder;
use App\Models\User;
use App\Models\RequestOrderItem;
use Illuminate\Support\Facades\DB;

class RequestOrderService
{
    public function create(array $data, User $user)
{
    return DB::transaction(function () use ($data, $user) {

        $requestOrder = RequestOrder::create([
            'department_id' => $user->department_id,
            'requested_by' => $user->id,
        ]);

        $requestOrder->items()->createMany($data['items']);

        return $requestOrder->load([
            'items.product',
            'department',
            'requester'
        ]);
    });
}
}