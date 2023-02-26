<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoanRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('status'); //0 pending, 1 ongoing, 2 denied, 3 completed
            $table->timestamps();
            //section 1
            $table->integer('employee_users_id');
            $table->string('employee_name');
            $table->integer('direct_report_id'); //reporting manager
            $table->string('mobile_number');
            $table->string('email');
            $table->integer('department_id');
            //section 2
            $table->integer('loan_type_id'); //from loans
            $table->float('loan_amount');
            $table->integer('ammortization_number');
            $table->integer('ammortization_period'); //0 - months, 1 - year
            $table->string('purpose');
            $table->longtext('attachments')->nullable();
            //section 3
            $table->integer('approval_flow_id');
            //etc
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_requests');
    }
}
