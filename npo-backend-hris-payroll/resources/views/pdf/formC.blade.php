<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>
        .table {
            border :  0.01em solid #000;
            width: 100%;
            box-shadow: 1px 1px 10px solid #ccc;
        }
        @font-face {
            font-family: 'Helvetica';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
        }
        body {
            font-family: Helvetica, sans-serif;
        }
        .header {
            font-size: 12px;
            border :  0.01em solid #000;
            text-align: center;
            padding: 10px 0px;
        }
        .values {
            font-size: 12px;
            border :  0.01em solid #000;
            text-align: center;
            padding:10px 0px;
        }
    </style>

        <body>
            <div>

            <table width="100%">
                <tr>
                    <td style="width: 100%; font-size: 11px">
                        Agency Name: National Printing Office
                        <br />
                        Agency BP Number: ---
                    </td>
                </tr>

                <tr>
                    <td style="width: 100%; font-size: 14px;">
                    <br/>
                      <strong>FOR AGENCY REMITTANCE ADVICE</strong>
                    <br/>
                    <br/>
                    <br/>
                    <strong>FORM C. </strong> List of employees with salary adjustments for confirmation as <br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    to correct amount of monthly salary and effictiviy date supplied below.
                    </td>
                </tr>
            </table>

            <div style="font-size: 10px; text-align:right;">Agency Name (with agenct BP no.)</div>

            <table class="table" style="border-collapse: collapse;">
                <tr>
                    <td class="header" style="width: 150px"> Member BP Number </td>
                    <td class="header" style="width: 110px"> Last Name </td>
                    <td class="header" style="width: 110px"> First Name </td>
                    <td class="header" style="width: 110px"> Suffix </td>
                    <td class="header" style="width: 70px"> MI </td>
                    <td class="header" style="width: 70px"> Salary </td>
                    <td class="header" style="width: 110px"> Effectivity Date </td>
                    <td class="header" style="width: 150px"> Position </td>
                    <td class="header" style="width: 70px"> Employment status </td>
                </tr>
            </table>

            <table class="table" style="border-collapse: collapse;">
            @foreach($vals['data'] as $row => $value)
                @php
                    $employee_is_temporary = $value->employment_and_compensation->employee_type->id === 2 ||
                        $value->employment_and_compensation->employee_type->id === 5;
                    $salary_with_step = $employee_is_temporary ?
                        $value->employment_and_compensation->salary_rate :
                        $value->employment_and_compensation->salary->step[$value->employment_and_compensation->step_increment ?? 0];

                    $position_name = $value->employment_and_compensation->position->position_name ??
                        $value->employment_and_compensation->position_name;

                    $effectivity_date = '';
                    if ($value->employment_and_compensation->job_info_effectivity_date) {
                        $effectivity_date = \Carbon\Carbon::createFromFormat('Y-m-d', $value->employment_and_compensation->job_info_effectivity_date)->format('m/d/Y');
                    } else if ($value->employment_and_compensation->period_of_service_start) {
                        $effectivity_date = \Carbon\Carbon::createFromFormat('Y-m-d', $value->employment_and_compensation->period_of_service_start)->format('m/d/Y');
                    }

                    $company = $value->work_experience->first() ? $value->work_experience->first()->company : '';
                @endphp
                    <tr>
                        <td class="values" style="width: 150px"> {{ $value->employment_and_compensation->gsis_number }} </td>
                        <td class="values" style="width: 110px"> {{ $value->personal_information->last_name }} </td>
                        <td class="values" style="width: 110px"> {{ $value->personal_information->first_name }} </td>
                        <td class="values" style="width: 110px"> {{ $value->personal_information->name_extension }}  </td>
                        <td class="values" style="width: 70px">  {{ $value->personal_information->middle_name }} </td>
                        <td class="values" style="width: 70px"> {{ number_format($salary_with_step, 2, '.',',') }} </td>
                        <td class="values" style="width: 110px">  {{ $effectivity_date }} </td>
                        <td class="values" style="width: 150px"> {{ $position_name }}  </td>
                        <td class="values" style="width: 70px">  {{ $value->employment_and_compensation->employee_type->employee_type_name }} </td>
                    </tr>
            @endforeach
            </table>

            <div style="font-size: 15px; margin-top: 50px">Note: No need to attached the Notice of Salary Adjustments (NOSA) and Notice if Salary Increase (NOSI).</div>
            <div style="font-size: 12px; text-align:right; margin-top: 40px">Issue No.1, Rev No.0, (16 August 2016), FM-GSIS-OPS-UMR-02</div>

            </div>
        </body>
    </html>

