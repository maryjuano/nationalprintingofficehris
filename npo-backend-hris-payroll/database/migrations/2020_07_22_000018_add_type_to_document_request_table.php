<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToDocumentRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('document_request')->truncate();
        Schema::table('document_request', function (Blueprint $table) {
            $table->bigInteger('document_request_type_id');
            $table->bigInteger('extra_id')->nullable();
            $table->dropColumn('document_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_request', function (Blueprint $table) {
            $table->dropColumn('document_request_type_id');
            $table->longtext('document_id');
            $table->dropColumn('extra_id');
        });
    }
}
