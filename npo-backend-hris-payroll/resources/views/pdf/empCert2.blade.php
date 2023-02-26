<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>
      * {
        box-sizing: border-box;
        margin:  0;
        padding: 0;
      }
      @font-face {
        font-family: 'Poppins';
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url("https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap");
      }

      table {
        width: 100%;
        padding: 0px 90px;
        margin-top : 10px;
      }

      tr, td {
        padding : 1px;
      }

      body {
        padding : 172.8px 144px 144px 144px;
      }

      .certicate-title{
        font-family: 'Poppins', sans-serif;
        font-weight : 700;
        text-align: center;
        font-size: 19.5px;
        margin-bottom : 20px;
        border-bottom : 2px solid #000;
      }

      .to-whom-title {
        text-align: left;
        font-size: 16px;
        font-weight : 300;
        margin-bottom : 30px;
        color : #000;
      }

      .content {
        text-align: justify;
        font-size: 16px;
        color : #000;
      }


      .whatever {
        text-indent: 50px;
        margin-top : 30px;
        font-size: 16px;
        color : #000;
      }

      .signatory {
        margin-top: 70px;
        margin-left : 350px;
        font-weight : 400;
      }

      .officer-position {
        font-style: italic;
      }

    </style>
    @php
      function checkValue($data){
        return $data ?? '';
      };
    @endphp

    <body>
      <div class="content-data" >
        <p style="text-align: right;">
          <b>{{date('F d, Y')}}</b>
        </p><br/><br/>
        <p class="certicate-title">CERTIFICATE OF EMPLOYMENT AND COMPENSATION</p><br/>
        <p class="to-whom-title">TO WHOM MAY IT CONCERN:</p>
        <div>
          <p class="content">
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; THIS IS TO CERTIFY that <b>{{$employee->name}}</b> who has been originally
              appointed on <b>{{$employee->job_start}}</b> is a regular employee of the
              <b>National Printing Office (NPO)</b> and presently holding a permanent
              appointment with the position of <b>{{$employee->position_name}}</b> and receiving yearly
              remunerations as follows:
          </p>
        </div>
          <table>
            @foreach ($employee->salaries as $item)
              <tr>
                <td>{{$item['label']}}</td>
                <td style="text-align : right;" ><b>{{$loop->first ? 'Php ' : '  '}}{{$item['amount']}} </b></td>
              </tr>
            @endforeach
            <tr>
              <td></td>
              <td style="text-align : right;">-----------------</td>
            </tr>
            <tr>
              <td>T O T A L: . . . . . . . . . . </td>
              <td style="text-align : right;"> <b> Php {{$employee->total}} </b></td>
            </tr>
          </table>
          <div>
            <p class="whatever">
              This certification issued to <b>{{ $employee->gender === 1 ? 'Mr.' : 'Ms.' }} {{$employee->last_name}} </b>
              for whatever legal intent and purpose it may serve.
            </p>
          </div>
          <div class="signatory" style="text-align : center">
            <div> <b>{{ $signatory->signatories[0]['name'] ?? '<NAME>' }} </b> </div>
            <div class="officer-position"> {{ $signatory->signatories[0]['title'] ?? '<TITLE>' }} </div>
          </div>
      </div>
    </body>
</html>

