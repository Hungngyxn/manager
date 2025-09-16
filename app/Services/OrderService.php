<?php

namespace App\Services;

use App\Models\Sku;

class OrderService
{
    protected Sku $sku;
    protected int $quantity;
    protected float $total;
    protected float $fulfill_fee;

    protected float $cost = 0;
    protected float $profit = 0;
    protected float $bonus = 0;

    public function __construct(Sku $sku, int $quantity, float $total, ?float $fulfill_fee = 0)
    {
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->total = $total;
        $this->fulfill_fee = $fulfill_fee ?? 0;
    }

    public function calculate(): array
    {
        $this->cost = $this->sku->cost * $this->quantity;

        $this->profit = $this->total - $this->cost - $this->fulfill_fee;

        if ($this->sku->tierBonus->tier != 'Tier 1') {
            $this->cost *= 1.5;
            $this->profit *= 1.5;
            $this->total *= 1.5;
        }

        $bonus_pct = $this->sku->tier->bonus ?? 0;
        $this->bonus = $this->profit * ($bonus_pct / 100);

        return [
            'cost' => round($this->cost, 2),
            'profit' => round($this->profit, 2),
            'bonus' => round($this->bonus, 2),
            'total' => round($this->total, 2),
        ];
    }

}
