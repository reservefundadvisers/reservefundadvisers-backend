
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion <?= $_COOKIE["sidebar"] === "true" ? 'toggled' : ''; ?>" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand w3-text-white d-flex align-items-center justify-content-center my-3 " href="javascript:void(0);">
                <div class="sidebar-brand-text mx-3">
                    <img class="mb-2  sidebar-brand-img" style="" src="<?php echo $base_web; ?>/assets/images/logo_white.png?v=1.0"/>
                </div>
            </a>


            <div class="sidebar-close"><i class="fa fa-times mr-1"></i> Close</div>
            

            <div class="w-100 text-center w3-text-white"><b>YOUR WEALTH, <span class="w3-text-green">YOUR FUTURE</span></b></div>
            
            <!-- Nav Item - User Information -->
            <div class="w-100 mb-2 mt-3  px-3 pb-2 border-white border-bottom">

                <span class="float-left w3-text-white  text-nowrap"  style="font-size:13px; ">
                    <?php $infos = $auth->sessioninfo(); echo ( ucfirst($infos['fn'])." ".strtoupper($infos['ln']) ); ?>
                    <br>
                    <span class="badge <?= $roles_badge[$auth->role()]; ?>"><?= ucfirst($roles_name[$auth->role()]); ?></span>
                </span>

                
                <label class="mt-3 float-right btn btn-sm w3-hover-text-red w3-text-white font-weight-bold text-nowrap" onclick="window.location.href = '<?php echo $pages['login']."?disconnect"; ?>' ">
                    <i class="fas fa-power-off fa-fw " style=""></i>
                    Logout
                    <!-- Counter - Messages -->
                </label>
            </div>

            <?php

                $menus = $roles_menu[$auth->role()];
                $isFirstHeader = true;

                
                
                foreach($menus as $menu ){

                    $header = $menu['header'];
                    $items = $menu['items'];


                    if(empty($items))continue;

                    if(!empty($header)){
                        if(!$isFirstHeader)echo '<hr class="sidebar-divider my-2">';
                        echo '  <!-- Heading -->
                                <div class="sidebar-heading">
                                    '.$header.'
                                </div>';
                    }

                    foreach($items as $item){
                        $icon = check_val($item, 'ic');
                        $txt  = ucwords(check_val($item, 'txt', 'item'));
                        $link = check_val($pages, check_val($item, 'link'), '#');

                        echo '
                                    <!-- Nav Item - Pages Collapse Menu -->
                                    <li class="nav-item">
                                        <a class="nav-link collapsed" href="'.$link.'"
                                            aria-expanded="true" aria-controls="collapseTwo">
                                            <i class="fas fa-fw fa-'.$icon.'"></i>
                                            <span>'.$txt.'</span>
                                        </a>
                                    </li>
                             ';
                    }

                    $isFirstHeader = false;
                }



            ?>


            <div class="position-sticky w3-text-white container p-0 m-0 px-2 w-100 pt-3 " style="top:60%; left:0px; right:0px;">
                
                <label class="mb-3 btn btn-sm w3-blue tour-start d-none" data-tour-name="main">Start Tour</label>

                <h5 class="w3-text-green">Help</h5>
                <div class="container m-0 p-0 mb-2">
                    <span class="font-weight-bold w3-indigo badge">CALL NOW</span><br>
                    <span class="h5">727.788.4800</span>
                </div>
                <span class="font-weight-bold w3-indigo badge">Email</span><br>
                <span class="" style="font-size: 0.9rem;">support@reservefundadvisers.com</span>
                

            </div>

            
            

        </ul>
        <!-- End of Sidebar -->