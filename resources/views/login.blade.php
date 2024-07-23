<!DOCTYPE html>
<html lang="en" class="h-100">
	<!-- All Meta -->
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="keywords" content="bootstrap admin, card, clean, credit card, dashboard template, elegant, invoice, modern, money, transaction, Transfer money, user interface, wallet">
	
	<meta property="og:image" content="../../django/social-image.png">
	<meta name="format-detection" content="telephone=no">
<head>
   
	<!-- Mobile Specific -->
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- favicon -->
	<link rel="shortcut icon" type="image/png" href="{{asset('')}}images/favicon.png">

	<!-- Page Title Here -->
	<title>SuvidhaBnk - </title>
	<link href="{{asset('')}}assets/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
	<link href="{{asset('')}}assets/css/toasting.css" rel="stylesheet" />
	<link href="{{asset('')}}css/style.css" rel="stylesheet">

	
	

</head>

<body class="vh-100">
    <div class="authincation h-100">
        <div class="container-fluid h-100">
            <div class="row h-100">
				<div class="col-lg-6 col-md-12 col-sm-12 mx-auto align-self-center">
					<div class="login-form">
						<div class="text-center">
							<h3 class="title">Sign In</h3>
							<p>Sign in to your account</p>
						</div>
						<form action="{{route('authCheck')}}" method="POST" class="loginForm" >
						{{ csrf_field() }}
							
							<div class="mb-4">
								<label class="mb-1 text-dark">Mobile <span class="text text-danger">*</span></label>
								<input type="text" class="form-control" id="validationCustom01" name="mobile" value="" required>
								
							</div>
							<div class="mb-4 position-relative">
								<label class="mb-1 text-dark">Password <span class="text text-danger">*</span></label>
								<input type="password" id="dlab-password" id="validationCustom03" name="password" class="form-control" value="" required>
								
							</div>
							<div class="form-row d-flex justify-content-between mt-4 mb-2">
								<div class="mb-4">
									<div class="form-check custom-checkbox mb-3">
										<input type="checkbox" class="form-check-input" id="customCheckBox1">
										<label class="form-check-label mt-1" for="customCheckBox1">Remember my preference</label>
									</div>
								</div>
								<div class="mb-4">
									<a href="" class="btn-link text-primary">Forgot Password?</a>
								</div>
							</div>
							<div class="text-center mb-4">
								<button type="submit" class="btn btn-primary btn-block">Sign In</button>
							</div>
							<h6 class="login-title"><span>Or continue with</span></h6>
							
							<div class="mb-3">
								<ul class="d-flex align-self-center justify-content-center">
									<li><a target="_blank" href="https://www.facebook.com/" class="fab fa-facebook-f btn-facebook"></a></li>
									<li><a target="_blank" href="https://www.google.com/" class="fab fa-google-plus-g btn-google-plus mx-2"></a></li>
									<li><a target="_blank" href="https://www.linkedin.com/" class="fab fa-linkedin-in btn-linkedin me-2"></a></li>
									<li><a target="_blank" href="https://twitter.com/" class="fab fa-twitter btn-twitter"></a></li>
								</ul>
							</div>
							<p class="text-center">Not registered?  
								<a class="btn-link text-primary" href="{{url('/register')}}">Register</a>
							</p>
						</form>
					</div>
				</div>
                <div class="col-xl-6 col-lg-6">
					<div class="pages-left h-100">
						<div class="login-content">
							
							
							<p>Your true value is determined by how much more you give in value than you take in payment. ...</p>
						</div>
						<div class="login-media text-center">
							<img src="images/login.png" alt="">
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>


    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>	 
<script src="{{asset('')}}vendor/global/global.min.js"></script>
<script src="{{asset('')}}js/custom.min.js"></script>
<script src="{{asset('')}}js/dlabnav-init.js"></script>
<script src="{{asset('')}}js/demo.js"></script>
<script src="{{asset('')}}js/styleSwitcher.js"></script>
    <!-- Sweet Alerts js -->
<script src="{{asset('')}}assets/sweetalert2/sweetalert2.min.js"></script>
<script src="{{asset('')}}assets/js/sweetalerts.init.js"></script>


<script src="{{asset('')}}assets/js/jquery.form.min.js"></script>
<script src="{{asset('')}}assets/js/jquery.validate.min.js"></script>
<script src="{{asset('')}}assets/js/toasting.js"></script>
<script>
$( document ).ready(function() {
    $( ".loginForm" ).validate({
                rules: {
                    mobile: {
                        required: true,
                        minlength: 10,
                        number : true,
                        maxlength: 11
                    },
                    password: {
                        required: true,
                    }
                },
                messages: {
                    mobile: {
                        required: "Please enter mobile number",
                        number: "Mobile number should be numeric",
                        minlength: "Your mobile number must be 10 digit",
                        maxlength: "Your mobile number must be 10 digit"
                    },
                    password: {
                        required: "Please enter password",
                    }
                },
                errorElement: "p",
                errorPlacement: function ( error, element ) {
                    if ( element.prop("tagName").toLowerCase() === "select" ) {
                        error.insertAfter( element.closest( ".form-group" ).find(".select2") );
                    } else {
                        error.insertAfter( element );
                        $
                    }
                },
                submitHandler: function () {
                    var form = $('.loginForm');
                    form.ajaxSubmit({
                        dataType:'json',
						beforeSubmit: function() {
                             Swal.fire({
                                title: 'Wait!',
                                text: 'Please wait, we are sending your details',
                                onOpen: () => {
                                    Swal.showLoading()
                                },
                                allowOutsideClick: () => !Swal.isLoading()
                            });
                    },
                success:function(data){
                    if(data.status == "Login"){
								Swal.fire({
                                position: "top-end",
                                icon: "success",
                                title: "Successfully logged in",
                                showConfirmButton: !1,
                                timer: 3000,
                                showCloseButton: !0
                            });
							window.location.reload();      
                    }
                },
                error: function(errors) {
                Swal.close();
                    if(errors.status == '400'){
						$('b.errorText').text(errors.responseJSON.status);
						setTimeout(function(){
							$('b.errorText').text('');
						}, 5000);
                    }else{
						$('b.errorText').text('Something went wrong, try again later.');
						setTimeout(function(){
							$('b.errorText').text('');
						}, 5000);
                    }
                }
            });
        }
    });           
});

       
    </script>
</body>


</html>