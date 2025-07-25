<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 50)->unique();
            $table->string('code', 50)->unique();
            $table->string('symbol', 5)->nullable();
            $table->timestamps();
        });
        // Direct insert in migration:
        if (DB::table('currencies')->count() === 0) {
            DB::table('currencies')->insert([
                 ['code' =>'AFN' , 'name' => 'Afghani', 'symbol' => '؋' ],
            ['code' =>'ALL' , 'name' => 'Lek', 'symbol' => 'Lek' ],
            ['code' =>'ANG' , 'name' => 'Netherlands Antillian Guilder', 'symbol' => 'ƒ' ],
            ['code' =>'ARS' , 'name' => 'Argentine Peso', 'symbol' => '$' ],
            ['code' =>'AUD' , 'name' => 'Australian Dollar', 'symbol' => '$' ],
            ['code' =>'AWG' , 'name' => 'Aruban Guilder', 'symbol' => 'ƒ' ],
            ['code' =>'AZN' , 'name' => 'Azerbaijanian Manat', 'symbol' => 'ман' ],
            ['code' =>'BAM' , 'name' => 'Convertible Marks', 'symbol' => 'KM' ],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
            ['code' =>'BBD' , 'name' => 'Barbados Dollar', 'symbol' => '$' ],
            ['code' =>'BGN' , 'name' => 'Bulgarian Lev', 'symbol' => 'лв' ],
            ['code' =>'BMD' , 'name' => 'Bermudian Dollar', 'symbol' => '$' ],
            ['code' =>'BND' , 'name' => 'Brunei Dollar', 'symbol' => '$' ],
            ['code' =>'BOB' , 'name' => 'BOV Boliviano Mvdol', 'symbol' => '$b' ],
            ['code' =>'BRL' , 'name' => 'Brazilian Real', 'symbol' => 'R$' ],
            ['code' =>'BSD' , 'name' => 'Bahamian Dollar', 'symbol' => '$' ],
            ['code' =>'BWP' , 'name' => 'Pula', 'symbol' => 'P' ],
            ['code' =>'BYR' , 'name' => 'Belarussian Ruble', 'symbol' => '₽' ],
            ['code' =>'BZD' , 'name' => 'Belize Dollar', 'symbol' => 'BZ$' ],
            ['code' =>'CAD' , 'name' => 'Canadian Dollar', 'symbol' => '$' ],
            ['code' =>'CHF' , 'name' => 'Swiss Franc', 'symbol' => 'CHF' ],
            ['code' =>'CLP' , 'name' => 'CLF Chilean Peso Unidades de fomento', 'symbol' => '$' ],
            ['code' =>'CNY' , 'name' => 'Yuan Renminbi', 'symbol' => '¥' ],
            ['code' =>'COP' , 'name' => 'COU Colombian Peso Unidad de Valor Real', 'symbol' => '$' ],
            ['code' =>'CRC' , 'name' => 'Costa Rican Colon', 'symbol' => '₡' ],
            ['code' =>'CUP' , 'name' => 'CUC Cuban Peso Peso Convertible', 'symbol' => '₱' ],
            ['code' =>'CZK' , 'name' => 'Czech Koruna', 'symbol' => 'Kč' ],
            ['code' =>'DKK' , 'name' => 'Danish Krone', 'symbol' => 'kr' ],
            ['code' =>'DOP' , 'name' => 'Dominican Peso', 'symbol' => 'RD$' ],
            ['code' =>'EGP' , 'name' => 'Egyptian Pound', 'symbol' => '£' ],
            ['code' =>'EUR' , 'name' => 'Euro', 'symbol' => '€' ],
            ['code' =>'FJD' , 'name' => 'Fiji Dollar', 'symbol' => '$' ],
            ['code' =>'FKP' , 'name' => 'Falkland Islands Pound', 'symbol' => '£' ],
            ['code' =>'GBP' , 'name' => 'Pound Sterling', 'symbol' => '£' ],
            ['code' =>'GIP' , 'name' => 'Gibraltar Pound', 'symbol' => '£' ],
            ['code' =>'GTQ' , 'name' => 'Quetzal', 'symbol' => 'Q' ],
            ['code' =>'GYD' , 'name' => 'Guyana Dollar', 'symbol' => '$' ],
            ['code' =>'HKD' , 'name' => 'Hong Kong Dollar', 'symbol' => '$' ],
            ['code' =>'HNL' , 'name' => 'Lempira', 'symbol' => 'L' ],
            ['code' =>'HRK' , 'name' => 'Croatian Kuna', 'symbol' => 'kn' ],
            ['code' =>'HUF' , 'name' => 'Forint', 'symbol' => 'Ft' ],
            ['code' =>'IDR' , 'name' => 'Rupiah', 'symbol' => 'Rp' ],
            ['code' =>'ILS' , 'name' => 'New Israeli Sheqel', 'symbol' => '₪' ],
            ['code' =>'IRR' , 'name' => 'Iranian Rial', 'symbol' => '﷼' ],
            ['code' =>'ISK' , 'name' => 'Iceland Krona', 'symbol' => 'kr' ],
            ['code' =>'JMD' , 'name' => 'Jamaican Dollar', 'symbol' => 'J$' ],
            ['code' =>'JPY' , 'name' => 'Yen', 'symbol' => '¥' ],
            ['code' =>'KGS' , 'name' => 'Som', 'symbol' => 'лв' ],
            ['code' =>'KHR' , 'name' => 'Riel', 'symbol' => '៛' ],
            ['code' =>'KPW' , 'name' => 'North Korean Won', 'symbol' => '₩' ],
            ['code' =>'KRW' , 'name' => 'Won', 'symbol' => '₩' ],
            ['code' =>'KYD' , 'name' => 'Cayman Islands Dollar', 'symbol' => '$' ],
            ['code' =>'KZT' , 'name' => 'Tenge', 'symbol' => 'лв' ],
            ['code' =>'LAK' , 'name' => 'Kip', 'symbol' => '₭' ],
            ['code' =>'LBP' , 'name' => 'Lebanese Pound', 'symbol' => '£' ],
            ['code' =>'LKR' , 'name' => 'Sri Lanka Rupee', 'symbol' => '₨' ],
            ['code' =>'LRD' , 'name' => 'Liberian Dollar', 'symbol' => '$' ],
            ['code' =>'LTL' , 'name' => 'Lithuanian Litas', 'symbol' => 'Lt' ],
            ['code' =>'LVL' , 'name' => 'Latvian Lats', 'symbol' => 'Ls' ],
            ['code' =>'MKD' , 'name' => 'Denar', 'symbol' => 'ден' ],
            ['code' =>'MNT' , 'name' => 'Tugrik', 'symbol' => '₮' ],
            ['code' =>'MUR' , 'name' => 'Mauritius Rupee', 'symbol' => '₨' ],
            ['code' =>'MXN' , 'name' => 'MXV Mexican Peso Mexican Unidad de Inversion (UDI]', 'symbol' => '$' ],
            ['code' =>'MYR' , 'name' => 'Malaysian Ringgit', 'symbol' => 'RM' ],
            ['code' =>'MZN' , 'name' => 'Metical', 'symbol' => 'MT' ],
            ['code' =>'NGN' , 'name' => 'Naira', 'symbol' => '₦' ],
            ['code' =>'NIO' , 'name' => 'Cordoba Oro', 'symbol' => 'C$' ],
            ['code' =>'NOK' , 'name' => 'Norwegian Krone', 'symbol' => 'kr' ],
            ['code' =>'NPR' , 'name' => 'Nepalese Rupee', 'symbol' => '₨' ],
            ['code' =>'NZD' , 'name' => 'New Zealand Dollar', 'symbol' => '$' ],
            ['code' =>'OMR' , 'name' => 'Rial Omani', 'symbol' => '﷼' ],
            ['code' =>'PAB' , 'name' => 'USD Balboa US Dollar', 'symbol' => 'B/.' ],
            ['code' =>'PEN' , 'name' => 'Nuevo Sol', 'symbol' => 'S/.' ],
            ['code' =>'PHP' , 'name' => 'Philippine Peso', 'symbol' => 'Php' ],
            ['code' =>'PKR' , 'name' => 'Pakistan Rupee', 'symbol' => '₨' ],
            ['code' =>'PLN' , 'name' => 'Zloty', 'symbol' => 'zł' ],
            ['code' =>'PYG' , 'name' => 'Guarani', 'symbol' => 'Gs' ],
            ['code' =>'QAR' , 'name' => 'Qatari Rial', 'symbol' => '﷼' ],
            ['code' =>'RON' , 'name' => 'New Leu', 'symbol' => 'lei' ],
            ['code' =>'RSD' , 'name' => 'Serbian Dinar', 'symbol' => 'Дин.' ],
            ['code' =>'RUB' , 'name' => 'Russian Ruble', 'symbol' => 'руб' ],
            ['code' =>'SAR' , 'name' => 'Saudi Riyal', 'symbol' => '﷼' ],
            ['code' =>'SBD' , 'name' => 'Solomon Islands Dollar', 'symbol' => '$' ],
            ['code' =>'SCR' , 'name' => 'Seychelles Rupee', 'symbol' => '₨' ],
            ['code' =>'SEK' , 'name' => 'Swedish Krona', 'symbol' => 'kr' ],
            ['code' =>'SGD' , 'name' => 'Singapore Dollar', 'symbol' => '$' ],
            ['code' =>'SHP' , 'name' => 'Saint Helena Pound', 'symbol' => '£' ],
            ['code' =>'SOS' , 'name' => 'Somali Shilling', 'symbol' => 'S' ],
            ['code' =>'SRD' , 'name' => 'Surinam Dollar', 'symbol' => '$' ],
            ['code' =>'SVC' , 'name' => 'USD El Salvador Colon US Dollar', 'symbol' => '$' ],
            ['code' =>'SYP' , 'name' => 'Syrian Pound', 'symbol' => '£' ],
            ['code' =>'THB' , 'name' => 'Baht', 'symbol' => '฿' ],
            ['code' =>'TRY' , 'name' => 'Turkish Lira', 'symbol' => 'TL' ],
            ['code' =>'TTD' , 'name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$' ],
            ['code' =>'TWD' , 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$' ],
            ['code' =>'UAH' , 'name' => 'Hryvnia', 'symbol' => '₴' ],
            ['code' =>'USD' , 'name' => 'US Dollar', 'symbol' => '$' ],
            ['code' =>'UYU' , 'name' => 'UYI Uruguay Peso en Unidades Indexadas', 'symbol' => '$U' ],
            ['code' =>'UZS' , 'name' => 'Uzbekistan Sum', 'symbol' => 'лв' ],
            ['code' =>'VEF' , 'name' => 'Bolivar Fuerte', 'symbol' => 'Bs' ],
            ['code' =>'VND' , 'name' => 'Dong', 'symbol' => '₫' ],
            ['code' =>'XCD' , 'name' => 'East Caribbean Dollar', 'symbol' => '$' ],
            ['code' =>'YER' , 'name' => 'Yemeni Rial', 'symbol' => '﷼' ],
            ['code' =>'ZAR' , 'name' => 'Rand', 'symbol' => 'R' ],
            ]);
        }
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currencies');
    }
}
