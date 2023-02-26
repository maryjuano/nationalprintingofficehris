<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignatoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->string('report_name');
            $table->integer('signatories_count');
            $table->longText('signatories');
            $table->timestamps();
        });

        $signatories = [
            [
                'report_name' => 'COEC',
                'signatories_count' => 1
            ],
            [
                'report_name' => 'NOSI',
                'signatories_count' => 1
            ],
            [
                'report_name' => 'NOSA',
                'signatories_count' => 1
            ],
            [
                'report_name' => 'Service Record (Document)',
                'signatories_count' => 1
            ],
            [
                'report_name' => 'Application for Leave  (for Rank and File)',
                'signatories_count' => 2
            ],
            [
                'report_name' => 'Application for Leave (for Chief)',
                'signatories_count' => 2
            ],
            [
                'report_name' => 'Application for CTO Leave',
                'signatories_count' => 2
            ],
            [
                'report_name' => 'Payroll Registry',
                'signatories_count' => 4
            ],
            [
                'report_name' => 'Payslips',
                'signatories_count' => 1
            ],
            [
                'report_name' => 'Remittance Report (Masterlist)',
                'signatories_count' => 1
            ],
            [
                'report_name' => 'Cash Advance Regular',
                'signatories_count' => 2
            ],
            [
                'report_name' => 'BUR',
                'signatories_count' => 2
            ],
            [
                'report_name' => 'DV',
                'signatories_count' => 3
            ],
            [
                'report_name' => 'Payroll Report',
                'signatories_count' => 2
            ],
        ];

        foreach ($signatories as $item) {
            $signatory = new \App\Signatories();
            $signatory->report_name = $item['report_name'];
            $signatory->signatories_count = $item['signatories_count'];
            $signatory->signatories = [];
            $signatory->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signatories');
    }
}
