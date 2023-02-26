<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Carbon\Carbon;

class CreateDocumentRequestTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_request_type', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('created_by');
            $table->string('updated_by');
            $table->boolean('is_active')->default(true);
            $table->string('name');
        });

        DB::table('document_request_type')->insert([
            ['name' => 'Attachment', 'created_by' => 'Administrator', 'updated_by' => 'Administrator', 'created_at' =>  \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()],
            ['name' => 'Service Record', 'created_by' => 'Administrator', 'updated_by' => 'Administrator', 'created_at' =>  \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()],
            ['name' => 'Certificate of Employment', 'created_by' => 'Administrator', 'updated_by' => 'Administrator', 'created_at' =>  \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()],
            ['name' => 'Certificate of Employment and Compensation', 'created_by' => 'Administrator', 'updated_by' => 'Administrator', 'created_at' =>  \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_request_type');
    }
}
