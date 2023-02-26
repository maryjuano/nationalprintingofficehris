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
        border :  0.01em solid #000;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
    }
    body {
        font-family: Helvetica, sans-serif;
    }
    .header {
        font-size: 12px;
        border :  0.01em solid #000;
        text-align: left;
        padding:5px 0px 5px 5px;
        font-weight : bold;
    }
    .values {
        font-size: 10px;
        border :  0.01em solid #000;
        text-align: left;
        padding: 3px 0px 3px 5px;
        word-wrap:break-word
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
                <strong>FORM A. </strong>
                    List of Employees with life and retirement premium <br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    remittance but without existing record in the GSIS Database.
                </td>
            </tr>
          </table>
          <table class="table" style="border-collapse: collapse; margin-top: 10px;">
            <tr>
                <th class="header" style="width: 5%"> Last Name </th>
                <th class="header" style="width: 5%"> First Name </th>
                <th class="header" style="width: 3%"> Suffix </th>
                <th class="header" style="width: 5%"> Middle Name </th>
                <th class="header" style="width: 5%"> Mailing Address / Zip Code </th>
                <th class="header" style="width: 5%"> Cellular Phone No.</th>
                <th class="header" style="width: 7%"> Email Address </th>
                <th class="header" style="width: 3%"> Sex </th>
                <th class="header" style="width: 3%"> Civil Status </th>
                <th class="header" style="width: 4%"> Date of Birth </th>
                <th class="header" style="width: 4%"> Place of Birth </th>
                <th class="header" style="width: 3%"> Basic Month Salary </th>
                <th class="header" style="width: 3%"> Effective Date </th>
                <th class="header" style="width: 4%"> Position </th>
                <th class="header" style="width: 5%"> Status of Employment </th>
            </tr>
			@foreach ($employees as $item)
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

                $employee_is_temporary = $item->employment_and_compensation->employee_type->id === 2 ||
                    $item->employment_and_compensation->employee_type->id === 5;
                $salary_with_step = $employee_is_temporary ?
                    $item->employment_and_compensation->salary_rate :
                    $item->employment_and_compensation->salary->step[$item->employment_and_compensation->step_increment ?? 0];

                $position_name = $item->employment_and_compensation->position->position_name ??
                    $item->employment_and_compensation->position_name;

                $effectivity_date = \Carbon\Carbon::createFromFormat('Y-m-d', $item->employment_and_compensation->date_hired)->format('m/d/Y');
			  @endphp
              <tr>
                <td class="values"> {{$item->personal_information->last_name}} </td>
                <td class="values"> {{$item->personal_information->first_name}} </td>
                <td class="values"> {{$item->personal_information->name_extension}} </td>
                <td class="values"> {{$item->personal_information->middle_name}} </td>
                <td class="values"> {{$item->personal_information->street}} </td>
                <td class="values"> {{$item->personal_information->mobile_number[0] ?? ''}}</td>
                <td class="values"> {{$item->personal_information->email_address}} </td>
                <td class="values"> {{$item->personal_information->gender===1?'Male':'Female'}} </td>
				<td class="values"> {{$map($item->personal_information->civil_status)}} </td>
                <td class="values"> {{date_format(date_create($item->personal_information->date_of_birth),'m/d/Y')}} </td>
                <td class="values"> {{$item->personal_information->place_of_birth}} </td>
                <td class="values"> {{number_format($salary_with_step, 2, '.',',')}} </td>
				<td class="values"> {{$effectivity_date}} </td>
                <td class="values"> {{$position_name}} </td>
				<td class="values"> {{$item->employment_and_compensation->employee_type->employee_type_name}} </td>
              </tr>
            @endforeach
          </table>
          <div style="font-size: 12px; margin-top: 30px">
              If any or all if the listed employees listed
              above are new Employees in that Agency, please provide the information in the appropriate column.
          </div>
          <div style="font-size: 12px; margin-top: 20px">
              NOTE : No need to attach supporting document such as :1) IMI 2) Birth Certificate etc.
          </div>
      </div>
  </body>
</html>

