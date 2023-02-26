<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateListEmployeeStubView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement($this->dropView());

        DB::statement($this->createView());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement($this->dropView());
    }

    private function dropView(): string
    {
        return "DROP VIEW IF EXISTS `list_employee_stub_view`";
    }

    private function createView(): string
    {
        return "CREATE
        ALGORITHM = UNDEFINED
        DEFINER = `root`@`localhost`
        SQL SECURITY DEFINER
    VIEW `list_employee_stub_view` AS
        SELECT DISTINCT
            `employees`.`id` AS `employee_id`,
            `personal_information`.`first_name` AS `first_name`,
            `personal_information`.`middle_name` AS `middle_name`,
            `personal_information`.`last_name` AS `last_name`,
            `sections`.`section_name` AS `section_name`,
            `departments`.`department_name` AS `department_name`,
            IF(ISNULL(`employment_and_compensation`.`position_id`),
                `employment_and_compensation`.`position_name`,
                `positions`.`position_name`) AS `position_name`,
            `employee_types`.`employee_type_name` AS `employee_type_name`,
            `employee_stub`.`earnings` AS `earnings`,
            `employee_stub`.`contributions` AS `contributions`,
            `employee_stub`.`deductions` AS `deductions`,
            `employee_stub`.`loans` AS `loans`,
            `employee_stub`.`reimbursements` AS `reimbursements`,
            `employee_stub`.`updated_at` AS `updated_at`,
            `employment_and_compensation`.`salary_grade_id` AS `salary_grade_id`,
            `employment_and_compensation`.`step_increment` AS `step_increment`,
            `employee_id_number`.`id_number` AS `id_number`
        FROM
            ((((((((`employees`
            LEFT JOIN `personal_information` ON ((`employees`.`id` = `personal_information`.`employee_id`)))
            LEFT JOIN `employee_stub` ON ((`employees`.`id` = `employee_stub`.`employee_id`)))
            LEFT JOIN `employment_and_compensation` ON ((`employees`.`id` = `employment_and_compensation`.`employee_id`)))
            LEFT JOIN `sections` ON ((`employment_and_compensation`.`section_id` = `sections`.`id`)))
            LEFT JOIN `employee_types` ON ((`employment_and_compensation`.`employee_type_id` = `employee_types`.`id`)))
            LEFT JOIN `departments` ON ((`employment_and_compensation`.`department_id` = `departments`.`id`)))
            LEFT JOIN `positions` ON ((`employment_and_compensation`.`position_id` = `positions`.`id`)))
            LEFT JOIN `employee_id_number` ON ((`employees`.`id` = `employee_id_number`.`employee_id`)))
        WHERE
            (`employees`.`status` = 1)";
    }
}
