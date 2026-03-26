<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Branches'])]

class Branches extends Component
{
    #[Computed]
    public function branches()
    {
        return Branch::withCount('sales')
            ->get()
            ->map(function ($branch) {
                // Aggregate financial data for this branch
                $stats = DB::table('sales')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.branch_id', $branch->id)
                    ->select(
                        DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                        DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost')
                    )
                    ->first();

                $revenue = $stats->total_revenue ?? 0;
                $cost = $stats->total_cost ?? 0;

                // Calculate Profit
                $branch->profit = \Money\Money::PHP((string) ($revenue - $cost));
                $branch->transaction_count = $branch->sales_count;

                return $branch;
            });
    }
}
