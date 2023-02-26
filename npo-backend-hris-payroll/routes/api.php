<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    [
        'middleware' => [
            'api'
        ]
    ],
    function ($router) {
        #### Reports

        Route::get('/formB', 'ReportContoller@FormB');
        Route::get('/formBtable', 'ReportContoller@formBtable');
        Route::get('/formC', 'ReportContoller@FormC');
        Route::get('/service_worker', 'ReportContoller@service_worker');
        Route::get('/gsis_service', 'ReportContoller@gsis_service');
        Route::get('/service_record', 'ReportContoller@service_record');
        Route::get('/gsis_service_record', 'ReportContoller@gsis_service_record');
        Route::get('/plantilla_positions', 'ReportContoller@plantillaPositions');
        Route::get('/attendance_general', 'ReportContoller@general');
        Route::get('/attendance_individual', 'ReportContoller@attendance_individual');
        Route::get('/attendance_general_table', 'ReportContoller@generalTable');
        Route::get('/emp_cert/{empid}', 'Reports\CoecController@empCert');
        Route::get('/coe/{employee}', 'Reports\CoecController@coe');
        Route::get('/pds', 'ReportContoller@pds');
        Route::get('/formA', 'ReportContoller@FormA');
        Route::get('/formD', 'ReportContoller@FormD');
        Route::get('/formE', 'ReportContoller@FormE');
        Route::get('/platillaReport', 'ReportContoller@plantillaReport');
        Route::get('/payroll_card', 'Reports\PayrollCardController@get');

        // C1S
        Route::get('/gsis/pdf', 'GSISReportController@pdf');
        Route::get('/gsis/excel', 'GSISReportController@excel');


        // Route::get('/CAreg/{payrollID}', 'ReportContoller@reportCashAdvance');
        // Route::get('/CAregAttach/{payrollID}', 'ReportContoller@reportCashAdvanceAttach');
        // Route::get('/PayrollRep/{payrollID}', 'ReportContoller@reportPayroll');
        // Route::get('/BUR/{payrollID}', 'ReportContoller@reportBUR');
        // Route::get('/DV/{payrollID}', 'ReportContoller@reportDV');
        Route::get('/payrollrun_report/{payrollID}', 'Reports\PayrollRunController@getReport');
        Route::get('/payrollrun_report/{payrollID}/simulated_summary', 'Reports\PayrollRunReportController@getSimulatedSummary');
        Route::get('/payrollrun_report/{payrollID}/payslip', 'Reports\PayslipController@downloadPayslipById');
        Route::get('/payrollrun_report/{payrollID}/overtime_report', 'Reports\PayrollRunReportController@getOvertimeReport');

        Route::get('/payrollrun_report/{payrollID}/bank', 'Reports\PayrollBankFileController@getBankFile');
        Route::get('/registry', 'Reports\PayrollRegistryController@registry');

        # nosi
        Route::get('/nosi', 'Reports\NosiController@list');
        Route::get('/nosi/pdfs', 'Reports\NosiController@getPdfs');
        Route::get('/nosi/table', 'Reports\NosiController@getTable');
        Route::get('/nosi/{empid}', 'Reports\NosiController@read');
        Route::get('/nosi_candidates', 'Reports\NosiController@candidates');

        # nosa
        Route::get('/nosa', 'Reports\NosaController@list');
        Route::get('/nosa/pdfs', 'Reports\NosaController@getPdfs');
        Route::get('/nosa/table', 'Reports\NosaController@getTable');
        Route::get('/nosa/extra_data', 'Reports\NosaController@getNosaExtraData');
        Route::get('/nosa/{empid}', 'Reports\NosaController@read');
        Route::get('/LeaveCard', 'ReportContoller@leaveCard');

        #### Payroll
        Route::post('/payroll/getMyTax', 'PayrollController@getMyTax');
        Route::post('/payroll/getMyGSIS', 'PayrollController@getMyGSIS');
        Route::post('/payroll/getMyPhilHealth', 'PayrollController@getMyPhilHealth');
        Route::post('/payroll/generate', 'PayrollController@generate');
        Route::get('/payroll/exportPayrollRegistry', 'PayrollController@exportPayrollRegistry');
        Route::get('/payroll/exportAlphaList/{type}/{startDate}/{endDate}/{employeeIds}', 'PayrollController@exportAlphaList');
        Route::get('/payroll/generatePayslip/{startDate}/{endDate}/{employeeId}', 'PayrollController@generatePayslip');

        #### Login
        Route::post('/login', 'LoginController@login');
        Route::post('/user_check', 'EmployeeController@get_user_password');

        #### Time Data
        Route::post('/time_data', 'TimeDataController@create');
        Route::put('/time_data/{time_data}', 'TimeDataController@update');
        Route::put('/time_data/{time_data}/set_status', 'TimeDataController@set_status');
        Route::get('/time_data', 'TimeDataController@list');

        #### Time Off
        Route::post('/time_off', 'TimeOffController@create');
        Route::get('/time_off/colors', 'TimeOffController@time_off_color_list');
        Route::get('/time_off/{time_off}', 'TimeOffController@read');
        Route::put('/time_off/{time_off}', 'TimeOffController@update');
        Route::put('/time_off/{time_off}/set_status', 'TimeOffController@set_status');
        Route::get('/time_offs', 'TimeOffController@list');

        #### Position
        Route::post('/position', 'PositionController@create');
        Route::put('/position/{position}', 'PositionController@update');
        Route::put('/position/{position}/set_status', 'PositionController@set_status');
        Route::get('/positions', 'PositionController@list');
        Route::get('/positions/unique', 'PositionController@get_unique_positions');
        Route::get('/positions/{position}/read', 'PositionController@read');

        #### Adjustment
        Route::post('/adjustment', 'AdjustmentController@create');
        Route::get('/adjustment/{adjustment}', 'AdjustmentController@read');
        Route::put('/adjustment/{adjustment}', 'AdjustmentController@update');
        Route::put('/adjustment/{adjustment}/set_status', 'AdjustmentController@set_status');
        Route::get('/adjustments', 'AdjustmentController@list');

        #### Courses
        Route::post('/course', 'CoursesController@create');
        Route::put('/course/{course}', 'CoursesController@update');
        Route::put('/course/{course}/set_status', 'CoursesController@set_status');
        Route::get('/courses', 'CoursesController@list');
        Route::get('/course/{course}', 'CoursesController@read');

        #### Signatories
        Route::put('/signatory', 'SignatoriesController@update');
        Route::get('/signatories', 'SignatoriesController@list');

        #### Work Schedule
        Route::post('/work_schedule', 'WorkScheduleController@create');
        Route::put('/work_schedule/{work_schedule}', 'WorkScheduleController@update');
        Route::put('/work_schedule/{work_schedule}/set_status', 'WorkScheduleController@set_status');
        Route::get('/work_schedules', 'WorkScheduleController@list');
        Route::get('/work_schedule/{work_schedule}/read', 'WorkScheduleController@read');
        Route::put('/work_schedule/{work_schedule}/update', 'WorkScheduleController@update');
        Route::put('/work_schedule/{work_schedule}/breaks_set_status', 'WorkScheduleController@breaks_set_status');


        #### Employee Type
        Route::post('/employee_type', 'EmployeeTypeController@create');
        Route::put('/employee_type/{employee_type}', 'EmployeeTypeController@update');
        Route::put('/employee_type/{employee_type_id}/set_status', 'EmployeeTypeController@set_status');
        Route::get('/employee_types', 'EmployeeTypeController@list');

        #### Document Type
        Route::post('/document_type', 'DocumentTypeController@create');
        Route::put('/document_type/{document_type}', 'DocumentTypeController@update');
        Route::put('/document_type/{document_type_id}/set_is_active', 'DocumentTypeController@set_is_active');
        Route::get('/document_types', 'DocumentTypeController@list');

        #### Salary Tranche
        Route::post('/salary', 'SalaryController@create');
        Route::put('/salary/{salary}', 'SalaryController@update');
        Route::get('/salaries', 'SalaryController@list');
        Route::put('/salary/{salary}/set_status', 'SalaryController@set_status');
        Route::get('/salary/{salary}', 'SalaryController@read');

        #### Holidays
        Route::post('/holiday', 'HolidayController@create');
        Route::get('/holidays', 'HolidayController@list');
        Route::put('/holiday/{holiday}', 'HolidayController@update');
        Route::get('/holidays/{holiday}', 'HolidayController@read');

        #### Departments
        Route::post('/department', 'DepartmentController@create');
        Route::get('/departments', 'DepartmentController@list');
        Route::put('/department/{department}/set_status', 'DepartmentController@set_status');
        Route::put('/department/{department}', 'DepartmentController@update');
        Route::get('/department/{department}/get_details', 'DepartmentController@read');

        #### Employees
        Route::get('/employees', 'EmployeeController@list');
        Route::get('/employees_for_dropdown', 'EmployeeController@list_employees_for_dropdown');
        Route::get('/employee/get_card/{employee_id}', 'EmployeeController@get_card_details');
        Route::post('/employee/save', 'EmployeeController@save');
        Route::post('/employee/save_and_activate', 'EmployeeController@save_and_activate');
        Route::put('/employee/{employee}/edit_save', 'EmployeeController@edit_save');
        Route::put('/employee/{employee}/edit_and_activate', 'EmployeeController@edit_and_activate');
        Route::get('/employee/{employee}/get_employee_name', 'EmployeeController@get_employee_name');
        #### General
        #### Personal Information
        Route::get('/employee/{employee}/personal_information', 'EmployeeController@view_personal_information');
        Route::put('/employee/{employee}/personal_information', 'EmployeeController@update_personal_information');
        Route::get('/employee/{employee}/location', 'EmployeeController@view_location');
        Route::put('/employee/{employee}/location', 'EmployeeController@update_location');
        Route::get('/employee/{employee}/contact_information', 'EmployeeController@view_contact_information');
        Route::put('/employee/{employee}/contact_information', 'EmployeeController@update_contact_information');
        #### Family Background
        Route::get('/employee/{employee}/family_background', 'EmployeeController@view_family_background');
        Route::put('/employee/{employee}/family_background', 'EmployeeController@update_family_background');
        #### Educational Background
        Route::get('/employee/{employee}/educational_background', 'EmployeeController@view_educational_background');
        Route::put('/employee/{employee}/educational_background', 'EmployeeController@update_educational_background');
        #### Civil Service
        Route::get('/employee/{employee}/civil_service', 'EmployeeController@view_civil_service');
        Route::put('/employee/{employee}/civil_service', 'EmployeeController@update_civil_service');
        #### Work Experience
        Route::get('/employee/{employee}/work_experience', 'EmployeeController@view_work_experience');
        Route::put('/employee/{employee}/work_experience', 'EmployeeController@update_work_experience');
        #### Voluntary Work
        Route::get('/employee/{employee}/voluntary_work', 'EmployeeController@view_voluntary_work');
        Route::put('/employee/{employee}/voluntary_work', 'EmployeeController@update_voluntary_work');
        #### Training Program
        Route::get('/employee/{employee}/training_program', 'EmployeeController@view_training_program');
        Route::put('/employee/{employee}/training_program', 'EmployeeController@update_training_program');
        #### Other Information
        Route::get('/employee/{employee}/other_information', 'EmployeeController@view_other_information');
        Route::put('/employee/{employee}/other_information', 'EmployeeController@update_other_information');
        #### Questionaire
        Route::get('/employee/{employee}/questionnaire', 'EmployeeController@view_questionnaire');
        Route::put('/employee/{employee}/questionnaire', 'EmployeeController@update_questionnaire');
        #### Government Id
        Route::get('/employee/{employee}/government_id', 'EmployeeController@view_government_id');
        Route::put('/employee/{employee}/government_id', 'EmployeeController@update_government_id');
        #### Reference
        Route::get('/employee/{employee}/reference', 'EmployeeController@view_references');
        Route::put('/employee/{employee}/reference', 'EmployeeController@update_references');

        #### Employment and Compensation
        Route::get('/employee/{employee}/employment_and_compensation', 'EmployeeController@view_employment_and_compensation');
        #### Payment Details
        Route::get('/employee/{employee}/payroll_information', 'EmployeeController@view_payroll_information');
        Route::put('/employee/{employee}/payroll_information', 'EmployeeController@update_payroll_information');
        #### Work Schedule
        Route::get('/employee/{employee}/work_schedule', 'EmployeeController@view_work_schedule');
        Route::put('/employee/{employee}/work_schedule', 'EmployeeController@update_work_schedule');
        #### Job Information
        Route::get('/employee/{employee}/job_information', 'EmployeeController@view_job_information');
        Route::put('/employee/{employee}/job_information', 'EmployeeController@update_job_information');


        #### Time Off Balance
        Route::get('/employee/{employee}/time_off_balance', 'EmployeeController@view_time_off_balance');

        #### System Information
        Route::get('/employee/{employee}/system_information', 'EmployeeController@view_system_information');

        Route::get('/employee/{employee}/pending_time_off', 'EmployeeController@view_pending_time_off');
        Route::get('/employee/{employee}/upcoming_time_off', 'EmployeeController@view_upcoming_time_off');
        Route::get('/employee/{employee}/time_off_history', 'EmployeeController@view_time_off_history');
        Route::get('/employee/{employee}/status', 'EmployeeController@get_employee_status');

        #### File Upload Attachment
        Route::post('/employee/{employee}/file_upload', 'DocumentController@add_documents');
        Route::get('/employee/{employee}/file_read', 'DocumentController@read_documents');
        Route::get('/file_lists', 'DocumentController@list_documents');
        Route::put('/employee/{employee}/archive_documents', 'DocumentController@archive_documents');
        Route::post('/employee/power_file_upload', 'DocumentController@create_power_upload');
        // Route::post('/employee/{employee_id}/power_file_upload', 'DocumentController@update_power_upload');

        #### File Upload Response
        Route::post('/file', 'FileController@upload_file');
        Route::get('/file', 'FileController@read_file');

        #### save profile image
        Route::post('/upload/profile', 'ProfilePictureController@create');
        Route::put('/upload/{profile_id}/edit', 'ProfilePictureController@update');
        Route::put('/employee/{employee}/update_profile', 'EmployeeController@update_profile');
        Route::get('/upload/{profile_id}/read', 'ProfilePictureController@read');

        #### Self-Service
        Route::get('/self_service/login_info', 'EmployeeController@employee_login_information');
        #### Edit Requests
        Route::post('/self_service/edit_request', 'EditRequestController@create');
        ## HRIS, LIST ALL
        Route::get('/edit_requests/history', 'EditRequestController@list_all_edit_requests_history');
        ## SELF SERVICE, LIST USER'S


        ### system information
        Route::put('/system_information/{employee}', 'EmployeeController@edit_system_info_email');
        Route::put('/system_information/{employee}/privileges', 'EmployeeController@edit_system_info_privileges');
        Route::get('/system_information', 'EmployeeController@list_view_system_information');
        Route::get('/system_information/{employee}/user_logs', 'Controller@list_user_actions');
        Route::get('/self_service/system_information/user_logs', 'Controller@list_user_actions_ss');

        ### directory

        ### sections
        Route::get('/section_list', 'DepartmentController@section_list');
        Route::put('/section_edit/{section}', 'DepartmentController@section_edit');
        Route::get('/section_read/{department_id}', 'DepartmentController@section_read');
        Route::put('/section_status/{section}', 'DepartmentController@section_status');
        Route::get('/section_list', 'DepartmentController@section_list');
        Route::delete('/section_delete/{section}', 'DepartmentController@section_delete');

        ### statutory - gsis
        Route::get('/statutory', 'StatutoryController@view');
        Route::put('/statutory/{statutory}', 'StatutoryController@update');
        Route::put('/statutory/activate/{statutory}', 'StatutoryController@activate');
        Route::put('/statutory/deactivate/{statutory}', 'StatutoryController@deactivate');

        ### pagibig
        Route::post('/statutory/pagibig', 'StatutoryController@add_pagibig');
        Route::put('/statutory/pagibig/{statutory}', 'StatutoryController@update_pagibig');
        Route::get('/statutory/pagibig/{statutory}', 'StatutoryController@read_pagibig');
        Route::get('/statutory/pagibig_list', 'StatutoryController@list_pagibig');

        ### philhealth
        Route::post('/statutory/philhealth', 'StatutoryController@add_philhealth');
        Route::put('/statutory/philhealth/{statutory}', 'StatutoryController@update_philhealth');
        Route::get('/statutory/philhealth/{statutory}', 'StatutoryController@read_philhealth');
        Route::get('/statutory/philhealth_list', 'StatutoryController@list_philhealth');

        ### Loan
        Route::post('/loan', 'LoanController@create');
        Route::get('/loan/{loan}', 'LoanController@read');
        Route::put('/loans/{loan}', 'LoanController@update');
        Route::get('/loans', 'LoanController@list');
        Route::put('/loan/{loan}/set_status', 'LoanController@set_status');



        Route::get('/salary_range/read', 'SalaryController@read_salary_range');
        Route::put('/salary_range/update', 'SalaryController@update_salary_range');

        ### document self Service request
        Route::post('/self_service/document/document_request', 'DocumentRequestController@create');
        ## HRIS, LIST ALL
        Route::get('/document_requests/history', 'DocumentRequestController@list_all_document_requests_history');
        ## SELF SERVICE, LIST USER'S
        Route::get('/self_service/{user_id}/document_requests', 'DocumentRequestController@list_user_doc_requests');
        Route::get('/self_service/{user_id}/document_requests_history', 'DocumentRequestController@list_user_doc_requests_history');

        ####contribution

        ### Tax
        Route::post('/tax', 'TaxController@create');
        Route::put('/tax', 'TaxController@update');
        Route::get('/tax_tables', 'TaxController@list');
        Route::get('/tax_table', 'TaxController@get');
        Route::get('/effective_tax_table', 'TaxController@get_effective_tax_table');

        // Route::get('/generate', 'EmployeeController@auto_generated_id');

        #### contribution SS

        ###admin

        ##loan document
        Route::post('/loan_request/create/upload_loan_file', 'LoanRequestController@upload_loan_file');
        Route::put('/loan_request/delete/{file_id}', 'LoanRequestController@delete_files_loan');
        Route::get('/loan_request/read/read_files_loan', 'LoanRequestController@read_files_loan');
        Route::get('/loan_request/read/{emp_id}', 'LoanRequestController@read_files_loan_with_params');

        ### NEW APIs

        ### Salary Tranche
        Route::post('/salary_tranche', 'SalaryTrancheController@create');
        Route::put('/salary_tranche/{salary_tranche}', 'SalaryTrancheController@update');
        Route::get('/salary_tranches', 'SalaryTrancheController@list');
        Route::put('/salary_tranche/{salary_tranche}/set_status', 'SalaryTrancheController@set_status');
        Route::get('/salary_tranches/list_active_salary_grades', 'SalaryTrancheController@list_active_salary_grades');

        ### Employee
        Route::get('/employee/{employee}/employment_history', 'EmployeeController@view_employment_history');
        Route::put('/employee/{employee}/employment_history', 'EmployeeController@update_employment_history');
        Route::post('/employee/offboard', 'EmployeeController@save_offboard');

        ### Contribution
        Route::get('/contribution/get_years_with_contributions', 'ContributionController@get_years_with_contributions');
        Route::get('/contribution/remittances', 'ContributionController@list_remittances');
        Route::get('/contribution/remittances/{employee_id}', 'ContributionController@read_employee_remittance');
        Route::get('/contribution/remittances/all/export', 'ContributionController@export_remittances');
        Route::get('/self_service/remittances', 'ContributionController@read_employee_remittance_ss');
        Route::get('/contribution/employee_contributions', 'ContributionController@list_employee_contributions');
        Route::get('/contribution/contributions/{employee_id}', 'ContributionController@read_employee_contribution');
        Route::get('/self_service/contributions', 'ContributionController@read_employee_contribution_ss');

        ### Contribution Remittances
        Route::get('/contribution/report/remittances/list_employees', 'Reports\RemittanceController@list_employees');
        Route::get('/contribution/report/remittances/gsis', 'Reports\RemittanceController@gsis');
        Route::get('/contribution/report/remittances/philhealth', 'Reports\RemittanceController@philhealth');
        Route::get('/contribution/report/remittances/taxes', 'Reports\RemittanceController@taxes');
        Route::get('/contribution/report/remittances/pagibig', 'Reports\RemittanceController@pagibig');


        ### Department
        Route::get('/department/{department}/get_employees', 'DepartmentController@get_employees_for_department');

        ### Section
        Route::get('/section/{section}/get_employees', 'DepartmentController@get_employees_for_section');

        ### Approval FLow
        Route::post('/approval_flow', 'AppFlowController@create');
        Route::get('/approval_flows', 'AppFlowController@list');
        Route::put('/approval_flow/{approval_flow}', 'AppFlowController@update');
        Route::put('/approval_flow/{approval_flow}/set_status', 'AppFlowController@set_status');
        Route::get('/approval_flow/{approval_flow}', 'AppFlowController@read');

        ### Approval Request
        Route::get('/approval_request/{approval_request_id}', 'AppFlowController@get_approval_request');
        Route::put('/approval_request/remarks', 'AppFlowController@add_edit_remarks');
        Route::put('/approval_request/attachments', 'AppFlowController@add_edit_attachments');

        ### Contribution Requests
        Route::post('/contribution_request/create', 'ContributionRequestController@create');
        Route::put('/contribution_request/{contribution_request}', 'ContributionRequestController@update');
        Route::get('/contribution_requests', 'ContributionRequestController@list_requests_for_approver');
        Route::put('/contribution_requests/set_status', 'ContributionRequestController@approve_reject_request');
        Route::get('/self_service/contribution_requests', 'ContributionRequestController@list_selfservice');

        ### Document Request
        Route::get('/document_requests', 'DocumentRequestController@list_requests_for_approver');
        Route::put('/document_requests/set_status', 'DocumentRequestController@approve_reject_request');
        Route::get('/self_service/document_requests', 'DocumentRequestController@list_employee_document_requests');
        Route::post('/self_service/document_request', 'DocumentRequestController@create');

        ### Edit Requests
        Route::put('/edit_requests/set_status', 'EditRequestController@approve_reject_request');
        Route::get('/edit_requests', 'EditRequestController@list_requests_for_approver');
        Route::get('/self_service/{user}/edit_requests', 'EditRequestController@list_user_edits');
        Route::get('/self_service/{user}/edit_requests_history', 'EditRequestController@list_user_edits_history');

        ### Time Off
        Route::get('/time_off_requests/time_off_balances', 'TimeOffRequestController@list_employees_time_off_balance');
        Route::put('/time_off_requests/set_status', 'TimeOffRequestController@approve_reject_request');
        Route::get('/time_off_requests/{employee_id}/history', 'TimeOffRequestController@list_employee_time_offs_history');
        Route::get('/time_off_requests/{employee_id}/upcoming', 'TimeOffRequestController@list_employee_time_offs_upcoming');
        Route::get('/time_off_requests/{employee_id}/pending', 'TimeOffRequestController@list_employee_time_offs_pending');
        Route::get('/time_off_requests/{employee_id}/balance', 'TimeOffRequestController@list_employee_time_offs_balance');
        Route::get('/time_off_requests/{employee_id}', 'TimeOffRequestController@list_employee_time_offs');
        Route::get('/time_off_requests', 'TimeOffRequestController@list_requests_for_approver');
        Route::get('/time_off_request/{time_off_request_id}', 'TimeOffRequestController@read_request_for_approver');
        Route::get('/time_off_balance/{time_off_balance_id}', 'TimeOffRequestController@read_time_off_balance');
        Route::post('/self_service/time_off_request', 'TimeOffRequestController@create');
        Route::get('/self_service/time_off_requests', 'TimeOffRequestController@list_employee_time_offs_self');
        Route::get('/self_service/time_off_request/{time_off_request}/pdf', 'TimeOffRequestController@read_pdf');
        Route::get('/self_service/time_off_requests/get_time_off_end_date', 'TimeOffRequestController@getTimeOffEndDate');
        Route::get('/self_service/time_off_requests/balance', 'TimeOffRequestController@list_employee_time_offs_balance_self');
        Route::get('/self_service/time_off_requests/history', 'TimeOffRequestController@list_employee_time_offs_history_self');
        Route::get('/self_service/time_off_requests/get_holidays_and_days_off', 'TimeOffRequestController@getHolidaysAndDaysOff');

        Route::put('/employee/{employee_id}/time_off_balance', 'EmployeeController@update_time_off_balances');

        ### Loan Request
        Route::post('/self_service/loans/loan_request', 'LoanRequestController@create');
        Route::get('/self_service/loan_requests/pending', 'LoanRequestController@list_employee_pending');
        Route::get('/self_service/loan_requests/history', 'LoanRequestController@list_employee_history');
        Route::get('/self_service/outstanding_balance', 'LoanRequestController@employee_outstanding_balance_self');
        Route::get('/loan_requests/summary', 'LoanRequestController@list_summary');
        Route::get('/loan_requests/history', 'LoanRequestController@list_history');
        Route::get('/loan_requests/{employee}/outstanding_balance', 'LoanRequestController@employee_outstanding_balance');
        Route::get('/loan_requests/{loan_request_id}', 'LoanRequestController@read');
        Route::get('/loan_requests', 'LoanRequestController@list_requests_for_approver');
        Route::put('/loan_requests/set_status', 'LoanRequestController@approve_reject_request');
        Route::get('/loan_request/loan_document_approvers', 'LoanRequestController@loan_active_list');

        ### OT Request
        Route::post('/authority_to_ot/create', 'AuthorityToOtController@create');
        Route::get('/authority_to_ots', 'AuthorityToOtController@list');
        Route::get('/ot_requests', 'OvertimeRequestController@list_requests_for_approver');
        Route::get('/ot_requests/{ot_request_id}', 'OvertimeRequestController@read');
        Route::put('/ot_requests/set_status', 'OvertimeRequestController@approve_reject_request');
        Route::get('/ot_requests/employee/{employee_id}', 'OvertimeRequestController@list_employee_ot');
        Route::post('/self_service/ot_request', 'OvertimeRequestController@create');
        Route::get('/self_service/ot_requests', 'OvertimeRequestController@list_employee_ot_self');
        Route::get('/self_service/ot_requests/history', 'OvertimeRequestController@list_employee_ot_history_self');
        Route::post('/self_service/ot_requests/submit', 'OvertimeRequestController@submit_overtime_requests');

        Route::get('/self_service/overtime_requests/get_available', 'OvertimeRequestController@get_available_overtimes_self');

        ### Employee Stubs
        Route::put('/employee_stub/{employee_id}', 'EmployeeStubController@save_default_employee_stub');
        Route::get('/employee_stubs', 'EmployeeStubController@list_employee_stubs');
        Route::get('/employee_stub/{employee_id}', 'EmployeeStubController@read_employee_stub');
        ### Payroll
        Route::get('/payroll/years', 'PayrollRunController@get_payroll_years');
        Route::post('/payroll/employee_list_payroll', 'PayrollRunController@employee_list_payroll');
        Route::get('/payrun/list', 'PayrollRunController@list');
        Route::post('/payrun/create', 'PayrollRunController@create');
        Route::put('/payrun/{payrun}', 'PayrollRunController@update');
        Route::get('/payrun/{payrun}', 'PayrollRunController@read');
        Route::put('/payrun/details/{payrun}', 'PayrollRunController@update_details');
        ### Payslip
        Route::get('/payslip/range', 'Reports\PayslipController@getRange');
        Route::get('/payslip/download', 'Reports\PayslipController@downloadPayslipByDate');

        Route::get('/self_service/payslip/range', 'Reports\PayslipController@getMyRange');
        Route::get('/self_service/payslip/download', 'Reports\PayslipController@downloadMyPayslip');

        ### Notifications
        Route::get('/notifications', 'NotificationController@list');
        Route::put('/notification/read/{notification}', 'NotificationController@read');

        ### Employee Cases
        Route::get('/employee/{employee}/cases', 'EmployeeController@view_employee_cases');
        Route::post('/employee/{employee_id}/case', 'EmployeeController@add_employee_case');
        Route::put('/employee/case/{employee_case}', 'EmployeeController@update_employee_case');
        Route::delete('/employee/case/{employee_case}', 'EmployeeController@delete_employee_case');

        ### Salary Grade
        Route::get('/salary_grade/{salary_grade}', 'SalaryController@get_active_salary_grade');

        ### Movement
        Route::post('/employee/{employee}/movement', 'EmployeeController@save_employee_movement');
        Route::post('/employee/{employee}/step_increment', 'EmployeeController@save_employee_step_increment');

        ### Attendance
        Route::get('/attendance/view_employees', 'AttendanceController@view_employees_attendance');
        Route::get('/attendance/view_employee', 'AttendanceController@view_employee_attendance');
        Route::get('/attendance/view_employee_self', 'AttendanceController@view_employee_attendance_self');
        Route::put('/attendance/approve_reject', 'AttendanceController@approve_reject_request');
        Route::post('/attendance/submit', 'AttendanceController@submit_dtr');
        Route::post('/attendance/edit', 'AttendanceController@edit_dtr');

        Route::get('/current_lwop/{employeeId}', 'CurrentLwopController@read');
        Route::put('/current_lwop/{employeeId}', 'CurrentLwopController@update');
    }
);
