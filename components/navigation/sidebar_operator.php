
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $base_web; ?>/">
                <div class="sidebar-brand-text mx-3">
                    <img class="mb-2 mt-0 sidebar-brand-img"  src="<?php echo $base_web; ?>/assets/images/booteek_logo_white.png"/>
                </div>
            </a>

            <!-- Heading -->
            <div class="sidebar-heading">
                Point de vente
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?php echo $pages['pos']; ?>"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Point de Vente</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-2">

            <!-- Heading -->
            <div class="sidebar-heading">
                Activité
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?php echo $pages['clients']; ?>"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Clients</span>
                </a>
            </li>

            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?php echo $pages['orders']; ?>"
                    aria-expanded="true" aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-list-alt"></i>
                    <span>Commandes</span>
                </a>
            </li>

            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?php echo $pages['quotes']; ?>"
                    aria-expanded="true" aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-sign-in-alt"></i>
                    <span>Devis</span>
                </a>
            </li>

            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?php echo $pages['invoices']; ?>"
                    aria-expanded="true" aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Factures</span>
                </a>
            </li>



        </ul>
        <!-- End of Sidebar -->