<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>

    table {
        border-left: 0.01em solid #ccc;
        border-right:  0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom:  0.01em solid #ccc;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        padding: 8
    }

    .span-small {
        font-size: 11px
    }

    .span-medium {
        font-size: 13px
    }

    .span-normal {
        font-size: 14px
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
    </style>

    <body>
            <div style="display: flex">
             <p class="span-small"> GSIS D 202-02 (Revised, 1989)</p>
             <p class="span-small" style="text-align: right">(Follow Instructions at the Back) </p>
            </div>
            <p style="text-align: center; font-size: 20px">S E R V I C E &nbsp; R E C O R D <br/><span class="span-small">(To be Accomplished by Employer) </span></p>

            <p class="span-medium">Name: <span style="text-decoration: underline;"> &nbsp; {{$vals['employee_details']['last_name']}} &nbsp; &nbsp; &nbsp; {{$vals['employee_details']['first_name']}} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {{$vals['employee_details']['middle_name']}} &nbsp; &nbsp; &nbsp;</span>
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            <span style="font-size: 9px">(If married woman, give also full name & other surname used)  </span>
            <br/>
            <span style="font-size: 9px"><i> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (Surname) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (Given)&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;(Middle Name)</i></span>
            </p>

            <p class="span-medium">Birth: <span style="text-decoration: underline;"> &nbsp; &nbsp; &nbsp; {{$vals['employee_details']['date_of_birth']}} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {{$vals['employee_details']['place_of_birth']}}  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp; &nbsp; </span>
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            <span style="font-size: 9px"> (Date herein should be checked from birth or baptismal certificate or </span>
            <br/>
            <span style="font-size: 9px"><i> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (Date) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;(Place)</i></span>
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  <span style="font-size: 9px"> some other official documents) </span>
            </p>

            <p class="span-medium" style="text-align: center; margin-left: 100px; margin-right: 100px">This is to certify that the employee named hereinabove actually rendered services in this Office or Offices indicated below each lin of which is supported by appointment and other papers actually issued and approved by the authorities concerned:</p>
            <div>


            <table class="table" style="border-collapse: collapse; text-align: center; font-size: 12px" border=1>
                <tr>
                    <td style="width: 20%" colspan="2">SERVICE <br/> (Inclusive Dates)</td>
                    <td style="width: 25%" colspan="3">RECORD OF APPOINMENT</td>
                    <td style="width: 10%" rowspan="2">OFFICE Station/ Place BRANCH </td>
                    <td style="width: 5%" rowspan="2">BRANCH <br/> (3) </td>
                    <td style="width: 8%" rowspan="2">LV / ABS WO / PAY (4)</td>
                    <td style="width: 10%" colspan="3"> SEPARATION (5)</td>
                </tr>
                <tr>
                    <td style="width: 8%; font-size: 10px">FROM </td>
                    <td style="width: 8%; font-size: 10px">TO </td>
                    <td style="width: 10%; font-size: 10px"> Designation </td>
                    <td style="width: 10%; font-size: 10px" >Status <br/> (1)</td>
                    <td style="width: 10%; font-size: 10px">Salary <br/> (2)</td>
                    <td style="width: 10%; font-size: 10px">Date </td>
                    <td style="width: 10%; font-size: 10px">Cause </td>
                    <td style="width: 10%; font-size: 10px">Amount <br/> Received </td>
                </tr>
                @foreach($vals['employee_position_history'] as $value)

                <tr>
                    <td style="width: 8%; font-size: 10px; border-top: none;border-bottom: none;"> &nbsp; {{$value['start_date']}} </td>
                    <td style="width: 8%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['end_date']}} </td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['position']}} </td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;" > {{$value['status']}}</td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['salary']}} </td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['section']}}</td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['branch']}}</td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['start_LWOP'] . '-' . $value['end_LWOP']}}</td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;">{{$value['separation_date']}} </td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['separation_cause']}} </td>
                    <td style="width: 10%; font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['separation_amount']}} </td>
                </tr>
                @endforeach
            </table>
            </div>
            <p class="span-medium" style="text-align: center; margin-left: 100px; margin-right: 100px">Issued in compliance with Executive Order No. 54 dated August 10, 1954, and in accordance with Circular No. 58, dated August 19, 1954 of the System.</p>
            <br/>
            <br/>

            <p class="span-small"> <span style="text-decoration: underline;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp;  {{$vals['generation_date']}} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp;  </span> <br/>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  Date &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; CERTIFIED CORRECT</p>
            <p class="span-small" style="text-align: right"> ______________________________ <br/>  Signature &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</p>
            <p class="span-small" style="text-align: right"> ______________________________ <br/>  Printed Name  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </p>
            <p class="span-small" style="text-align: right"> ______________________________ <br/>  Designation &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</p>
            <div>



    </body>
</html>

