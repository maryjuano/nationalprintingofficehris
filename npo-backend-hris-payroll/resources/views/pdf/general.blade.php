<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>

      table {
        width: 100%;
        margin-top : 20px;
        border-collapse: collapse;
      }
      .label1 {
        color: #383838;
        text-transform: uppercase;
        font-size: 20px;
        font-weight : 600;
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
      .flex-center {
          align-items: center;
          display: flex;
          justify-content: center;
          text-align: center;
      }
      .position-ref {
        position: relative;
      }
      .image-header {
        width : 40px;
        height : 40px;
        margin-right : 350px;
      }
      .title {
        text-align: center;
        font-size: 18px;
        font-weight : 700;
        color: #383838;
        position : absolute;
        top : 30;
        left : 120;
      }
      .title2 {
        text-align: center;
        font-size: 16px;
        font-weight : 500;
        color: #383838;
        text-align : center;
        margin-top : 10px;
      }
      .divider {
        margin-top : 10px;
        width : 100%;
        border-bottom : 1px solid #383838;
      }
      .divider2 {
        position : absolute;
        width : 100%;
        top : 80;
        border-bottom : 1px solid gray;
      }
      .span-label {
        font-size: 13px;
        color: #525252;
      }
      .span-value {
        font-size: 13px;
        color: #1f1f1f;
      }
      .header-row {
        border-bottom : 3px solid gray;
        margin-bottom : 10px;
      }

      th {
        font-size: 12px;
        text-transform: uppercase;
        text-align : left;
        color: gray;
        height: 20px;
        border-bottom : 3px solid gray;
        padding-bottom: 8px;
      }
      td{
        width: 12%;
        font-size: 10px;
        color: #525252;
        height: 40px;
        border-bottom : 0.5px solid gray;
      }
    </style>

    <body>
      <div class="flex-center position-ref">
          <img src="{{ $image }}" class="image-header">
          <div class="label1 ">National Printing Office</div>
      </div>
      <div class="title">
          Summary of Lates, Absences and Undertimes
      </div>
      <div class="title2">
          Report Period : {{date('Y')}}
      </div>
      <div class="divider"> </div>
      <div class="divider2"> </div>
      <div style="margin-top : 10px;">
        <div>
            <span class="span-label">Generated by : </span>
            <span class="span-value">{{$general['formdetail']['generated_by']}}</span>
        </div>
        <div>
            <span class="span-label">Date Generated : </span>
            <span class="span-value">{{date('M. d, Y')}}</span>
        </div>
        <div>
            <span class="span-label">Divisions: </span>
            <span class="span-value">
                {{implode(',', $general['formdetail']['division'])}}
            </span>
        </div>
      </div>
      <table>
        <tr>
            <th style="width: 20%;">Name</td>
            <th style="width: 20%;">Division</td>
            <th style="width: 10%;text-align : center;">Absence</td>
            <th style="width: 10%;text-align : center;">Lates</td>
            <th style="width: 10%;text-align : center;">Undertime</td>
        </tr>
        @foreach ($general['summary'] as $item)
          <tr>
            <td>{{$item['name']}}</td>
            <td>{{$item['division']}}</td>
            <td style="width: 10%;text-align : center;" >{{$item['absences']}}</td>
            <td style="width: 10%;text-align : center;" >{{$item['lates']}}</td>
            <td style="width: 10%;text-align : center;" >{{$item['undertimes']}}</td>
          </tr>
        @endforeach
      </table>
  </body>
</html>

