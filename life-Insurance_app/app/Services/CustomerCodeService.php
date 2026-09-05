<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Str;

/** 重複しない顧客コードを発行します。形式: C + 年月 + 6桁英数字（例: C202609-A1B2C3）。 */
final class CustomerCodeService
{
    public function generate(): string
    {
        do {
            $code = 'C'.now()->format('Ym').'-'.strtoupper(Str::random(6));
        } while (Customer::withTrashed()->where('customer_code', $code)->exists());

        return $code;
    }
}
