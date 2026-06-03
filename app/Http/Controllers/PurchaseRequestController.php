<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Services\PurchaseRequestService;
class PurchaseRequestController extends Controller
{
    protected PurchaseRequestService $purchaseRequestService;

    public function __construct(
        PurchaseRequestService $purchaseRequestService
    ) {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function store(StorePurchaseRequestRequest $request)
    {
       return $this->purchaseRequestService->create(
    $request->validated(),
    Auth::user()
);
    }
}
