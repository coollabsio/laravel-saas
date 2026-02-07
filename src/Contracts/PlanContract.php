<?php

namespace Coollabsio\LaravelSaas\Contracts;

interface PlanContract
{
    /**
     * @return list<self>
     */
    public static function paid(): array;

    public function stripePriceId(string $interval = 'monthly'): ?string;

    public function label(): string;

    public function tier(): int;

    public static function fromPriceId(string $priceId): ?self;
}
