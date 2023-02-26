<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSalariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $salary_tranches = \DB::table('salaries')->groupBy('effectivity_date')->get();
        foreach ($salary_tranches as $salary_tranche) {
            $id = \DB::table('salary_tranche')->insertGetId([
                'effectivity_date' => $salary_tranche->effectivity_date,
                'created_by' => $salary_tranche->created_by,
                'updated_by' => $salary_tranche->updated_by,
                'created_at' => $salary_tranche->created_at,
                'updated_at' => $salary_tranche->updated_at,
            ]);
            \DB::table('salaries')->where('effectivity_date', $salary_tranche->effectivity_date)->update(['max_step_and_max_grade_id' => $id]);
        }
        Schema::table('salaries', function (Blueprint $table) {
            $table->renameColumn('max_step_and_max_grade_id', 'salary_tranche_id');
            $table->dropColumn('effectivity_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->renameColumn('salary_tranche_id', 'max_step_and_max_grade_id');
            $table->date('effectivity_date');
        });
    }
}
