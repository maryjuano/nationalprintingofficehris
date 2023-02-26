<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigSignatoriesForNosiExcel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Signatories::create([
            'signatories_count' => 2,
            'report_name' => 'NOSI_XLS',
            'signatories' => []
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\Signatories::where('report_name', 'NOSI_XLS')->delete();
    }
}
