<!doctype html>
<html lang="en">
  <!--begin::Head-->
 <?php include "head.php" ?>
  <style>

    .container {
        width: 90%;
        margin: auto;
        text-align: center;
    }
    .top-controls {
        display: flex;
        justify-content: center;
        padding: 10px;
        
    }
    button {
        background-color: #00aaff;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 5px;
    }
    select, input {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-left: 20px;
    }
    .go-btn {
        background-color: green;
    }
    .wage-rates {
        
        font-size: 16px;
        font-weight: bold;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #ddd;
        text-align: center;
    }
    input[type="text"] {
        width: 90%;
        padding: 0px;
    }
</style>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
<?php include "header.php" ?>
      <main class="app-main">
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <div class="container">
                <div class="top-controls">
                    <button>&#8592; Back</button>
                    <select>
                        <option>February</option>
                    </select>
                    <select>
                        <option>2025</option>
                    </select>
                    <select>
                        <option>Target View</option>
                    </select>
                    <button class="go-btn">Go</button>
                </div>
                <div class="wage-rates">
                    manager wage rate : 250 &nbsp;&nbsp; supervisor wage rates : 250 &nbsp;&nbsp; ESH wage rates : 250
                </div>
                <table>
                    <tr>
                        <th>Shift</th>
                        <th>Description</th>
                        <th>To be provided as per norms</th>
                    </tr>
                    <tr>
                        <td rowspan="3">General (Round the clock)</td>
                        <td>Project Manager</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Driver</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Pest Control</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td rowspan="2">Shift 1</td>
                        <td>Supervisor</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Housekeeper</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td rowspan="2">Shift 2</td>
                        <td>Supervisor</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Housekeeper</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td rowspan="2">Shift 3</td>
                        <td>Supervisor</td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Housekeeper</td>
                        <td><input type="text"></td>
                    </tr>
                </table>
      

          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>

     <?php include"footer.php" ?>
      <!--end::Footer-->
    </div>

  </body>
  <!--end::Body-->
</html>
