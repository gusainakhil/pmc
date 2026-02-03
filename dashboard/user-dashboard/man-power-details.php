<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <?php include"head.php" ?>
  <!--end::Head-->
  <!--begin::Body-->
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
              <div class="col-sm-6"><h3 class="mb-0">Man power Details </h3></div>
              
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
            <div class="row g-4">
              <div class="col-md-12">
                <div class="card card-info card-outline mb-4">
                  <form class="needs-validation" novalidate>
                    <!--begin::Body-->
                    <div class="card-body">
                      <!--begin::Row-->
                      <div class="row g-3">
                       
                        <div class="col-md-2">
                          <label for="validationCustom04" class="form-label">Select station</label>
                          <select class="form-select" id="validationCustom04" required>
                            <option selected disabled value="">Choose...</option>
                            <option>...</option>
                          </select>
                         
                        </div>
                        <div class="col-md-2">
                          <label for="validationCustom04" class="form-label">Daily surpise Report</label>
                          <select class="form-select" required>
                            <option selected disabled value="">Choose...</option>
                            <option>...</option>
                          </select>
                          
                        </div>
                        <div class="col-md-2">
                          <label for="validationCustom04" class="form-label">Select Auditior</label>
                          <select class="form-select"  required>
                            <option selected disabled value="">Choose...</option>
                            <option>...</option>
                          </select>
                         
                        </div>
                        <div class="col-md-2">
                          <label for="validationCustom04" class="form-label">Select station</label>
                          <select class="form-select" required>
                            <option selected disabled value="">Choose...</option>
                            <option>...</option>
                          </select>
                          
                        </div>
                        <div class="col-md-2">
                          <label for="validationCustom04" class="form-label">From</label>
                          <input class="form-select"  type="date" name="submit ">
                          
                        </div>

                        <div class="col-md-2">
                          <label for="validationCustom04" class="form-label">From</label>
                          <input class="form-select"  type="date" name="submit ">
                        </div>

                        <div class="card-footer">
                          <a class="btn btn-info" href="billing-printing-ui.html">GO</a>
                          <a class="btn btn-success" href="man-powertarget.html">Man power Target</a>
                        </div>
                        
                    
                        <!--end::Col-->
                      </div>
                      <!--end::Row-->
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                   
                    <!--end::Footer-->
                  </form>
                  
                </div>
                <!--end::Form Validation-->
              </div>
              <!--end::Col-->
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
