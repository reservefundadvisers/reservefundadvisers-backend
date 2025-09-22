<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Cost of Capital Tool</title>


    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- W3.CSS -->
    <link rel="stylesheet" href="css/w3.css">
    
    <!-- UI -->
	<link href="css/jquery-ui.css" rel="stylesheet">
	<link href="css/ui/slider.css" rel="stylesheet">
    
    
</head>

<body id="page-top" onload="init('#forcaster'); cash_flow('#cash_flow');">

    <!-- Page Wrapper -->
    <div id="wrapper">


        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                
                <!-- Begin Page Content -->
                <div class="container-fluid mt-5" align="center">

                    <div class="w-100 w3-red overflow-auto">
                        <div class="card shadow w-75" style=""  align="left">

                            <div class="card-header w3-green">
                                Cost of Capital Tool
                            </div>
                            
                            <div class="card-body py-5" id="forcaster">
                                <div class="w-100 overflow-auto">
                                    <div class="btn btn-sm w3-green mb-2 float-right">Cash Flow</div>
                                </div>
                                <div id="chart" class="w-100" style="height:300px;"></div>
                            <div>
                            
                            
                        </div>

                        <div class="slider" data-range="true" data-min="20" data-max="75" data-values="[25, 75]"></div>

                    </div>

                    


                    <div class="modal fade" id="cash_flow_modal" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
						<div class="modal-dialog modal-xl modal-dialog-centered" role="document"> 
						<div class="modal-content shadow"> 
							<div class="modal-header pb-2"> 
							<h5 class="modal-title" style="font-size:20px;" qompta-tr="spec">Cash Flow</h5> 
							<button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
								<span aria-hidden="true">×</span> 
							</button> 
							</div> 

							<form class="needs-validation" novalidate> 
							<div class="modal-body pt-0">  
							<!-- MODAL BODY END -->    
								<div class="row pt-4" id="cash_flow">
									<div class="col-md-6 col-lg-4">
										<table class="table table-sm table-bordered">
											<thead class="w3-green">
												<tr><th>Period</th><th>Cash Flow</th></tr>
											</thead>
											<tbody>
												<tr><td class="text-center">1</td><td class="text-right">-100</td></tr>
											</tbody>
										</table>
									</div>
									<div class="col-md-6 col-lg-8">
										<div id="cash_flow_chart" class="w-100" style="height:300px;"></div>
									</div>
								</div>
					
							
							</div> 

							<div class="modal-footer"> 
								
							</div> 
							</form> 

						</div> 
						</div> 
					</div>


                    

                </div>
                <!-- /.container-fluid -->

            </div>  
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white mt-3">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; 2021</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    
    

    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery-ui.js"></script>
    <script type="text/javascript" src="js/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script type="text/javascript" src="js/utils.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="js/ui.js?v=<?php echo time(); ?>"></script>
    
    <!-- E-Charts -->
    <script src="js/echarts-5.2.2/echarts.min.js"></script>
	
    <script type="text/javascript" src="js/app/app.js"></script>
    
	

</body>

</html>