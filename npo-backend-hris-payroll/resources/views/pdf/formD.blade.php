<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>
        @font-face {
            font-family: 'Helvetica';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
        }
        .table {
            border: 0.01em solid #000;
            width: 100%;
            box-shadow: 1px 1px 10px solid #ccc;
        }
        body {
            font-family: Helvetica, sans-serif;
        }
        .header {
            font-size: 12px;
            border: 0.01em solid #000;
            text-align: center;
            padding: 10px 0px 5px 5px;
            font-weight :bold;
        }
        .values {
            font-size: 10px;
            border: 0.01em solid #000;
            text-align: left;
            padding: 5px 0px 5px 5px;
            word-wrap:break-word
        }
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
                        <strong>FORM D. </strong> List of Employees with no premium remittances for 2 consecutive months.
                        </td>
                    </tr>
                </table>
                <table class="table" style="border-collapse: collapse; margin-top : 10px;">
                    <tr>
                        <th class="header" style="width: 10%" > Member BP Number </th>
                        <th class="header" style="width: 8%" > Last Name </th>
                        <th class="header" style="width: 8%" > First Name </th>
                        <th class="header" style="width: 4%" > Suffix </th>
                        <th class="header" style="width: 8%" > MI </th>
                        <th class="header" style="width: 10%"> Reason </th>
                        <th class="header" style="width: 8%" > Effectivity Date </th>
                        <th class="header" style="width: 10%"> Remarks </th>
                    </tr>
                    @foreach($vals['data'] as $row => $value)
                        <tr>
                            <td class="values" > {{ $value->employment_and_compensation->gsis_number != '0' ? $value->employment_and_compensation->gsis_number : ''}} </td>
                            <td class="values" > {{ $value->personal_information->last_name }} </td>
                            <td class="values" > {{ $value->personal_information->first_name }} </td>
                            <td class="values" > {{ $value->personal_information->name_extension }}  </td>
                            <td class="values" >  {{ $value->personal_information->middle_name }} </td>
                            <td class="values" > {{ $value->offboard->reason }} </td>
                            <td class="values" >  {{date_format(date_create($value->offboard->effectivity),'M. d, Y')}} </td>
                            <td class="values" > {{ $value->offboard->remarks ?? 'N/A' }} </td>
                        </tr>
                    @endforeach
                </table>
                <div>
                    <div style="font-size: 12px; margin-top: 50px; text-align:center; ">
                        1. Reason : please specify whether transferred to the office / resigned / deceased / dismissed / laid off / <br/>
                            end of term/ end of contract /drop from the rolls / suspended / on live without pay etc.
                    </div>
                    <div style="font-size: 12px; margin-top: 10px; text-align:center; margin-right: 70px;">
                        2. Remarks : in case transferred to the office, please indicate the new office (if available).
                    </div>
                    <div style="font-size: 12px; text-align:right; margin-top: 50px; margin-right: 50px;">Issue No.1, Rev No.0, (16 August 2016), FM-GSIS-OPS-UMR-04</div>
                </div>
            </div>
        </body>
    </html>

