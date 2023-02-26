<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsExtraDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports_extra_data', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('report');
            $table->longText('data');
        });

        $nosa_report = array();
        $nosa_report['circular'] = 'National Budget Circular No. 572';
        $nosa_report['circular_date'] = 'January 3, 2018';
        $nosa_report['executive_order'] = 'Executive Order No. 201, s. 2016';

        \App\ReportsExtraData::create([
            'report' => 'nosa',
            'data' => $nosa_report,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports_extra_data');
    }
}
