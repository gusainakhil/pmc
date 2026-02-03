<!doctype html>
<html lang="en">
 <?php include"head.php" ?>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <?php include"header.php" ?>
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Equipment </h3></div>
              
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                }
                .container {
                    width: 90%;
                    margin: auto;
                }
                .buttons {
                    margin: 20px 0;
                }
                .buttons button {
                    padding: 10px 15px;
                    margin: 5px;
                    border: none;
                    cursor: pointer;
                    font-size: 16px;
                    border-radius: 5px;
                }
                .back-btn {
                    background-color: #00aaff;
                    color: white;
                }
                .go-btn {
                    background-color: #00c851;
                    color: white;
                }
                .set-target, .delivered {
                    background-color: #ffcc00;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 10px;
                    text-align: center;
                }
                th {
                    background-color: #ccc;
                }
            </style>
             <div class="container">
                <button class="back-btn">&#8592; Back</button>
                <div class="buttons">
                    <button class="go-btn">GO</button>
                    <button class="set-target">Set Target</button>
                    <button class="delivered">Delivered Equipments</button>
                </div>
                <h2>NORTH EASTERN RAILWAY</h2>
                <h3>EQUIPMENTS - TARGETS FOR 1 YEAR</h3>
                <table>
                    <tr>
                        <th>S.No</th>
                        <th>Description Of Material</th>
                        <th>Target</th>
                        <th>Units</th>
                        <th>Achieved</th>
                        <th>Deficit</th>
                        <th>Delivered 22/05/2022</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>Plastic Buckets - 15 Ltrs</td>
                        <td>12</td>
                        <td>NOS</td>
                        <td>100%</td>
                        <td>0</td>
                        <td>12</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Plastic Buckets - 5 Ltrs</td>
                        <td>12</td>
                        <td>NOS</td>
                        <td>100%</td>
                        <td>0</td>
                        <td>12</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Bombay Soft Brooms</td>
                        <td>240</td>
                        <td>NOS</td>
                        <td>100%</td>
                        <td>0</td>
                        <td>240</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Goa Brooms Hard (Coconut brooms)</td>
                        <td>500</td>
                        <td>NOS</td>
                        <td>100%</td>
                        <td>0</td>
                        <td>500</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Steel Scrubber</td>
                        <td>1500</td>
                        <td>NOS</td>
                        <td>100%</td>
                        <td>0</td>
                        <td>1500</td>
                    </tr>
                </table>
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->
      <!--begin::Footer-->
     <?php include"footer.php" ?>
      <!--end::Footer-->
    </div>
    
  </body>
  <!--end::Body-->
</html>
