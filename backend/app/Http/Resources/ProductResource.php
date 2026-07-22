<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'description' => $this->description,
            'stock' => $this->whenLoaded('branchStocks', fn () => $this->branchStocks->map(fn ($stock) => [
                'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch->name,
                'quantity' => $stock->quantity,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
