<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Carbon\Carbon;

class DropOldApprovalFlowTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('approval_flows');
        Schema::dropIfExists('approval_levels');
        Schema::dropIfExists('approval_users');
        Schema::dropIfExists('approval_logs_request_list');
        Schema::dropIfExists('approval_employees');
        Schema::dropIfExists('approval_affected_request');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('approval_flow_name')->nullable();
            $table->integer('section_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('request_id')->nullable();
            $table->boolean('pick_employee')->nullable();
            $table->text('employee_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('approval_flow_id')->nullable();
            $table->string('description')->nullable();
            $table->integer('in_order')->default(0);
            $table->integer('order_option')->default(0);
            $table->timestamps();
        });
        Schema::create('approval_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('approval_flow_id')->nullable();
            $table->integer('approval_flow_levels_id')->nullable();
            $table->integer('users_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->boolean('to_be_notified')->default(0);
            $table->boolean('can_approve')->default(0);
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
        Schema::create('approval_logs_request_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->integer('request_id');
            $table->integer('request_type_id');
            $table->integer('approval_flow_id');
            $table->integer('approval_levels_id');
            $table->integer('approval_users');
            $table->integer('is_approved');
            $table->string('is_remarks')->nullable();
            $table->boolean('is_next');
            $table->boolean('is_in_order')->default(0);
            $table->boolean('status');
            $table->timestamps();
        });
        Schema::create('approval_employees', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('approval_flow_id');
            $table->integer('employee_id');
            $table->integer('request_id');
            $table->boolean('status');
            $table->timestamps();
        });
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
}
