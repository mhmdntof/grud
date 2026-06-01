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

//موافقة مدير المشفى على طلب القسم 

public function managerApproval(
    int $requestId,
    array $data
)
{
    $requestOrder = RequestOrder::findOrFail(
        $requestId
    );

    if (
        $requestOrder->manager_status !== 'pending'
    ) {
        throw new \Exception(
            'Request already processed'
        );
    }

    $requestOrder->manager_status =
        $data['status'];

    if (
        $data['status'] === 'rejected'
    ) {

        $requestOrder->rejection_reason =
            $data['rejection_reason'];
    }

    $requestOrder->save();

    return $requestOrder;
}



}