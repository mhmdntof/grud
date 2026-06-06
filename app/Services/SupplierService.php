<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return Supplier::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(int $id, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->update([
            'name' => $data['name'] ?? $supplier->name,
            'email' => $data['email'] ?? $supplier->email,
            'phone' => $data['phone'] ?? $supplier->phone,
            'address' => $data['address'] ?? $supplier->address,
            'notes' => $data['notes'] ?? $supplier->notes,
            'is_active' => $data['is_active'] ?? $supplier->is_active,
        ]);

        return $supplier->fresh();
    }

    public function delete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete(); // SoftDelete
    }

    public function restore(int $id): Supplier
    {
        $supplier = Supplier::withTrashed()->findOrFail($id);
        $supplier->restore();
        return $supplier;
    }

    public function getAll(array $filters = []): array
    {
        $query = Supplier::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $filters['per_page'] ?? 15;
        $suppliers = $query->paginate($perPage);

        return [
            'suppliers' => \App\Http\Resources\SupplierResource::collection($suppliers),
            'pagination' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ];
    }

    public function getById(int $id): Supplier
    {
        return Supplier::with(['products'])->findOrFail($id);
    }
}
