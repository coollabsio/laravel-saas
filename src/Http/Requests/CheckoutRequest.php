<?php

namespace Coollabsio\LaravelSaas\Http\Requests;

use Coollabsio\LaravelSaas\Contracts\PlanContract;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (Billing::isDynamic()) {
            return [
                'plan' => ['required', 'string', Rule::in(['dynamic'])],
            ];
        }

        $planEnum = Billing::planEnum();
        $paidValues = array_map(fn (PlanContract $p) => $p->value, $planEnum::paid());

        return [
            'plan' => ['required', 'string', Rule::in($paidValues)],
            'interval' => ['required', 'string', Rule::in(['monthly', 'yearly'])],
        ];
    }

    public function plan(): PlanContract
    {
        $planEnum = Billing::planEnum();

        return $planEnum::from($this->validated('plan'));
    }

    public function interval(): string
    {
        return $this->validated('interval');
    }
}
