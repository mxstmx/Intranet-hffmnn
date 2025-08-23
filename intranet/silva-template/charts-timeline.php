<?php include 'services/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
    <head>

        <?php $title = "Timeline Charts";
        include 'partials/title-meta.php'; ?>

        <?php include 'partials/head-css.php'; ?>

    </head>

    <?php include 'partials/body.php'; ?>

        <!-- Begin page -->
        <div id="app-layout">

            <?php include 'partials/menu.php'; ?>

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->
        
            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">

                        <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-18 fw-semibold m-0">Timeline Charts</h4>
                            </div>
            
                            <div class="text-end">
                                <ol class="breadcrumb m-0 py-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Charts</a></li>
                                    <li class="breadcrumb-item active">Timeline Charts</li>
                                </ol>
                            </div>
                        </div>


                        <!-- Timeline Charts -->
                        <div class="row">
                            <!-- Basic Timeline Chart -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Timeline Chart</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="basic_timeline_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                            <!-- Distributed Timeline Chart -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Distributed Timeline</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="distributed_timeline_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                            <!-- Multi-Series Timeline Chart -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Multi Series Timeline</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="multi_timeline_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                            <!-- Advanced Timeline Chart -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Advanced Timeline</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="advanced_timeline_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                            <!-- Multiple series – Group rows Chart -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Multiple Series Group Rows Timeline</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="group_timeline_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                            <!-- Dumbbell (Horizontal) Chart -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Dumbbell Horizontal Timeline</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="dumbbell_timeline_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                        </div>
                        
                    </div> <!-- container-fluid -->

                </div> <!-- content -->

                <?php include 'partials/footer.php'; ?>

            </div>
            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        <?php include 'partials/vendor.php'; ?>

        <!-- Apexcharts JS -->
        <script src="assets/libs/apexcharts/apexcharts.min.js"></script>

        <!-- Moment Js -->
        <script src="assets/libs/moment/min/moment.min.js"></script>

        <!-- Mixed Chart Init Js -->
        <script src="assets/js/pages/apexcharts-timeline.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.js"></script>
        
    </body>
</html>