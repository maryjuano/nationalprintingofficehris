<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Adjustment extends Model
{

    public const CONST_PREMIUM = 'Premium';
    public const CONST_TAX_TWO_PERCENT = '2% Tax';
    public const CONST_TAX_THREE_PERCENT = '3% Tax';
    public const CONST_PAG_IBIG = 'Pag-Ibig';
    public const CONST_GSIS = 'GSIS';
    public const CONST_PHILHEALTH = 'PhilHealth';
    public const CONST_TAX = 'ITW';

    public const CONST_NAPOWA_DUE = 'Napowa Due';
    public const CONST_MID_YEAR_BONUS = 'Mid Year Bonus';
    public const CONST_YEAR_END_BONUS = 'Year End Bonus';
    public const CONST_PEI = 'Performance Enhancement Incentive';
    public const CONST_CASH_GIFT = 'Cash Gift';
    public const CONST_CLOTHING_ALLOWANCE = 'Clothing Allowance';
    public const CONST_PERA_ALLOWANCE = 'PERA';

    public const CONST_LATE = 'Late';
    public const CONST_UNDERTIME = 'Undertime';
    public const CONST_ABSENCE = 'Absence';
    public const CONST_OVERTIME = 'Overtime';
    public const CONST_BASIC_PAY = 'Basic Pay';

    public const CONST_NIGHT_DIFF = 'Night Differential';

    public const PREFIX_UNDERPAID = 'Underpaid';
    public const PREFIX_OVERPAID = 'Overpaid';
    public const PREFIX_REFUND = 'Refund';
    public const PREFIX_ARREAR = 'Arrear';

    public const PREFIX_SHORT_NAME = [
        self::PREFIX_UNDERPAID => 'UPAID',
        self::PREFIX_OVERPAID => 'OPAID',
        self::PREFIX_REFUND => 'REFUND',
        self::PREFIX_ARREAR => 'ARR',
    ];


    // These are not used (only in migrations to remove).
    public const CONST_UPAID_SALARY = 'Upaid Salary';
    public const CONST_OPAID_ALLOWANCE = 'Opaid Allowance';
    public const CONST_OPAID_REGULAR = 'Opaid Regular';


    public const CONST_TYPE_EARNINGS = 0;
    public const CONST_TYPE_DEDUCTIONS = 1;
    public const CONST_TYPE_STATUTORY = 2;
    public const CONST_TYPE_TAX = 3;

    public const CONST_CATEGORY_OTHER = 0;
    public const CONST_CATEGORY_REGULAR = 1;
    public const CONST_CATEGORY_FRINGE = 2;

    public const CONST_TAXABLE = 0;
    public const CONST_NON_TAXABLE = 1;

    const READ_ONLY_ADJUSTMENTS = [
        self::CONST_NAPOWA_DUE,
        self::CONST_MID_YEAR_BONUS,
        self::CONST_YEAR_END_BONUS,
        self::CONST_PERA_ALLOWANCE,
        self::CONST_PREMIUM,
        self::CONST_TAX_TWO_PERCENT,
        self::CONST_TAX_THREE_PERCENT,
        self::CONST_PAG_IBIG,
        self::CONST_GSIS,
        self::CONST_PHILHEALTH
    ];
    const NO_DEFAULT_ADJUSTMENTS = [
        self::CONST_MID_YEAR_BONUS,
        self::CONST_YEAR_END_BONUS,
    ];


    protected $table = 'adjustments';
    protected $fillable = ['adjustment_name', 'short_name', 'type', 'tax', 'id', 'category', 'ceiling', 'default_amount', 'is_hidden', 'read_only'];
    protected $casts = [
        'status' => 'boolean',
        //'ceiling' => 'float',
        'read_only' => 'boolean',
        'is_hidden' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
