<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('approval_level_id');
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
        });
        Schema::create('approval_item_employee', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('approval_item_id');
            $table->bigInteger('approver_id');
            $table->boolean('can_approve')->default(false);
            $table->longText('remarks')->nullable();
            $table->longText('attachments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_items');
        Schema::dropIfExists('approval_item_employee');
    }
}
