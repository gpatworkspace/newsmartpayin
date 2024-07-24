@extends('layouts.app')
@section('content')
<div class="content-body">
            <div class="container-fluid">
				<div class="row page-titles">
					<ol class="breadcrumb">
						<li class="breadcrumb-item"><a href="javascript:void(0)">User</a></li>
						<li class="breadcrumb-item active"><a href="javascript:void(0)">Create New</a></li>
					</ol>
                </div>
                <!-- row -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Create User</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-validation">
                                    <form class="needs-validation" novalidate >
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">First Name</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="First Name" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Last Name</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="Last Name" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Email</label><span class="text-danger">*</span>
                                                <input type="email" class="form-control" placeholder="Email" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Phone Number</label><span class="text-danger">*</span>
                                                <input type="number" class="form-control" placeholder="Phone Number" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Address</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="Address" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">State</label><span class="text-danger">*</span>
                                                <select id="inputState" class="default-select form-control wide" required>
                                                    <option selected>Choose State</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">City</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="City" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Zip</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="Zip" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Shop Name</label>
                                                <input type="text" class="form-control" placeholder="Shop Name">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">PAN Number</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="PAN Number" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Aadhar Number</label><span class="text-danger">*</span>
                                                <input type="text" class="form-control" placeholder="Aadhar Number" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Scheme</label><span class="text-danger">*</span>
                                                <select id="inputState" class="default-select form-control wide" required>
                                                    <option selected>Choose Scheme</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox">
                                                <label class="form-check-label">
                                                    I accept terms & conditions
                                                </label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Sign in</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endsection        