<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <!-- <link rel="stylesheet" href="{{ asset('css/app.css')  }}"> -->
    </head>
    <style>
        @font-face {
            font-family: 'Helvetica';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
        }
        * {
            box-sizing: border-box;
            margin:  0;
            padding: 0;
        }
        body {
            font-family: Helvetica, sans-serif;
            padding : 50px 20px;
        }
        .table {
            border: 0.01em solid #000;
            width: 100%;
            box-shadow: 1px 1px 10px solid #ccc;
        }
        .header {
            font-size: 12px;
            border: 0.01em solid #000;
            text-align: center;
            padding: 10px 0px 5px 5px;
            font-weight :bold;
        }
        .values {
            font-size: 9px;
            border: 0.01em solid #000;
            text-align: left;
            padding: 5px 0px 5px 5px;
            word-wrap:break-word
        }
        thead:before, thead:after { display: none; }
        tbody:before, tbody:after { display: none; }
    </style>
        <body>
            <div>
                <table width="100%">
                    <tr>
                        <td style="width: 100%; font-size: 11px">
                            Agency Name: NATIONAL PRINTING OFFICE
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
                        <strong>FORM E. </strong> List of Employees with changes / correction in their Personal Data.
                        </td>
                    </tr>
                </table>
                <table class="table" style="border-collapse: collapse; margin-top : 10px;">
                    <thead>
                        <tr>
                            <th rowspan="1" class="header" style="width: 5%" > Member BP Number </th>
                            <th colspan="2" class="header" style="width: 8%" >  Last Name  </th>
                            <th colspan="2" class="header" style="width: 8%" > First Name </th>
                            <th colspan="2" class="header" style="width: 8%" > Suffix </th>
                            <th colspan="2" class="header" style="width: 8%" > Middle Name </th>
                            <th colspan="2" class="header"  style="width: 8%"> Mailing Addres/Zip Code </th>
                            <th colspan="2" class="header" style="width: 8%" > Cellular Phone No. </th>
                            <th colspan="2" class="header" style="width: 8%" > Email Address </th>
                            <th colspan="2" class="header" style="width: 8%" > Civil Status </th>
                            <th colspan="2" class="header" style="width: 8%" > Date of Birth </th>
                            <th colspan="2" class="header" style="width: 8%" > Place of Birth </th>
                            <th colspan="2" class="header" style="width: 8%" > Position </th>
                            <th colspan="2" class="header" style="width: 8%" > Status of Employee </th>
                        </tr>
                        <tr>
                            <th class="header" >  </th>
                            @for($i=0; $i < 12; $i++)
                                <td class="header" > From </td>
                                <td class="header" > To </td>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                    @php
                        clock($data);
                    @endphp
                    @foreach ($data as $item)
                        @php
                            $map=function($val)
                            {
                                switch($val){
                                    case 1:
                                        return 'Single';
                                    case 2:
                                        return 'Married';
                                    case 3:
                                        return 'Divorced';
                                    case 4:
                                        return 'Seperated';
                                    case 5:
                                        return 'Widowed';
                                }
                            };
                            $gsisNo='000000';
                            $oldLastName = $item->edit_history_last_name?$item->edit_history_last_name->old:'';
                            $newLastName = $item->edit_history_last_name?$item->edit_history_last_name->new:'';
                            $oldFirstName = $item->edit_history_first_name?$item->edit_history_first_name->old:'';
                            $newFirstName = $item->edit_history_first_name?$item->edit_history_first_name->new:'';
                            $oldSuffix = $item->edit_history_name_extension?$item->edit_history_name_extension->old:'';
                            $newSuffix = $item->edit_history_name_extension?$item->edit_history_name_extension->new:'';
                            $oldMiddle = $item->edit_history_middle_name?$item->edit_history_middle_name->old:'';
                            $newMiddle = $item->edit_history_middle_name?$item->edit_history_middle_name->new:'';
                            $oldZipCode = $item->edit_history_zip_code?$item->edit_history_zip_code->old:'';
                            $newZipCode = $item->edit_history_zip_code?$item->edit_history_zip_code->new:'';
                            $oldPhone = $item->edit_history_mobile_number?$item->edit_history_mobile_number->old:'';
                            $newPhone = $item->edit_history_mobile_number?$item->edit_history_mobile_number->new:'';
                            $oldEmail = $item->edit_history_email_address?$item->edit_history_email_address->old:'';
                            $newEmail = $item->edit_history_email_address?$item->edit_history_email_address->new:'';
                            $oldCivil = $item->edit_history_civil_status? $map($item->edit_history_civil_status->old):'';
                            $newCivil = $item->edit_history_civil_status? $map($item->edit_history_civil_status->new):'';
                            $oldDOB = $item->edit_history_date_of_birth? date_format(date_create($item->edit_history_date_of_birth->old),"Y-m-d") :'';
                            $newDOB = $item->edit_history_date_of_birth? date_format(date_create($item->edit_history_date_of_birth->new),"Y-m-d"):'';
                            $oldPOB = $item->edit_history_place_of_birth?$item->edit_history_place_of_birth->old:'';
                            $newPOB = $item->edit_history_place_of_birth?$item->edit_history_place_of_birth->new:'';
                            $oldPos = $item->edit_history_position_old?$item->edit_history_position_old->position_name:'';
                            $newPos = $item->edit_history_position_new?$item->edit_history_position_new->position_name:'';
                            $oldEmpType = $item->edit_history_employee_type_old?$item->edit_history_employee_type_old->employee_type_name:'';
                            $newEmpType = $item->edit_history_employee_type_new?$item->edit_history_employee_type_new->employee_type_name:'';
                        @endphp
                        <tr>
                            <td class="values"> {{$item->employment_and_compensation->gsis_number}} </td>
                            <td class="values">{{$oldLastName}}</td>
                            <td class="values">{{$newLastName}}</td>
                            <td class="values">{{$oldFirstName}}</td>
                            <td class="values">{{$newFirstName}}</td>
                            <td class="values">{{$oldSuffix}}</td>
                            <td class="values">{{$newSuffix}}</td>
                            <td class="values">{{$oldMiddle}}</td>
                            <td class="values">{{$newMiddle}}</td>
                            <td class="values">{{$oldZipCode}}</td>
                            <td class="values">{{$newZipCode}}</td>
                            <td class="values">{{$oldPhone}}</td>
                            <td class="values">{{$newPhone}}</td>
                            <td class="values">{{$oldEmail}}</td>
                            <td class="values">{{$newEmail}}</td>
                            <td class="values">{{$oldCivil}}</td>
                            <td class="values">{{$newCivil}}</td>
                            <td class="values">{{$oldDOB}}</td>
                            <td class="values">{{$newDOB}}</td>
                            <td class="values">{{$oldPOB}}</td>
                            <td class="values">{{$newPOB}}</td>
                            <td class="values">{{$oldPos}}</td>
                            <td class="values">{{$newPos}}</td>
                            <td class="values">{{$oldEmpType}}</td>
                            <td class="values">{{$newEmpType}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div>
                    <div style="font-size: 12px; margin-top: 50px; text-align:left; margin-left: 100px;">
                        *for Change of date of birth please attach scanned copy of Original PSA authenticated Birth Certificate
                    </div>
                    <div style="font-size: 12px; margin-top: 10px; text-align:left; margin-left: 100px;">
                        *for Change of Last Name (to Married Name, for females) or Status (from 'Single' to 'Married') please attach scanned copy of Original PSA authenticated Birth Certificate
                    </div>
                    <div style="font-size: 12px; text-align:right; margin-top: 50px; margin-right: 50px;">Issue No.1, Rev No.0, (16 August 2016), FM-GSIS-OPS-UMR-05</div>
                </div>
            </div>
        </body>
    </html>

