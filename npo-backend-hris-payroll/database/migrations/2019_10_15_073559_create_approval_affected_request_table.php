<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateApprovalAffectedRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_affected_request', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        DB::table('approval_affected_request')->insert([
            ['name' => 'DTR Submission', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'DTR Adjustments', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Time Off', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Employee Overtime', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Employee Information', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Employee Document', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Employee Schedule', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Employee Loan', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Employee Contribution', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_affected_request');
    }
}
