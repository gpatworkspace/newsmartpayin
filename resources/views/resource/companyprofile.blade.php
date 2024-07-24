@extends('layouts.app')
@section('content')
    <div class="content-body">
        <div class="container-fluid">
            
            <div class="row page-titles">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">Resources</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Company</a></li>
                </ol>
            </div>
            <!-- row -->


            <!-- row -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card h-auto">
                        <div class="card-body">
                            <div class="profile-tab">
                                <div class="custom-tab-1">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item"><a href="#profile" data-bs-toggle="tab" class="nav-link active show">Company Details</a></li>
                                        <li class="nav-item"><a href="#logo" data-bs-toggle="tab" class="nav-link">Company Logo</a></li>
                                        <li class="nav-item"><a href="#news" data-bs-toggle="tab" class="nav-link">Company News</a></li>
                                        <li class="nav-item"><a href="#notice" data-bs-toggle="tab" class="nav-link">Company Notice</a></li>
                                        <li class="nav-item"><a href="#support" data-bs-toggle="tab" class="nav-link">Company Support Details</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        <div id="profile" class="tab-pane fade active show">
                                            <div class="pt-3">
                                                <div class="settings-form">
                                                    <h4 class="text-primary">Company Information</h4>
                                                    <form>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label">Company Name</label>
                                                                <input type="text" name="companyname" class="form-control" value="" required="" placeholder="Enter Value">
                                                            </div>
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label">Company Website</label>
                                                                <input type="text" name="website" class="form-control" value="" required="" placeholder="Enter Value">
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-success light" type="submit"><i class="fa fa-paper-plane"></i> Update Info</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="logo" class="tab-pane fade">
                                            <div class="pt-3">
                                                <div class="cm-content-body publish-content form excerpt">
                                                    <div class="card-body">
                                                        <div class="avatar-upload d-flex align-items-center">
                                                            <div class=" position-relative ">
                                                                <div class="avatar-preview">
                                                                    <div id="imagePreview" style="background-image: url({{asset('')}}images/no-img-avatar.png);"> 			
                                                                    </div>
                                                                </div>
                                                                <form id="logoupload" action="{{route('resourceupdate')}}" method="post" enctype="multipart/form-data">
                                                                    <div class="change-btn d-flex align-items-center flex-wrap">
                                                                    {{ csrf_field() }}
                                                                        <input type="hidden" name="actiontype" value="company">
                                                                        <input type="hidden" name="id" value="">    
                                                                        <input type='file' class="form-control d-none"  id="imageUpload" accept=".png, .jpg, .jpeg">
                                                                        <label for="imageUpload" class="btn btn-light ms-0">Select Image</label>
                                                                    </div>
                                                                </form>
                                                                <small class="text-danger">Note : Prefered image size is 260px * 56px</small>
                                                            </div>		
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div id="news" class="tab-pane fade">
                                            <div class="pt-3">
                                                <div class="settings-form">
                                                    <form id="newsForm" action="{{route('resourceupdate')}}" method="post">                                                    
                                                        {{ csrf_field() }}
                                                        <input type="hidden" name="id" value="">
                                                        <input type="hidden" name="company_id" value="">
                                                        <input type="hidden" name="actiontype" value="companydata">
                                                        <div class="row">
                                                            <div class="mb-3 col-xl-6 col-lg-6 col-md-6 col-sm-4 col-12">
                                                                <div class="form-group">
                                                                    <label for="inputName">News</label>
                                                                    <textarea name="news" class="form-control" cols="30" rows="3" placeholder="Enter News"></textarea>
                                                                </div>
                                                            </div>                                    
                                                            <div class="mb-3 col-xl-6 col-lg-6 col-md-6 col-sm-4 col-12">
                                                                <div class="form-group">
                                                                    <label for="inputName">Bill Notice</label>
                                                                    <textarea name="billnotice" class="form-control" cols="30" rows="3" placeholder="Enter News"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-success light" type="submit"><i class="fa fa-paper-plane"></i> Update Info</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="notice" class="tab-pane fade">
                                            <div class="pt-3">
                                                <div class="settings-form">
                                                    <form id="noticeForm" action="{{route('resourceupdate')}}" method="post">
                                                        <div class="card-body">
                                                            {{ csrf_field() }}
                                                            <input type="hidden" name="id" value="">
                                                            <input type="hidden" name="company_id" value="">
                                                            <input type="hidden" name="actiontype" value="companydata">
                                                            <input type="hidden" name="notice">
                                                            <div class="custom-ekeditor">
                                                                <div id="ckeditor"></div>
                                                            </div>
                                                        </div>
                                                            <button class="btn btn-success light" type="submit"><i class="fa fa-paper-plane"></i> Update Info</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="support" class="tab-pane fade">
                                            <div class="pt-3">
                                                <div class="settings-form">
                                                    <form id="supportForm" action="{{route('resourceupdate')}}" method="post">
                                                            {{ csrf_field() }}
                                                            <input type="hidden" name="id" value="">
                                                            <input type="hidden" name="company_id" value="">
                                                            <input type="hidden" name="actiontype" value="companydata">
                                                            <input type="hidden" name="notice">
                                                            <div class="row">
                                                                <div class="mb-3 form-group col-md-6">
                                                                    <label>Contact Number</label>
                                                                    <textarea name="number" class="form-control" cols="30" rows="3" placeholder="Enter Value" required=""></textarea>
                                                                </div>

                                                                <div class="mb-3 form-group col-md-6">
                                                                    <label>Contact Email</label>
                                                                    <textarea name="email" class="form-control" cols="30" rows="3" placeholder="Enter Value" required=""></textarea>
                                                                </div>
                                                            </div>
                                                            <button class="btn btn-success light" type="submit"><i class="fa fa-paper-plane"></i> Update Info</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{asset('')}}vendor/ckeditor/ckeditor.js"></script>
@endsection