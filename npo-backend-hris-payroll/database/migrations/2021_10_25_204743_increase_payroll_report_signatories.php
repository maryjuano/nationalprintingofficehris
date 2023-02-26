<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreasePayrollReportSignatories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $signatory = \App\Signatories::where('report_name', '=', 'Payroll Report')->first();
        $signatory->signatories_count = 3;
        $temp = $signatory->signatories;
        array_push($temp, array('id'=> 2, 'name' => '', 'title' => ''));
        $signatory->signatories = $temp;
        $signatory->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
