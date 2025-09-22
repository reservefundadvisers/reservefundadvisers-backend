<?php

include_once('app/init.php');
include_once('app/auth.php');
include_once('app/global_func.php');

auth_handle_login();
auth_handle_user();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Simulation - <?php echo $app_name; ?></title>

    <!-- Header -->        
    <?php echo print_header(['simulation']); ?>
    <!-- End of Header -->

    

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
            <?php echo print_sidebar(); ?>
        <!-- End Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                    <?php echo print_topbar(); ?>
                <!-- End of Topbar -->






                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Simulation</h1>
                    </div>

                    <template id="model_select_template">
                        <div class="container w-100 m-0 p-2 pb-3" style="min-height:300px;">
                            <div class="container w-100 d-none">
                                <div class="w-100 h4 pb-3 mb-2 text-center border-bottom border-dark">Please choose an <b class="text-primary">Association</b></div>
                                <div class="row m-0 p-0 mt-3 model-select-associations"></div>
                            </div>
                            <div class="container w-100 d-none">
                                <label class="btn btn-success mt-2 ml-2 model-select-change-association"><i class="fa fa-chevron-left mr-2"></i>Change Association</label>
                                <div class="w-100 h4 pb-3 mb-2 text-center border-bottom border-dark">
                                    Please choose a <b class="text-primary">Model</b>
                                </div>
                                <div class="row m-0 p-0 mt-3 model-select-models"></div>
                            </div>
                        </div>

                    </template>


                    <div class="card shadow mb-4 blank"  id="simulation">
                        <div class="card-header pt-3 pb-0">
                            
                            <div class="w-100 overflow-auto text-center">
                                
                                <label class="btn btn-success float-md-left simulation-change-model">Change Model</label>

                                <label class="btn btn-info float-md-right ml-2 tour-start" data-tour-name="main">Start Guided Tour</label>

                                <div class="d-md-inline invisible h5">
                                    Model: <span class="simulation-curr-model text-primary font-weight-bold mx-2"></span>                                
                                    Association: <span class="simulation-curr-association text-primary font-weight-bold mx-2"></span>
                                </div>
                            
                                <!-- <select class="app-input associations" data-field="Association" data-style="width:250px;" data-classes="d-inline-block mr-3 mr-xs-0 w-xs-100">
                                    <option value="" disabled selected>Loading...</option>
                                </select>

                                <select class="app-input models" data-field="Model" data-style="width:250px;" data-classes="d-inline-block w-xs-100">
                                    <option value="" disabled selected>Loading...</option>
                                </select> -->
                                        
                                <div class="d-inline-block float-right text-right m-1 mt-xs-3 w-xs-100">
                                    <div class="action-group">
                                        <!-- <button class="d-nones btn btn-sm btn-success shadow-sm  " onclick="simulation.run()"><i
                                                class="fas  fa-sync btn-icon text-white" ></i> <span class="btn-text" >Refresh</span></button> -->
                                    
                                    </div>
                                            
                                </div>
                                        
                            </div>
                            
                            
                            <div class="w-100 border-top border-dark mt-3"></div>

                            <div class="w-100 pt-2">
                                
                                <div class="float-right w-xs-100 w-50 px-xs-3">
                                    
                                                
                                </div>

                            </div> 


                        </div>


                        <template>
                        

                        </template>



                        <div class="card-body position-relative">

                        

                            <div class="row" >
                                <div class="col-12 mb-3 pb-1">
                                    <div class="row border-bottom border-dark model-info invisible  w3-white">
                                    
                                        
                                        <div class="col-md-6 text-left mb-3 pb-1 d-inline-block">
                                            <label class="btn btn-sm btn-danger reset-simulation" data-action="all"  data-tour-name="main" data-tour-step="0" data-tour-first="true">Start Over</label>
                                            <label class="btn btn-sm btn-danger reset-simulation ml-2" data-action="current" data-tour-name="main" data-tour-step="1">Reset Current Data</label>
                                            

                                             <!-- <label class="btn btn-sm w3-indigo model-edit" data-action="model">Edit Model</label>
                                            <label class="btn btn-sm w3-green model-edit" data-action="items">Edit Model Items</label> -->
                                        </div>
                         
                                        <div class="col-md-6 mb-2 pb-2">
                                            <button class="btn btn-sm btn-primary float-right" style="z-index:99;" onclick="simulation.showTable();"   data-tour-name="main" data-tour-step="3" >Show Details</button>
                                            <button class="btn btn-sm w3-deep-purple float-right mr-2" style=" z-index:99;" onclick="simulation.showGraph(0);" data-tour-name="main" data-tour-step="2">Show Graph</button> 
                                                    
                                        </div>
                                        

                                    </div>
                                </div>
                              


                            </div>


                            
                            <div class="row m-0 p-0 invisible position-relative" id="deficit_box_template">
                                
                                <div class="col-12 mb-1">
                                    
                                    <label class="btn btn-sm w3-dark-gray float-left mb-0" onclick="$('.simulation-general-rules').fadeToggle('fast');"  data-tour-name="main" data-tour-step="4">Simulation Settings</label>

                                    <div class="row m-0 p-0 pt-1 ml-3 float-left w3-purple rounded px-2 "  data-tour-name="main" data-tour-step="5">
                                        <div class="col p-0 m-0 pt-1"><i class="fa fa-rocket mr-2"></i>Boost With LTIM</div>    
                                        <div class="col-auto p-0 m-0 pl-3">
                                            <label class="checkbox p-0 m-0 ">
                                                <label class="switch switch-success border-white">
                                                    <input type="checkbox" class="simulation-boost-ltim">
                                                    <span class="switch-slider rounded-circle" style="" ></span>
                                                </label>
                                            </label>
                                        </div>
                                    </div>

                                    <label class="btn btn-sm w3-green float-left mb-0 ml-2 simulation-compare"  data-tour-name="main" data-tour-step="6" >Compare Results</label>
                                    
                                    <div class="float-right mx-2 mt-3 mt-md-0 ">
                                        <label class="btn btn-sm btn-danger reset invisible" data-tour-name="main" data-tour-step="7" data-tour-remove-classes="invisible"><i class="fa fa-sync mr-2"></i> Reset Year Data</label>
                                    </div>

                                
                                    <div class="d-lg-none float-right mt-3 mt-md-0  ">
                                        <label class="btn btn-sm w3-deep-purple simulation-fa-toggle" data-tour-name="main" data-tour-step="8" data-tour-hide-size="lg">Choose Year</label>
                                    </div>

                                </div>

                                <div class="col-12 mb-2 py-3 border border-dark w3-round-xlarge shadow simulation-general-rules" style="display:none;">
                                    <div class="w-100 m-0 p-0 mb-2">
                                        <strong class="h5 font-weight-bold">Simulation Settings</strong>
                                    </div>
                                    <div class="row m-0 p-0 pb-2">
                                        
                                        <div class="col-8 col-md-3 col-lg-3 pt-2 mb-2  ltim-option">
                                            <span class=" font-weight-bold">Default % Allocated To LTIM From Available to Invest </span>
                                        </div>
                                        <div class="col-4 col-md-3 col-lg-1 pt-2 mb-2 px-0 mx-0 text-right border-right-md border-dark pr-2  ltim-option">
                                            <input type="number" class="app-input simulation-rule percent w-75 float-left" data-key="ltim_perc" min="0" max="100" step="1" value="0"  />
                                            <span class="font-weight-bold h4 float-left ml-2" style="line-height: 60%;">%</span>
                                        </div>

                                        <div class="col-8 col-md-4 col-lg-3 pt-2 mb-2  ltim-option">
                                            <span class=" font-weight-bold">Automatically Use Invested LTIM Funds To Cover Deficit </span>
                                        </div>
                                        <div class="col-4 col-md-2 col-lg-1 pt-2 mb-2 text-right border-right-md border-dark pr-2  ltim-option">
                                            <label class="checkbox">
                                                <label class="switch">
                                                    <input type="checkbox" class=" simulation-rule" data-key="ltim_cover">
                                                    <span class="switch-slider  rounded-circle" ></span>
                                                </label>
                                            </label>
                                        </div>

                                        <div class="col-8 col-md-4 col-lg-3 pt-2 mb-2">
                                            <span class=" font-weight-bold">Repeat Investment Strategy Changes For All Future Years </span>
                                        </div>
                                        <div class="col-4 col-md-2 col-lg-1 pt-2 mb-2 mb-2 text-right border-right-md border-dark pr-2">
                                            <label class="checkbox">
                                                <label class="switch">
                                                    <input type="checkbox" class="simulation-rule" data-key="inv_keep">
                                                    <span class="switch-slider  rounded-circle" ></span>
                                                </label>
                                            </label>
                                        </div>

                                        <div class="col-8 col-md-4 col-lg-3 pt-2 mb-2">
                                            <span class=" font-weight-bold">Repeat Monthly Fees Increase For All Future Years </span>
                                        </div>
                                        <div class="col-4 col-md-2 col-lg-1 pt-2 mb-2 text-right border-right-md border-dark pr-2">
                                            <label class="checkbox">
                                                <label class="switch">
                                                    <input type="checkbox" class=" simulation-rule" data-key="mf_keep">
                                                    <span class="switch-slider  rounded-circle" ></span>
                                                </label>
                                            </label>
                                        </div>
                                                
                                    </div>

                                </div>
                                
                                
                                <div class="col-12">
                                    <span class="deficit-title font-weight-bold h4"></span>
                                </div>

                                <div class="col">

                                
                                    
                                    <div class="w-100 d-block d-xl-none px-2 align-center" align="center" style="" data-tour-name="main" data-tour-step="17" data-tour-last="true" data-tour-hide-size="xl">
                                        <div class="w-100 mb-2" style="height:30px;">
                                            <strong class="h6">Year Summary</strong>
                                            <!-- <div class="w-100 h5 font-weight-bold w3-text-deep-orange deficit-almost-erased d-none">You've almost erased the deficit !</div>
                                            <div class="w-100 h5 font-weight-bold w3-text-green deficit-erased d-none">Congratulations! You have no deficit !</div> -->
                                        </div>
                                        <div class="w-100 h6 text-left px-4">
                                            
                                            <!-- <div class="d-inline-block w-50 mb-2 ">                                 Cumulated Earnings</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold"><span class="cumulated-earnings-amount">$0</span></div>
                                            <div class="d-inline-block w-50 pb-2 mb-2 border-bottom border-dark">   Cumulated Spendings</div><div class="d-inline-block w-50 pb-2 mb-2 text-right border-bottom border-dark font-weight-bold"><span class="cumulated-spendings-amount">$0</span></div>
                                            <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">   Cumulated Deficit/Surplus</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-red"><span class="cumulated-deficit-amount">$0</span></div>
                                            
                                            <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">   Deficit Per Year</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-red"><span class="yearly-deficit-amount">$0</span></div> -->
                                            
                                            <!-- <div class="d-inline-block w-50 mb-1 ">Original Surplus</div><div class="d-inline-block w-50 mb-1 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-green"><span class="surplus-amount">$0</span></div> -->
                                            <!-- <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">Original Deficit</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-red"><span class="deficit-amount">$0</span></div> -->
                                            
                                            
                                            <!-- <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">Reduced Expenses</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold w3-text-indigo border-bottom border-dark"><span class="red-spendings-amount">$0</span></div> -->
                                            
                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Annual Fee</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold" style="color:#e3ab01"><span class="yearly-increase-amount">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Loan Principal</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold " style="color:#673ab7"><span class="pi-amount">$0</span></div>
                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Assessment</div><div class="col-6 col-md-3 p-0 pb-1 m-0 text-nowrap px-2 text-left font-weight-bold " style="color:#46971d"><span class="immediate-assessment-amount">$0</span></div>
                                                
                                                <!-- <div class="d-inline-block w-50">Yearly P+I</div><div class="d-inline-block w-50 text-right font-weight-bold  w3-text-red" style=""><span class="yearly-pi-amount">$0</span></div> -->
                                                
                                                <!-- <div class="d-inline-block w-50 mt-2 pt-2 border-top border-dark">LTIM Amount</div><div class="d-inline-block mt-2 pt-2 border-top border-dark w-50 text-right font-weight-bold  w3-text-green" style=""><span class="ltim-amount">$0</span></div> -->
                                            </div>
                                            
                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Cash in Bank</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold" style="color:#e301d0"><span class="inv-strategy-p">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Project Bank Earnings</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold" style="color:#e301d0"><span class="inv-strategy-ne">$0</span></div>
                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0  ltim-option">Amount Allocated to LTIM</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold w3-text-green  ltim-option"><span class="ltim-p">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0  ltim-option">Projected LTIM Earnings</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold w3-text-green  ltim-option"><span class="ltim-ne">$0</span></div>                                                
                                            </div>

                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Planned Spending This Year</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold w3-text-indigo"><span class="spendings-amount">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Loss in Purchase Power</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold w3-text-red " style=""><span class="lp-amount">$0</span></div>                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Loan P+I Payment</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold w3-text-purple " style=""><span class="yearly-pi-amount">$0</span></div>                                                                                  
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Remaining Loan Balance</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold w3-text-purple " style=""><span class="loan-remaining-amount">$0</span></div>    
                                            </div>

                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Remaining Deficit</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-left font-weight-bold w3-text-red"><span class="remaining-deficit-amount">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Remaining Surplus</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-left font-weight-bold w3-text-green"><span class="remaining-surplus-amount">$0</span></div>
                                            </div>
                                        </div>
                                        <!-- <div class="model-info-deficit-chart d-none" style="height:300px; width:90%"></div> -->
                                    </div>

                                    <div class="row m-0 border-bottom border-dark mb-4">
                                        <nav class="mt-2 col-auto">
                                            <div class="nav nav-tabs mt-2" style="color:#000000 !important;" id="deficit-box-tabs" role="tablist">
                            
                                                <a class="nav-item nav-link py-2 simulation-tab-nav active"               id="spendings-tab" data-toggle="tab" href="#nav-spendings" role="tab" aria-controls="nav-spendings" aria-selected="false"  data-tour-name="main" data-tour-step="9">Change Priorities</a>
                                                <a class="nav-item nav-link py-2 simulation-tab-nav "               id="inv-strategy-tab" data-toggle="tab" href="#nav-inv-strategy" role="tab" aria-controls="nav-inv-strategy" aria-selected="false"  data-tour-name="main" data-tour-step="10">Investment Strategy</a>
                                                <a class="nav-item nav-link py-2 simulation-tab-nav ltim ltim-option"   id="ltim-tab" data-toggle="tab" href="#nav-ltim" role="tab" aria-controls="nav-ltim" aria-selected="false"  data-tour-name="main" data-tour-step="11" data-tour-remove-classes="d-none">LTIM Strategy</a>
                                                <a class="nav-item nav-link py-2 simulation-tab-nav "               id="monthly-fees-tab" data-toggle="tab" href="#nav-monthly-fees" role="tab" aria-controls="nav-monthly-fees" aria-selected="false"  data-tour-name="main" data-tour-step="12">Projected monthly increase</a>
                                                <a class="nav-item nav-link py-2 simulation-tab-nav "               id="loan-tab" data-toggle="tab" href="#nav-loan" role="tab" aria-controls="nav-loan" aria-selected="true"  data-tour-name="main" data-tour-step="13">Loan Options</a>
                                                <a class="nav-item nav-link py-2 simulation-tab-nav "               id="assessment-tab" data-toggle="tab" href="#nav-assess" role="tab" aria-controls="nav-assess" aria-selected="true"  data-tour-name="main" data-tour-step="14">Immediate Assessment</a>
                             
                                            </div>
                                            
                                        </nav>
                                    </div>
                                    
                                    <div class="ltim-option w-100 font-weight-bold h5 w3-text-green text-center py-2" data-tour-name="main" data-tour-step="15" data-tour-remove-classes="d-none">Working in LTIM Mode</div>


                                    <div class="w-100 w-xl-50 float-xl-left tab-content p-2 pl-4" id="deficit-box-tabContent" style="min-height:500px; overflow-auto;" data-tour-name="main" data-tour-step="16">


                                        <!-- Spendings -->
                                        <div class="tab-pane px-2 fade show active" id="nav-spendings" role="tabpanel" aria-labelledby="spendings-tab">
                                            
                                            <div class="w-100 mb-2 h6"><b class="field-name">Expenses (<span class="spendings-count">0</span>)</b></div>
                                            
                                            <div class="w-100 mt-2 mb-3 text-right spendings-actions invisible"> 
                                                <label class="btn btn-sm btn-danger spendings-cancel"><i class="fa fa-sync mr-2"></i> Revert</label> 
                                                <label class="btn btn-sm btn-success spendings-apply"><i class="fa fa-check mr-2"></i> Apply</label>
                                                <!-- <label class="btn btn-sm btn-success apply"><i class="fa fa-check mr-2"></i> Apply</label>  -->
                                            </div> 
                                            
                                            <div class="w-100 mt-3">
                                                <div class="row m-0  h6 d-none">
                                                    <div class="col-1"></div>
                                                    <div class="col-11">Item</div>
                                                </div> 
                                                <div class="w-100 overflow-auto"  style="height:400px;">
                                                    <div class="row m-0 spendings-table pt-3  h6"></div>                                                
                                                </div>
                                            </div>
                                            
                                            <div class="w-100 mt-4 mb-3 text-right">
                                                <label class="btn btn-sm btn-success spendings-apply d-none"><i class="fa fa-check mr-2"></i> Apply</label>
                                            </div>

                                        </div>
                                        
                                        <!-- Investment -->
                                        <div class="tab-pane px-2 fade" id="nav-inv-strategy" role="tabpanel" aria-labelledby="inv-strategy-tab">
                                            
                                            <div class="w-100 mb-2 h6">
                                                <b class="field-name">Investment Strategy</b>                                                
                                                <label class="btn btn-sm btn-success float-right inv-strategy-apply invisible"><i class="fa fa-check mr-2"></i> Apply</label>
                                                <label class="btn btn-sm btn-danger float-right inv-strategy-reset mr-1 invisible"><i class="fa fa-sync mr-2"></i> Reset</label>
                                            </div>
                                            
                                            <div class="w-100 mt-3">
                                                <div class="row w-100 w-md-50 w-xl-100 m-0  h6 inv-strategy-box">
                                                    <div class="col-12 h6 font-weight-bold">
                                                        1st Strategy
                                                    </div>                                  
                                                    <div class="col-sm-6 border-bottom border-dark mb-1 pb-1">
                                                        <input type="number" value="0" step="0.01" min="0" maxlength="4" step="any" class="app-input inv-startegy" data-type="rate" data-index="0" data-field="Intrest Rate" />
                                                    </div>  
                                                    <div class="col-sm-6 border-bottom border-dark mb-1 pb-1">
                                                        <input type="number" value="0" step="0.01" min="0" max="100" step="1" class="app-input inv-startegy" data-type="perc" data-index="0" data-field="% of Wallet" />
                                                    </div>  
                                                                
                                                    <div class="col-12 h6 font-weight-bold mt-3">
                                                        2nd Strategy
                                                    </div>                                  
                                                    <div class="col-sm-6 border-bottom border-dark mb-1 pb-1">
                                                        <input type="number" value="0" step="0.01" min="0" maxlength="4" step="any" class="app-input inv-startegy" data-type="rate" data-index="1" data-field="Intrest Rate" />
                                                    </div>  
                                                    <div class="col-sm-6 border-bottom border-dark mb-1 pb-1">
                                                        <input type="number" value="0" step="0.01" min="0" max="100" step="1" class="app-input inv-startegy" data-type="perc" data-index="1" data-field="% of Wallet" />
                                                    </div>  
                                                    
                                                    <div class="col-12 h6 font-weight-bold mt-3">
                                                        3rd Strategy
                                                    </div>      
                                                    <div class="col-sm-6 border-bottom border-dark mb-1 pb-1">
                                                        <input type="number" value="0" step="0.01" min="0" maxlength="4" step="any" class="app-input inv-startegy" data-type="rate" data-index="2" data-field="Intrest Rate" />
                                                    </div>  
                                                    <div class="col-sm-6 border-bottom border-dark mb-1 pb-1">
                                                        <input type="number" value="0" step="0.01" min="0" max="100" step="1" class="app-input inv-startegy" data-type="perc" data-index="2" data-field="% of Wallet" />
                                                    </div>  

                                                </div>
                                            </div>
                                            
                                        </div>

                                        
                                        <!-- LTIM --> 
                                        <div class="tab-pane px-2 fade" id="nav-ltim" role="tabpanel" aria-labelledby="ltim-tab">
                                            
                                            <div class="w-100 mb-2 h6">
                                                <b class="field-name">LTIM Strategy</b>                                                
                                            </div>
                                            
                                            <div class="w-100 mt-3">
                                                <div class="row w-100 w-md-75 w-xl-100 m-0 ltim-box">
                                                    
                                                    <div class="col-7 pb-2 font-weight-bold">Year's Total Savings</div><div class="col-5 text-right w3-text-green font-weight-bold ltim-ta">$0</div>
                                                    <div class="col-7 pb-2 font-weight-bold">3 Years Of Projected Spending</div><div class="col-5 text-right w3-text-red font-weight-bold ltim-yoc">$0</div>

                                                    <div class="col-7 py-2 mt-2 font-weight-bold border-top border-dark">Available to Invest</div><div class="col-5 py-2 mt-2 text-right font-weight-bold border-top border-dark w3-text-green font-weight-bold ltim-surplus">$0</div>

                                                </div>
                                            </div>

                                            
                                            <div class="w-100 w-md-75 w-xl-100 mb-0 mt-4 h6">
                                                <b class="field-name">LTIM Options</b>                                                
                                                <label class="btn btn-sm btn-danger float-right ltim-perc-reset mr-1 invisible"><i class="fa fa-sync mr-2"></i> Reset</label>
                                            </div>

                                            <div class="row m-0 p-0 w-100 w-md-75 w-xl-100 m-0 position-relative">
                                                
                                                <div class="col-6">
                                                    <b class="field-name">% to Allocate to LTIM:</b><br>
                                                    <!-- <div class="ltim-amount-slider app-slider ui-orange ml-3 mt-2"  style="/*width:250px;*/" data-key="ltim_amount" data-percent="true" 
                                                        data-range="min" data-min="0"  data-step="1" data-max="100" data-format="n?%" data-handle-top="0rem" data-limits="true"></div> -->
                                                    <div class="" style="width:225px; height:40px;"> 
                                                        <input type="number" class="ltim-perc app-input" value="0" min="0" max="100" data-classes="d-inline-block" data-style="width:80px;"  style="font-size: 0.9rem;">                                                        
                                                        <span class="font-weight-bold h5">%</span>
                                                        <label class="btn btn-sm btn-success ltim-perc-apply ">Apply</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 pb-2"></div>
                                                

                                                <div class="col-6 pb-2 d-nones">
                                                    <span class="font-weight-bold">Amount Allocated to LTIM:</span><br>                                                    
                                                    <span class="font-weight-bold ltim-p h5" style="line-height: 2.1rem;">0</span>
                                                    <!-- <div class="" style="width:225px; height:40px;"> 
                                                        <span class="font-weight-bold">$</span>
                                                        <input type="number" class="ltim-amount app-input" value="0" min="0" data-classes="d-inline-block" data-style="width:150px;"  style="font-size: 0.9rem;">
                                                        <label class="btn btn-sm btn-success ltim-amount-apply ">Apply</label>
                                                    </div> -->
                                                </div>

                                                <div class="col-6 pb-2 d-nones">
                                                    <span class="font-weight-bold">Projected LTIM Earnings:</span><br>                                                    
                                                    <span class="font-weight-bold ltim-ne w3-text-green h5" style="line-height: 2.1rem;">0</span>
                                                    <!-- <div class="" style="width:225px; height:40px;"> 
                                                        <span class="font-weight-bold">$</span>
                                                        <input type="number" class="ltim-amount app-input" value="0" min="0" data-classes="d-inline-block" data-style="width:150px;"  style="font-size: 0.9rem;">
                                                        <label class="btn btn-sm btn-success ltim-amount-apply ">Apply</label>
                                                    </div> -->
                                                </div>

                                                <!-- <div class="col-8 pt-3 border-top border-dark">
                                                    <span class=" font-weight-bold ltim-amount-chk-txt invisible">Remove From Compound of Savings</span>
                                                </div>
                                                <div class="col-4 pt-3 text-right border-top border-dark">
                                                    <label class="checkbox ltim-amount-chk-txt invisible">
                                                        <label class="switch">
                                                            <input type="checkbox" class="ltim-amount-chk">
                                                            <span class="switch-slider  rounded-circle" ></span>
                                                        </label>
                                                    </label>
                                                </div>
                                                -->

                                                <div class="ltim-amount-disable position-absolute w-100 h-100 d-none" style="top:0; left:0; background:rgba(200,200,200,0.5); z-index:9" onclick="e.preventDefault(); "></div>

                                            </div>


                                            <div class="w-100 w-md-75 w-xl-100 mb-0 mt-4 h6">
                                                <b class="field-name">LTIM Available</b>                                                
                                                <label class="btn btn-sm btn-danger float-right ltim-withdraw-reset mr-1 invisible"><i class="fa fa-sync mr-2"></i> Reset</label>
                                            </div>

                                            <div class="row  w-100 w-md-75 w-xl-100 m-0 p-0 position-relative">
                                                
                                                <div class="col-8 mt-3 pb-1 h6 font-weight-bold">Projected Accumulated LTIM Principal</div><div class="col-4 mt-3 pb-1 h6 text-right w3-text-indigo font-weight-bold ltim-acc-p">$0</div>
                                                <div class="col-8 mt-0 pb-2 h6 font-weight-bold border-bottom border-dark">Projected Compounded LTIM Earnings</div><div class="col-4 border-bottom border-dark mt-0 pb-2 h6 text-right w3-text-deep-purple font-weight-bold ltim-acc-ne">$0</div>
                                                <div class="col-8 mt-1 pb-0 h6 font-weight-bold">Projected Total LTIM Funds</div><div class="col-4 mt-1 pb-0 h6 text-right w3-text-green font-weight-bold text-nowrap ltim-acc">$0</div>

                                                <div class="col-12 mt-2 d-none">
                                                    <b class="field-name">% to Use from LTIM Principal :</b><br>
                                                    <div class="ltim-withdraw-slider app-slider ui-orange ml-3 mt-2"  style="/*width:250px;*/" data-key="ltim_withdraw" data-percent="true" 
                                                        data-range="min" data-min="0"  data-step="1" data-max="100" data-format="n?%" data-handle-top="0rem" data-limits="true"></div>

                                                </div>
                                                
                                                <div class="col-6 mt-2 pb-2">
                                                    <span class="font-weight-bold">Amount to Withdraw:</span> 
                                                    <div class="" style="width:225px; height:40px;"> 
                                                        <span class="font-weight-bold">$</span>
                                                        <input type="number" class="ltim-withdraw app-input" value="0" min="1" data-classes="d-inline-block" data-style="width:120px;"  style="font-size: 0.9rem;">
                                                        <label class="btn btn-sm btn-success ltim-withdraw-apply invisible">Apply</label>
                                                    </div>
                                                    <label class="btn btn-sm btn-danger ltim-withdraw-clear invisible">Clear Deficit</label>
                                                </div>
                                                

                                                <div class="col-6 mt-2 pb-2">
                                                    <span class="font-weight-bold">Amount Withdrawn:</span><br>                                                    
                                                    <span class="font-weight-bold ltim-wth h5" style="line-height: 2.1rem;">0</span>
                                                    <!-- <div class="" style="width:225px; height:40px;"> 
                                                        <span class="font-weight-bold">$</span>
                                                        <input type="number" class="ltim-amount app-input" value="0" min="0" data-classes="d-inline-block" data-style="width:150px;"  style="font-size: 0.9rem;">
                                                        <label class="btn btn-sm btn-success ltim-amount-apply ">Apply</label>
                                                    </div> -->
                                                </div>


                                                <div class="col-8 mt-2 pb-2 h6 font-weight-bold ltim-next-acc-txt invisible">Next Year's Projected Total LTIM Funds</div><div class="col-4 mt-0 pb-2 h6 text-right w3-text-green font-weight-bold text-nowrap  invisible ltim-next-acc-txt ltim-next-acc">$0</div>

                                                <div class="ltim-withdraw-disable position-absolute w-100 h-100 d-none" style="top:0; left:0; background:rgba(200,200,200,0.5); z-index:9" onclick="e.preventDefault(); "></div>


                                            </div>
                                            
                                        </div>
                                        
                                        <!-- Monthly Fees -->
                                        <div class="tab-pane px-2 fade " id="nav-monthly-fees" role="tabpanel" aria-labelledby="monthly-fees-tab">
                                            <b class="field-name  d-none">Projected monthly increase:</b><br>
                                            <div class="monthly-fees-slider  d-none app-slider ui-orange ml-3 mt-2"  style="/*width:250px;*/" data-key="monthly_fees" data-percent="true" 
                                                data-range="min" data-min="0"  data-step="1" data-max="1000" data-format="$n?" data-handle-top="0rem" data-limits="true"></div>
                                            
                                            </br>
                                            
                                            <div class="w-100 text-left" style="height:35px;">
                                                <label class="btn btn-sm btn-danger monthly-fees-reset-btn d-none">Reset</label>
                                                <label class="btn btn-sm btn-success monthly-fees-clear-btn d-none">Clear Deficit</label>
                                            </div>
                                                                                    
                                            <span class="h6  font-weight-bold">Projected Monthly Fees:</span><span class="font-weight-bold h5 ml-2">$</span> 
                                                <div class="d-inline-block" style="width:150px; height:40px;"> 
                                                    <input type="number" class="monthly-fees app-input" min="1" data-classes="float-left" data-style="width:100px;"  style="font-size: 1.1rem;">
                                                    <label class="btn btn-sm btn-success monthly-fees-apply invisible">Apply</label>
                                                </div>
                                            </br>
                                            <span class="h6  font-weight-bold mt-2">Default Monthly Fees:</span>
                                                <span class="monthly-fees-old  font-weight-bold h5  ml-2">$0.00</span></b></span>                                                                                 

                                            
                                            <div class="container m-0 p-0 w-100 border-top border-dark mt-4 pt-4 monthly-fees-year-box invisible">  
                                                
                                                <div class="w-100 text-right" style="height:35px;">
                                                    <label class="btn btn-sm btn-danger monthly-fees-year-reset-btn d-none">Reset</label>
                                                </div>                  
                                            
                                                <span class="h6 font-weight-bold ">Start Monthly Fees Increase In Year:</span>
                                                <div class="w-100 pt-2"> 
                                                    <input type="number" class="monthly-fees-year app-input" min="0" data-classes="float-left" data-style="width:100px;"  style="font-size: 1.1rem;">
                                                    <label class="btn btn-sm btn-success monthly-fees-year-apply invisible">Apply</label>
                                                </div>
                                                </br>
                                                <span class="h6  font-weight-bold mt-2">Increasing From Year <span class="w3-text-red h5  font-weight-bold monthly-fees-year-from">0</span> By:</span>
                                                <span class="monthly-fees-year-by font-weight-bold h5 w3-text-red  ml-2">$0</span></span>                                                                                 

                                                </br>
                                            </div> 
                                            
                                        </div>

                                        
                                        <!-- Loan -->
                                        <div class="tab-pane px-2 fade position-relative" id="nav-loan" role="tabpanel" aria-labelledby="loan-tab">
                                            <b class="field-name">Loan Options:</b><br>
                                            <div class="row  w-100 w-md-50 w-xl-100 m-0">
                                                <div class="col-12 my-2 text-right" style="height:30px;">
                                                        <label class="btn btn-sm btn-danger  loan-reset d-none ml-1 ">Reset</label>
                                                        <label class="btn btn-sm btn-success loan-apply d-none"><i class="fa fa-check mr-2"></i> Apply</label>

                                                </div>
                                                <div class="col-12 mb-4">
                                                    <!-- <div class="loan-slider app-slider editor-slider ui-purple" data-key="loan_ratio" data-range="min" data-min="0" data-max="100" data-value="0" data-format="?%"  data-handle-top="0rem" data-limits="true"></div> -->
                                                    <span class="h6">Take Loan for : <span class="font-weight-bold h5 ml-2">$</span> 
                                                        <div class="d-inline-block mr-2" style="width:150px;">                                            
                                                            <input type="number" class="loan-amount app-input h5" min="0" style="font-size: 1.1rem;" />
                                                        </div> 
                                                        <!-- of <span class="deficit-loan-remaining h5 font-weight-bold w3-text-red ml-2"></span> -->
                                                        <label class="btn btn-sm btn-success loan-clear-btn d-none">Clear Deficit</label>
                                                    </span>
                                                </div>
                                                
                                                <div class="col-sm-6 mt-2">
                                                    <input class="app-input model-info-val" data-key="bank_rate" data-percent="true" data-field="Intrest Rate" data-icon="percent" type="number" step="any"  min="0" max="30" />
                                                </div>
                                                <div class="col-sm-6 mt-2">
                                                    <input class="app-input model-info-val" data-key="loan_years" data-field="Terms" data-icon="calendar" type="number" step="1"  min="0" max="30" />
                                                </div>
                                                
                                                <div class="col-12 mt-2">
                                                    <span class="h6 text-nowrap">Total P+I : <span class="loan-total ml-2 h5 font-weight-bold w3-text-deep-purple">$0.00</span></span>                                                     
                                                    <br>
                                                    <span class="h6 text-nowrap">Intrest : <span class="loan-intrests ml-4 h5 font-weight-bold w3-text-deep-orange">$0.00</span></span>
                                                </div>

                                            </div>

                                            <div class="deficit-loan-disable position-absolute w-100 h-100 d-none" style="top:0; left:0; background:rgba(200,200,200,0.5); z-index:9" onclick="e.preventDefault(); "></div>

                                        </div>

                                        <!-- Assessment -->
                                        <div class="tab-pane px-2 fade" id="nav-assess" role="tabpanel" aria-labelledby="assessment-tab">
                                            <span class="font-weight-bold h5 mr-1">$</span><input type="number" min="0" value="0" class="app-input assessment-amount"  style="font-size: 1.1rem;" data-classes="d-inline-block" data-field="Assessment Amount" data-style="max-width:200px; width:100%;" />
                                            <label class="btn btn-sm btn-success assessment-apply d-none">Apply</label>
                                            </br>
                                            <span class="h6 mt-2">Total Assessment Amount:</span>
                                                <span class=" assessment-total  font-weight-bold h5  ml-2 w3-text-green">$0.00</span></b></span>   
                                            </br>
                                            <span class="h6 mt-2">Assessment Amount Per Unit:</span>
                                                <span class=" assessment-unit  font-weight-bold h5  ml-2 w3-text-green">$0.00</span></b></span>   
                                        </div>
                                    </div>

                                    
                                    <div class="w-100 w-xl-50 d-none d-xl-block float-right px-2 align-center" align="center" style="" data-tour-name="main" data-tour-step="17" data-tour-last="true" data-tour-show-size="xl" >
                                        <div class="w-100 mb-2" style="height:30px;">
                                            <strong class="h6">Year Summary</strong>
                                            <!-- <div class="w-100 h5 font-weight-bold w3-text-deep-orange deficit-almost-erased d-none">You've almost erased the deficit !</div>
                                            <div class="w-100 h5 font-weight-bold w3-text-green deficit-erased d-none">Congratulations! You have no deficit !</div> -->
                                        </div>
                                        <div class="w-100 h6 text-left px-4">
                                            
                                            <!-- <div class="d-inline-block w-50 mb-2 ">                                 Cumulated Earnings</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold"><span class="cumulated-earnings-amount">$0</span></div>
                                            <div class="d-inline-block w-50 pb-2 mb-2 border-bottom border-dark">   Cumulated Spendings</div><div class="d-inline-block w-50 pb-2 mb-2 text-right border-bottom border-dark font-weight-bold"><span class="cumulated-spendings-amount">$0</span></div>
                                            <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">   Cumulated Deficit/Surplus</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-red"><span class="cumulated-deficit-amount">$0</span></div>
                                            
                                            <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">   Deficit Per Year</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-red"><span class="yearly-deficit-amount">$0</span></div> -->
                                            
                                            <!-- <div class="d-inline-block w-50 mb-1 ">Original Surplus</div><div class="d-inline-block w-50 mb-1 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-green"><span class="surplus-amount">$0</span></div> -->
                                            <!-- <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">Original Deficit</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold border-bottom border-dark w3-text-red"><span class="deficit-amount">$0</span></div> -->
                                            
                                            
                                            <!-- <div class="d-inline-block w-50 border-bottom border-dark mb-2 pb-2">Reduced Expenses</div><div class="d-inline-block w-50 pb-2 text-right font-weight-bold w3-text-indigo border-bottom border-dark"><span class="red-spendings-amount">$0</span></div> -->
                                            
                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Annual Fee</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold" style="color:#e3ab01"><span class="yearly-increase-amount">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Loan Principal</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold " style="color:#673ab7"><span class="pi-amount">$0</span></div>
                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Assessment</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold " style="color:#46971d"><span class="immediate-assessment-amount">$0</span></div>
                                                
                                                <!-- <div class="d-inline-block w-50">Yearly P+I</div><div class="d-inline-block w-50 text-right font-weight-bold  w3-text-red" style=""><span class="yearly-pi-amount">$0</span></div> -->
                                                
                                                <!-- <div class="d-inline-block w-50 mt-2 pt-2 border-top border-dark">LTIM Amount</div><div class="d-inline-block mt-2 pt-2 border-top border-dark w-50 text-right font-weight-bold  w3-text-green" style=""><span class="ltim-amount">$0</span></div> -->
                                            </div>
                                            
                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Cash in Bank</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold" style="color:#e301d0"><span class="inv-strategy-p">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Project Bank Earnings</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold" style="color:#e301d0"><span class="inv-strategy-ne">$0</span></div>
                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0  ltim-option">Amount Allocated to LTIM</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold w3-text-green  ltim-option"><span class="ltim-p">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0  ltim-option">Projected LTIM Earnings</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold w3-text-green  ltim-option"><span class="ltim-ne">$0</span></div>                                                
                                            </div>

                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Planned Spending This Year</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap pl-2 text-left font-weight-bold w3-text-indigo"><span class="spendings-amount">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Loss in Purchase Power</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold w3-text-red " style=""><span class="lp-amount">$0</span></div>                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Loan P+I Payment</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold w3-text-purple " style=""><span class="yearly-pi-amount">$0</span></div>                                                
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Remaining Loan Balance</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-nowrap px-2 text-left font-weight-bold w3-text-purple " style=""><span class="loan-remaining-amount">$0</span></div>                                                
                                            </div>

                                            <div class="row p-0 m-0 my-2 pb-2 border-bottom border-dark">
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Remaining Deficit</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-left font-weight-bold w3-text-red"><span class="remaining-deficit-amount">$0</span></div>
                                                <div class="col-6 col-md-3 p-0 pb-2 m-0">Remaining Surplus</div><div class="col-6 col-md-3 p-0 pb-2 m-0 text-left font-weight-bold w3-text-green"><span class="remaining-surplus-amount">$0</span></div>
                                            </div>
                                        </div>
                                        <div class="model-info-deficit-chart" style="height:300px; width:90%"></div>
                                    </div>

                                
                                </div>
                                <div class="col-auto p-4 pl-5 pr-0 border-left border-dark w3-white position-relative simulation-fa-parent d-none d-lg-block" data-tour-name="main" data-tour-step="8"  data-tour-show-size="lg" style="width:350px; top:30px; right:0;">
                                    <div class="w-100 pb-1 border-bottom border-dark font-weight-bold">Remaining Amount</div>
                                    <div class="row m-0 p-0">
                                        <div class="col-4 p-1 text-center border-bottom border-dark"></div>
                                            <div class="col-4 p-1 text-center  border-bottom border-dark">Managed</div>
                                            <div class="col-4 p-1 text-center  border-bottom border-dark">Original</div>
                                    </div>
                                    <div class="container p-0 mx-2 overflow-auto" style="max-height:650px">
                                        <div class="row simulation-fa font-weight-bold m-0 p-0">

                                        </div>
                                    </div>
                                    
                                    <div class="w-100 position-absolute d-none simulation-fa-overlay" style="top:0; left:0; bottom:0; background:rgba(200,200,200,0.5);" onclick="void(0);">

                                    </div>

                                </div>
                            </div>

                            <div class="modal fade" id="simultaion_graph_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2);" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
                                <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:95% !important; width:95% !important;"> 
                                    <div class="modal-content shadow"> 
                                        <div class="modal-header pb-2"> 
                                        <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Simulation Graph</h5> 
                                        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                                            <span aria-hidden="true">×</span> 
                                        </button> 
                                        </div> 

                                        <div class="modal-body pt-0">
                                                
                                            <div class="row">
                                                
                                                <div class="col-12 pb-3 mb-3 simulation-visual position-relative">   
                                                    <div class="col-12 pb-3 my-3 mt-5 simulation-chart w-100" style="height:600px;">
                                                    
                                                    </div>
                                                    <div class="col-12 py-3 my-3 simulation-handles d-none w-100">
                                                
                                                    </div>
                                                </div>

                                            </div>  

                                        
                                        </div> 
                                    </div> 
                                </div>
                            </div>

                            
                            <div class="modal fade" id="simulation_table_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
                                <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:95% !important; width:95% !important;"> 
                                    <div class="modal-content shadow"> 
                                        <div class="modal-header pb-2"> 
                                        <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Simulation Data</h5> 
                                        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                                            <span aria-hidden="true">×</span> 
                                        </button> 
                                        </div> 

                                        <div class="modal-body pt-0"> 
                                                
                                            <div class="row">
                                                        <div class="col-12">
                                                            <label class="btn btn-sm w3-green mt-2 float-right" id="simulation-table-export"><i class="fa fa-download mr-2"></i>Export Data</label>
                                                        </div> 
                                                        <div class="col-12 mt-2 overflow-auto table-responsive">
                                                                <table class="table-primary start-toggled w-100 simulation-calculation-table d-nones" id="simulation_table" style="height:800px;">
                                                                    <thead>
                                                                        <tr>
                                                                            <th tabulator-field="year" tabulator-frozen="true"  tabulator-minWidth="70" tabulator-minWidth="70"  tabulator-maxWidth="70" tabulator-hozAlign="center" tabulator-resizable="false" tabulator-headerSort="false" data-css="text-center border-right">Year</th>
                                                                            
                                                                            <th tabulator-field="sa" tabulator-frozen="true" tabulator-headerSort="false" tabulator-cssClass="cell-bold" tabulator-width="110" >Starting Amount</th>
                                                                            <!-- <th tabulator-field="sa_o" tabulator-frozen="true" tabulator-headerSort="false" tabulator-cssClass="togglable cell-bold cell-ltim" tabulator-width="110">Starting Amount ORG</th> -->

                                                                            <th tabulator-field="assess" tabulator-headerSort="false" tabulator-cssClass="togglables cell-bold">Immediate Assessment</th>

                                                                            <th tabulator-field="loan_t" tabulator-headerSort="false" tabulator-cssClass="togglables cell-bold" tabulator-width="110">Loan</th>
                                                                            <!-- <th tabulator-field="loan_pr_ltim" tabulator-headerSort="false" tabulator-cssClass="negative cell-ltim togglable cell-bold" tabulator-width="110">Previous Loan Payment LTIM</th> -->

                                                                            

                                                                            
                                                                            <th tabulator-field="yc" tabulator-headerSort="false" tabulator-width="110">Yearly Collections</th>
                                                                            <!-- <th tabulator-field="yc_o" tabulator-headerSort="false" tabulator-cssClass="togglable cell-ltim" tabulator-width="110">Yearly Collections ORG</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="asses" tabulator-headerSort="false" tabulator-width="110">Immediate Assessment</th> -->
                                                                            
                                                                            <th tabulator-field="ta" tabulator-headerSort="false" tabulator-cssClass="togglables" tabulator-width="110">Total Investment Amount</th>
                                                                            <!-- <th tabulator-field="ta_o" tabulator-headerSort="false" tabulator-cssClass="togglable cell-ltim" tabulator-width="110">Total starting amount ORG</th> -->

                                                                            
                                                                            
                                                                            <th tabulator-field="ir" tabulator-headerSort="false" data-css="text-right pl-3" tabulator-cssClass="decimals togglables" tabulator-width="85">Investment Strategy</th>
                                                                            <!-- <th tabulator-field="is_ltim" tabulator-headerSort="false" data-css="text-right pl-3" tabulator-cssClass="decimals cell-ltim togglable" tabulator-width="85">Investment Strategy LTIM</th> -->

                                                                            
                                                                            

                                                                            <th tabulator-field="ne" tabulator-headerSort="false" data-css="text-right pl-3"  tabulator-cssClass="togglables" tabulator-width="110">Net earning</th>
                                                                            <!-- <th tabulator-field="ne_o" tabulator-headerSort="false" data-css="text-right pl-3"  tabulator-cssClass="togglable cell-ltim" tabulator-width="110">Net earning ORG</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="ltim_i" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold cell-text-green cell-bold" tabulator-width="110">LTIM Intrests</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="sep" tabulator-cssClass="empty cell-bold"  tabulator-width="20" tabulator-minWidth="20"  tabulator-maxWidth="20" tabulator-hozAlign="center" tabulator-resizable="false" tabulator-headerSort="false" data-css="text-center border-right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th> -->
                                                                            
                                                                            <th tabulator-field="cp" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold cell-green cell-bold" tabulator-width="110">Compound value of savings</th>
                                                                            <!-- <th tabulator-field="cp_o" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold togglable cell-ltim cell-bold" tabulator-width="110">Compound value of savings ORG</th>
                                                                            
                                                                            <th tabulator-field="loan_y" tabulator-headerSort="false" tabulator-cssClass="negative togglables cell-bold" tabulator-width="110">Loan Payment</th> -->
                                                                            <!-- <th tabulator-field="loan_pr_ltim" tabulator-headerSort="false" tabulator-cssClass="negative cell-ltim togglable cell-bold" tabulator-width="110">Previous Loan Payment LTIM</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="sep" tabulator-cssClass="empty cell-bold"  tabulator-width="60" tabulator-minWidth="60"  tabulator-maxWidth="60" tabulator-hozAlign="center" tabulator-resizable="false" tabulator-headerSort="false" data-css="text-center border-right">&nbsp;&nbsp;&nbsp;</th> -->
                                                                            
                                                                            
                                                                            <th tabulator-field="lp" tabulator-headerSort="false" tabulator-cssClass="negative togglables cell-bold" tabulator-width="110">Loss in purchasing power</th>
                                                                            <!-- <th tabulator-field="lp_o" tabulator-headerSort="false" tabulator-cssClass="negative cell-ltim togglable cell-bold" tabulator-width="110">Loss in purchasing power ORG</th> -->
                                                                            
                                                                            <th tabulator-field="sp" tabulator-headerSort="false" tabulator-cssClass="negative text-nowrap cell-bold" tabulator-minWidth="130" tabulator-width="130">Spending</th>
                                                                            
                                                                            <th tabulator-field="loan_pay" tabulator-headerSort="false" tabulator-cssClass="cell-bold negative">Loan Payments</th>
                                                                            

                                                                            <th tabulator-field="fa" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold cell-green cell-bold" tabulator-width="110">Remaining Amount</th>
                                                                            <!-- <th tabulator-field="fa_o" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold togglables cell-green cell-bold" tabulator-width="110">Remaining Amount ORG</th>
                                                                            
                                                                            <th tabulator-field="erase_deficit" tabulator-cssClass="cell-bold"  tabulator-minWidth="130" tabulator-minWidth="130"  tabulator-maxWidth="130" tabulator-hozAlign="center" tabulator-resizable="false" tabulator-headerSort="false" data-css="text-center border-right">&nbsp;</th>
                                                                            
                                                                            <th tabulator-field="ltim" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold cell-bold" tabulator-width="110">Amount Used In LTIM</th>
                                                                            <th tabulator-field="ltim_i" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold cell-green cell-bold" tabulator-width="110">LTIM Intrests</th> -->
                                                                            <!-- <th tabulator-field="ltim_pi" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold cell-green cell-bold" tabulator-width="110">LTIM P+I</th> -->
                                                                            <!-- <th tabulator-field="ltim_r" tabulator-headerSort="false" tabulator-cssClass="tabulator-bold percent decimals cell-bold" tabulator-width="110">LTIM Inv Rate</th> -->
                                                                            <!-- <th tabulator-field="erase_deficit_ltim" tabulator-cssClass="cell-bold"  tabulator-minWidth="120" tabulator-minWidth="120"  tabulator-maxWidth="120" tabulator-hozAlign="center" tabulator-resizable="false" tabulator-headerSort="false" data-css="text-center border-right">&nbsp;&nbsp;</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="loan_pi" tabulator-headerSort="false" tabulator-cssClass="cell-bold purple">Loan</th>
                                                                            <th tabulator-field="loan_i" tabulator-headerSort="false" tabulator-cssClass="cell-bold purple">Loan Intrests</th>
                                                                            <th tabulator-field="loan_pay" tabulator-headerSort="false" tabulator-cssClass="cell-bold purple">Loan Payments</th>

                                                                            <th tabulator-field="assess" tabulator-headerSort="false" tabulator-cssClass="cell-bold green">Immediate Assessment</th> -->

                                                                            
                                                                            <!-- <th tabulator-field="yc_cp" tabulator-headerSort="false" tabulator-cssClass="amber cell-bold" >Compound Value Of Savings</th>                                                    
                                                                            <th tabulator-field="yc_tx" tabulator-headerSort="false" tabulator-cssClass="amber cell-bold negative" >Total Expenses</th> -->
                                                                            <!-- <th tabulator-field="yc_inc" tabulator-headerSort="false" tabulator-cssClass="amber cell-bold" >Yearly Collections Increase By</th>
                                                                            <th tabulator-field="mf_new" tabulator-headerSort="false" tabulator-cssClass="amber cell-bold" >Projected Monthly Fee</th>
                                                                            <th tabulator-field="mf_inc_r" tabulator-headerSort="false" tabulator-cssClass="amber cell-bold" >Monthly Fee Increase By</th>                                                   -->
                                                                            
                                                                            <!--
                                                                            <th tabulator-field="yc_fa" tabulator-headerSort="false" tabulator-cssClass="amber cell-bold" >Collections Remaining Amount</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="inv" tabulator-headerSort="false" tabulator-cssClass="green cell-bold" >Investment Startegy Starting Amount</th>
                                                                            <th tabulator-field="inv_f" tabulator-headerSort="false" tabulator-cssClass="green cell-bold cell-green" >Investment Startegy Remaining Amount</th>
                                                                            <th tabulator-field="inv_s" tabulator-headerSort="false" tabulator-cssClass="green cell-bold">Investment Startegy Save</th> -->
                                                                            
                                                                            <!-- <th tabulator-field="erase_deficit" tabulator-frozen="true" tabulator-cssClass="cell-bold"  tabulator-minWidth="120" tabulator-minWidth="120"  tabulator-maxWidth="90" tabulator-hozAlign="center" tabulator-resizable="false" tabulator-headerSort="false" data-css="text-center border-right"></th> -->
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    </tbody>
                                                                </table>
                                                        </div>
                                            </div>

                                        
                                        </div> 
                                    </div> 
                                </div>
                            </div>

                            
                                
                                

                                


                        </div>
                    </div>



                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; <?php echo $app_name; ?> 2021</span>
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

    
    <!-- Footer -->        
    <?php echo print_footer(); ?>
    <!-- End of Footer -->

    <!-- JS -->        
    <?php echo print_js(['models']); ?>
    <!-- End of JS -->


    <script>
        var simulation = init_simulation('#simulation');
    </script>

</body>

</html>