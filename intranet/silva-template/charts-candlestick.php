<?php include 'services/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php $title = "Candle Stick Charts";
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
                                <h4 class="fs-18 fw-semibold m-0">Candle Stick Charts</h4>
                            </div>
            
                            <div class="text-end">
                                <ol class="breadcrumb m-0 py-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Charts</a></li>
                                    <li class="breadcrumb-item active">Candle Stick Charts</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Candle Stick Charts -->
                        <div class="row">
                            <!-- Basic Candlestick Charts -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Candlestick</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="basic_candlestick_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>

                            <!-- Combo Candlestick Charts -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Combo Candlestick</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="combo_candlestick_chart" class="apex-charts"></div> 
                                        <div id="combo_candlestick_chart1" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div>
                        </div>


                        <div class="row">
                            <!-- Category x-axis Candlestick Charts -->
                            <!-- <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Category x-axis Candlestick Chart</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="category_candlestick_chart" class="apex-charts"></div> 
                                    </div>
                                </div>  
                            </div> -->

                            <!-- Line Candlestick Charts -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Line Candlestick</h5>
                                    </div>

                                    <div class="card-body">
                                        <div id="line_candlestick_chart" class="apex-charts"></div> 
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

        <script src="https://apexcharts.com/samples/assets/ohlc.js"></script>

        <!-- Candlestick Charts Init Js -->
        <script src="assets/js/pages/apexcharts-candlestick.init.js"></script>
        
        <!-- App js-->
        <script src="assets/js/app.js"></script>
        
    </body>
</html>