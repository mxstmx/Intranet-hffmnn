<?php include 'services/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
    <head>

        <?php $title = "Auth Confirm Mail";
        include "partials/title-meta.php"; ?>

        <?php include 'partials/head-css.php'; ?>

    </head>
    
    <body class="bg-primary-subtle">

        <!-- Begin page -->
        <div class="account-page">
            <div class="container-fluid p-0">
                <div class="row align-items-center g-0">

                    <div class="col-md-5">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card p-3 mb-0 mb-0">
                                    <div class="card-body">
                                        <div class="mb-0 border-0 p-md-5 p-lg-0 p-4">

                                            <div class="mb-4 p-0 text-center">
                                                <a href="index.php" class="auth-logo">
                                                    <img src="assets/images/logo-dark.png" alt="logo-dark" class="mx-auto" height="28" />
                                                </a>
                                            </div>

                                            <div class="auth-title-section mb-3 text-center mt-2">
                                                <h3 class="text-dark fs-20 fw-medium mb-2">Email Confirmation</h3>
                                                <p class="text-muted fs-15">Please check your email for confirmation mail. <br>Click link in email to verification your account</p>
                                            </div>

                                            <div class="text-center">
                                                <a href="#" class="btn btn-primary mt-3 me-1" type="submit">Resend Confirmation</a>
                                            </div>

                                            <div class="text-center pt-4">
                                                <div class="maintenance-img">
                                                    <img src="assets/images/svg/confirmation-email.svg" height="200" alt="svg-logo">
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-7">
                        <div class="account-page-bg p-md-5 p-4">
                            <div class="text-center">
                                <div class="auth-image">
                                    <img src="assets/images/auth-images.svg" class="mx-auto img-fluid"  alt="images">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- END wrapper -->

        <?php include 'partials/vendor.php'; ?>

        <!-- App js-->
        <script src="assets/js/app.js"></script>
        
    </body>
</html>