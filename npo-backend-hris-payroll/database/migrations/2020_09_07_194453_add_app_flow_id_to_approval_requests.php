<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppFlowIdToApprovalRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->bigInteger('app_flow_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropColumn('app_flow_id');
        });
    }
}
