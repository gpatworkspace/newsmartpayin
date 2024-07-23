@extends('layouts.app')
@section('content')
        <div class="content-body">
            <div class="container-fluid">
				
				<div class="row page-titles">
					<ol class="breadcrumb">
						<li class="breadcrumb-item active"><a href="javascript:void(0)">Table</a></li>
						<li class="breadcrumb-item"><a href="javascript:void(0)">Datatable</a></li>
					</ol>
                </div>
                <!-- row -->


                <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Patient</h4>
                                <button type="button" class="btn btn-rounded btn-primary"  data-bs-toggle="modal" data-bs-target="#basicModal"><span
                                        class="btn-icon-start text-primary"><i class="fa fa-plus color-info"></i></a></span>Add</button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example5" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>
													<div class="form-check custom-checkbox ms-2">
														<input type="checkbox" class="form-check-input" id="checkAll" required="">
														<label class="form-check-label" for="checkAll"></label>
													</div>
												</th>
                                                <th>Patient ID</th>
                                                <th>Date Check in</th>
                                                <th>Patient Name</th>
                                                <th>Doctor Assgined</th>
                                                <th>Disease</th>
                                                <th>Status</th>
                                                <th>Room no</th>
                                                <th>Action</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
													<div class="form-check custom-checkbox ms-2">
														<input type="checkbox" class="form-check-input" id="customCheckBox2" required="">
														<label class="form-check-label" for="customCheckBox2"></label>
													</div>
												</td>
                                                <td>#P-00001</td>
                                                <td>26/02/2020, 12:42 AM</td>
                                                <td>Tiger Nixon</td>
                                                <td>Dr. Cedric</td>
                                                <td>Cold & Flu</td>
												<td>
													<span class="badge light badge-danger">
														<i class="fa fa-circle text-danger me-1"></i>
														New Patient
													</span>
												</td>
                                                <td>AB-001</td>
                                                <td>
													<div class="dropdown ms-auto text-end">
														<div class="btn-link" data-bs-toggle="dropdown">
															<svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"></rect><circle fill="#000000" cx="5" cy="12" r="2"></circle><circle fill="#000000" cx="12" cy="12" r="2"></circle><circle fill="#000000" cx="19" cy="12" r="2"></circle></g></svg>
														</div>
														<div class="dropdown-menu dropdown-menu-end">
															<a class="dropdown-item" href="#">Accept Patient</a>
															<a class="dropdown-item" href="#">Reject Order</a>
															<a class="dropdown-item" href="#">View Details</a>
														</div>
													</div>
												</td>
                                                <td>
													<div class="d-flex">
														<a href="#" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
														<a href="#" class="btn btn-danger shadow btn-xs sharp"><i class="fa fa-trash"></i></a>
													</div>												
												</td>													
                                            </tr>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>

<!-- Modal -->
<div class="modal fade" id="basicModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body">Modal body text goes here.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>
@endsection