<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 住所を郵便番号・都道府県・市区町村・住所1・住所2・建物名へ分割します。
 *
 * 住所は機微情報のため（仕様 6.8）、分割後の各列も暗号化対象です。
 * 暗号化列は暗号文が元データより長くなるため TEXT 型にします。
 * 既存の address は復号して address_line1 へ移してから削除します。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->text('postal_code')->nullable()->after('birth_date');
            $table->text('prefecture')->nullable()->after('postal_code');
            $table->text('city')->nullable()->after('prefecture');
            $table->text('address_line1')->nullable()->after('city');
            $table->text('address_line2')->nullable()->after('address_line1');
            $table->text('building')->nullable()->after('address_line2');
        });

        $this->moveEncrypted('address', 'address_line1');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->text('address')->nullable()->after('birth_date');
        });

        $this->moveEncrypted('address_line1', 'address');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'postal_code',
                'prefecture',
                'city',
                'address_line1',
                'address_line2',
                'building',
            ]);
        });
    }

    /**
     * 暗号化された列の値を、復号→再暗号化して別の列へ移します。
     * SQL だけでは暗号文を移し替えられないため PHP 側で処理します。
     */
    private function moveEncrypted(string $from, string $to): void
    {
        DB::table('customers')
            ->select(['id', $from])
            ->whereNotNull($from)
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($from, $to): void {
                foreach ($rows as $row) {
                    try {
                        $plain = Crypt::decryptString($row->{$from});
                    } catch (\Throwable) {
                        // 復号できない値は移行対象から外し、元データを壊さないようにします。
                        continue;
                    }

                    DB::table('customers')
                        ->where('id', $row->id)
                        ->update([$to => Crypt::encryptString($plain)]);
                }
            });
    }
};
