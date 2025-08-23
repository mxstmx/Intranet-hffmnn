<?php
// Navigation and header shared across pages. Expects $username to be defined.
?>
<!--! ================================================================ !-->
<!--! [Start] Navigation Menu !-->
<!--! ================================================================ !-->
<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="index.html" class="b-brand">
                <!-- ========   change your logo hear   ============ -->
                <img src="assets/images/hoffmann-logo-light.png" alt="" class="logo logo-lg" />
                <img src="assets/images/hoffmann-logo-light.png" alt="" class="logo logo-sm" />
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-item nxl-caption"><label>Navigation</label></li>
                <li class="nxl-item"><a class="nxl-link" href="dashboard.php"><span class="nxl-micon"><i class="feather-file-text"></i></span><span class="nxl-mtext">Dashboard</span></a></li>
                <li class="nxl-item"><a class="nxl-link" href="offene_posten.php"><span class="nxl-micon"><i class="feather-file-text"></i></span><span class="nxl-mtext">Belege</span></a></li>
                <li class="nxl-item"><a class="nxl-link" href="textbestellungen.php"><span class="nxl-micon"><i class="feather-type"></i></span><span class="nxl-mtext">Textbestellung</span></a></li>
                <li class="nxl-item"><a class="nxl-link" href="bestand.php"><span class="nxl-micon"><i class="feather-database"></i></span><span class="nxl-mtext">Bestand</span></a></li>
                <li class="nxl-item"><a class="nxl-link" href="steuermarken.php"><span class="nxl-micon"><i class="feather-tag"></i></span><span class="nxl-mtext">Steuermarken</span></a></li>
                <li class="nxl-item"><a class="nxl-link" href="bestellungen.php"><span class="nxl-micon"><i class="feather-shopping-cart"></i></span><span class="nxl-mtext">Bestellungen</span></a></li>
                <li class="nxl-item"><a class="nxl-link" href="tickets.php"><span class="nxl-micon"><i class="feather-life-buoy"></i></span><span class="nxl-mtext">Ticketsystem</span></a></li>
            </ul>
        </div>
    </div>
</nav>
<!--! ================================================================ !-->
<!--! [End]  Navigation Menu !-->
<!--! ================================================================ !-->
<!--! ================================================================ !-->
<!--! [Start] Header !-->
<!--! ================================================================ !-->
<header class="nxl-header">
    <div class="header-wrapper">
        <!--! [Start] Header Left !-->
        <div class="header-left d-flex align-items-center gap-4">
            <!--! [Start] nxl-head-mobile-toggler !-->
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
            <!--! [Start] nxl-head-mobile-toggler !-->
            <!--! [Start] nxl-navigation-toggle !-->
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button">
                    <i class="feather-align-left"></i>
                </a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none">
                    <i class="feather-arrow-right"></i>
                </a>
            </div>
            <!--! [End] nxl-navigation-toggle !-->
        </div>
        <!--! [End] Header Left !-->
        <!--! [Start] Header Right !-->
        <div class="header-right ms-auto">
            <div class="d-flex align-items-center">
                <div class="nxl-h-item d-none d-sm-flex">
                    <div class="full-screen-switcher">
                        <a href="javascript:void(0);" class="nxl-head-link me-0" onclick="$('body').fullScreenHelper('toggle');">
                            <i class="feather-maximize maximize"></i>
                            <i class="feather-minimize minimize"></i>
                        </a>
                    </div>
                </div>
                <div class="dropdown nxl-h-item">
                    <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside">
                        <img src="assets/images/avatar/1.png" alt="user-image" class="img-fluid user-avtar me-0" />
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <img src="assets/images/avatar/1.png" alt="user-image" class="img-fluid user-avtar" />
                                <div>
                                    <h6 class="text-dark mb-0"><?php echo $username; ?></h6>
                                    <span class="fs-12 fw-medium text-muted"><?php echo $username; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="feather-log-out"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!--! [End] Header Right !-->
    </div>
</header>
<!--! ================================================================ !-->
<!--! [End] Header !-->
<!--! ================================================================ !-->

