@extends('layouts.app')

@section('content')

<div class="container-fluid mt-4">
    <!-- Palitan ang panimulang tag ng ginawa nating sticky container nito: -->
<div class="sticky-top bg-white pt-2 pb-3" style="top: 70px; z-index: 1; box-shadow: 0 4px 6px -6px #222; border-radius:5px; padding:8px;">

    <h3 class="mb-3">My Leads</h3>
     @if (in_array(auth()->user()->position_id, [13, 237, 158]))
         <button type="button" class="btn btn-success d-inline-flex align-items-center justify-content-center px-4 text-white shadow-sm" id="bulkAssignBtn" style="height: 38px;">
           <i class="fas fa-user-plus"></i> &nbsp; Assign PSC
         </button>

          <button type="button" class="btn btn-danger d-inline-flex align-items-center justify-content-center px-4 text-white shadow-sm" id="bulkRemoveBtn" style="height: 38px;">
           <i class="fas fa-user-alt-slash"></i> &nbsp;Remove PSC
         </button>
     @endif

<!-- For Filter by Exhibit or Source -->
<form action="{{ route('AsssignedContact')}}" method="GET" class="mb-3">
    <div class="row align-items-end">
        <div class="col-md-4">
            <label for="exhibit_filter" class="form-label">Filter by Source:</label>
            <select name="exhibit_filter" id="exhibit_filter" class="form-control" onchange="this.form.submit()">
                <option value="">-- All Source --</option>
                @foreach($exhibits as $exhibit)
                    <option value="{{ $exhibit }}" {{ $selectedExhibit == $exhibit ? 'selected' : '' }}>
                        {{ $exhibit }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <a href="{{ route('AsssignedContact') }}" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>
    </div>
    <!-- Ending of sticky-top -->

<!-- Ending of Filter -->
    <div id="summary" class="mb-3 text-muted"></div>

    <!-- Start of Card -->
<!-- 1. PARENT WRAPPER: Flex container na nag-aalaga sa pantay na agwat at pagbaba ng hilera -->
<div class="d-flex flex-wrap justify-content-start align-items-stretch p-4" style="gap: 25px; z-index: -1;">
    
    @foreach($companies as $companyData)
        <!-- 2. MAIN CARD: Tinanggal ang panggulong 'col' classes para hindi mag-clash ang width -->
        <div class="main-container bg-white shadow-sm p-2 border rounded d-flex flex-column" style="width: 330px; height: auto; font-size: 0.75rem !important; flex-shrink: 0;">
                    
                    
                    <div class="company-source {{ $companyData['exhibit_name']=='PhilConstruct' ? 'source-blue' : 'source-pink' }}">
                        <span> 
                            <label class="font-weight-bold">Source:</label> 
                            {{ $companyData['exhibit_name'] }} 
                        </span>  
                      </div>

                     <!--Ending of Div Source  -->
            <!-- 1. HEADER SECTION (Fixed Height) -->
            <div class="company-header-card {{ $companyData['exhibit_name'] == 'Inquiry' ? 'header-inquiry' : 'header-philconstruct' }}
    p-3 rounded-top d-flex justify-content-between align-items-center mb-2" style="height: auto;">
                   
                <div class="text-break w-100" >
                    <h6 class="fw-bold m-0 text-uppercase tracking-wide" style="color:whitesmoke;font-size: 0.90rem;" title="{{ $companyData['company_name'] }}">
                        {{ $companyData['company_name'] }}
                    </h6>
                  
                    <small class="text-white d-block  text-break w-100" style="white-space: normal !important;">
                        <i class="fas fa-map-marker-alt address-icon text-success h6"></i>  
                        {{ $companyData['address'] }} 
                        <i class="far fa-edit ms-1" style="font-size: 0.7rem; cursor:pointer;" id="UpdateAddressModal" 
                        data-caddress="{{ $companyData['address'] }}"
                        data-company_name="{{ $companyData['company_name']}}"
                        data-company_id="{{ $companyData['company_id']}}"
                        ></i>
                    </small>

                 

                    
                </div>
               <!--  <i class="fas fa-building fs-4 text-muted-custom flex-shrink-0 h3 text-white"></i> -->
                        <div class="company-icon">
                        <i class="fas fa-building"></i>
                        </div>
            </div>

            <!-- 2. LEAD PIPELINE SECTION (Fixed Height) -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold">Lead Pipeline</span> 
                    <span class="text-muted" style="font-size: 0.7rem;">[ {{ $companyData['status_percentage'] }} ]</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar progress-bar-custom" role="progressbar" style="width: {{ $companyData['status_percentage'] }};"></div>
                </div>
                 @if (in_array(auth()->user()->position_id, [13, 237,158]))
                <div class="form-check m-0 ">
                    <input class="form-check-input border-danger mt-0 participant_checkbox" type="checkbox" id="checkAssign_{{ $loop->index }}" value="{{$companyData['company_id']}}">
                    <label class="form-check-label text-danger fw-medium d-inline-flex align-items-center justify-content-center" style="cursor:pointer;" for="checkAssign_{{ $loop->index }}">
                        Change/Remove PSC
                    </label>
                </div>
                @endif
            </div>

            <!-- 3. COMPANY CONTACTS SECTION WITH STRICT SCROLL HEIGHT -->
            <div class="border p-2 rounded mb-2 d-flex flex-column" style="height: 220px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold"><i class="fas fa-building me-1"></i> Company Contacts</span>
                    <button class="btn btn-outline-secondary btn-sm px-3">Add</button>
                </div>

                <!-- FIXED HEIGHT SCROLL BOX -->
                <div class="contact-scroll-box bg-light flex-grow-1" style="height: 170px; overflow-y: auto;">
                    @if(count($companyData['contacts']) > 0)
                        @foreach($companyData['contacts'] as $index => $contact)
                            <div class="contact-card">
                                <span class="text-muted d-block mb-1" style="font-size: 0.65rem;">Contact Person {{ $index + 1 }}</span>
                                <span class="font-weight-bold d-block fst-italic mb-1 text-truncate contact-name" >{{ $contact['name'] }}</span>
                                
                                <div class="text-secondary lh-sm text-truncate" style="font-size: 0.7rem;">
                                    <div class="mb-1 text-truncate"><i class="fas fa-phone-alt text-danger me-1"></i> {{ $contact['phone'] }}</div>
                                    <div class="mb-1 text-truncate"><i class="fas fa-envelope text-danger me-1"></i> {{ $contact['email'] }}</div>
                                </div>
                                
                                <div class="text-end mt-1 text-right">
                                    <button class="btn btn-outline-dark btn-sm" id="UpdateContact" data-id="{{ $contact['id'] }}" data-contact="{{ $contact['phone'] }}" data-email="{{ $contact['email'] }}" data-name="{{ $contact['name'] }}">
                                        <i class="fas fa-pencil-alt me-1"></i>Edit
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4 my-auto">No Contact Persons</div>
                    @endif
                </div>
            </div>

            <!-- 4. AGENT DETAILS SECTION (Fixed Height sa Pinakababang Bahagi) -->
            <div class="agent-section bg-white mt-auto border border-success  rounded" style="height: auto;">
                <div class="d-flex justify-content-between align-items-center border rounded p-2" style="background-color:#fef1cf;">
                    <span class="font-weight-bold">Agent Details</span>
                   
                            @if(auth()->check() && $companyData['assigned_agent_id'] == auth()->user()->emp_id)
                                    <button class="btn btn-outline-success btn-sm btnUpdateStatus" 
                                            id="UpdateStatus"
                                            data-id="{{ $companyData['company_id'] }}"
                                            data-cname="{{ $companyData['company_name'] }}"
                                            data-lead_status="{{ $companyData['ContactUpdate'] }}"
                                            data-remarks="{{ $companyData['UpdateRemarks'] }}"
                                            >
                                        <i class="fas fa-pencil-alt me-1"></i> &nbsp;Update Status 
                                    </button>
                                @endif
                           


                </div>

                <div class="d-flex align-items-center mb-2 border  border-secondary p-2">
                                        @php
                        $nameParts = explode(' ', trim($companyData['AgentName']));
                        $initials = strtoupper(substr($nameParts[0],0,1));

                        if(count($nameParts) > 1){
                            $initials .= strtoupper(substr(end($nameParts),0,1));
                        }
                    @endphp

                    <div class="agent-avatar">
                        {{ $initials }}
                    </div>
                    <div class=" text-truncate">
                        <span class="font-weight-bold d-block m-0 text-uppercase text-truncate" style="font-size: 0.75rem;">
                           &nbsp; {{ $companyData['AgentName'] }}
                        </span>
                        
                    </div>
                </div>

                <!-- Fixed Height Update Inner Box -->
                <div class="update-box p-1 " style="height: auto;">
                    <div class="bg-white p-2 rounded border shadow-sm h-100" style="overflow: hidden;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                                @if($companyData['lead_status']=='No Update Yet')
                            <span class="badge bg-danger text-white py-0.5 px-1.5" style="font-size: 0.6rem;">
                                {{ $companyData['lead_status'] }}
                            </span>
                            @else 
                             <span class="badge bg-success text-white py-0.5 px-1.5" style="font-size: 0.6rem;">
                                {{ $companyData['lead_status'] }}
                            </span>
                            
                            @endif

                            

                            <small class="text-muted" style="font-size: 0.6rem;">
                                @if($companyData['UpdateTime'] && $companyData['UpdateTime'] !== '--')
                                    {{ \Carbon\Carbon::parse($companyData['UpdateTime'])->format('M d, Y h:i A') }}
                                @else
                                    No Update Yet
                                @endif
                            </small>
                        </div>
                        <p class="m-0 text-dark fst-italic lh-sm text-wrap" style="font-size: 0.7rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $companyData['UpdateRemarks'] }}
                        </p>
                            <br>
                           <!--  <a href="{{ asset('storage/' . $companyData['file_path']) }}" target="_blank" class="btn btn-outline-success btn-sm"> -->
                            <!-- <i class="fas fa-file-download"></i>  -->
                            <button class="btn btn-outline-success btn-sm view-files-btn" data-companyid="{{ $companyData['company_id'] }}"><i class=" fas fa-file-pdf" style="color:crimson;"></i>&nbsp;View Files</button>
                            &nbsp;
                             <!-- </a> -->
                            
                        

                    </div>
                </div>
            </div>

        </div> <!-- WAKAS NG MAIN CONTAINER -->
    @endforeach

</div>
 <!-- WAKAS NG PARENT WRAPPER -->

<!-- Ending of Card -->

</div>
<!-- Ending of container mt-4 -->

 <!-- Modal to View Files -->
  <div class="modal fade" id="filesModal" tabindex="-1" aria-labelledby="filesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filesModalLabel"><i class="fas fa-folder-open text-warning"></i> Company Uploaded Files</h5>
               <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; color: #000; cursor: pointer;">
    <span aria-hidden="true">&times;</span>
</button>

            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped w-100" id="filesTable" >
                    <thead class="table-dark" >
                        <tr>
                            <th style="font-size:10px;">File Name</th>
                            <th style="font-size:10px;">Uploaded By</th>
                            <th style="font-size:10px;">Date Uploaded</th>
                            <th style="font-size:10px;" class="text-center" >Action</th>
                        </tr>
                    </thead>
                    <tbody style="font-size:10px;">
                        <!-- Dito papasok ang mga files gamit ang jQuery -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ending of Modal to View Files -->



<!-- For Carousel Modal -->
<div class="modal fade" id="imageCarouselModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-body">

                <div id="participantCarousel" class="carousel slide" data-ride="carousel">

                    <div class="carousel-inner" id="carouselImages">
                        <!-- Images will be inserted here via JS -->
                    </div>

                    <a class="carousel-control-prev" href="#participantCarousel" data-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </a>

                    <a class="carousel-control-next" href="#participantCarousel" data-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </a>

                </div>

            </div>

        </div>
    </div>
</div>
<!-- Ending of Carousel Modal -->

<!-- MOdal for Assigning of PSC -->
 <div class="modal fade" id="assignModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                    <h5>Assign PSC</h5>
                    </div>

                    <div class="modal-body">
                    <select id="psc_id" class="form-control">
                   @foreach($user_group as $user)
                        @php
                            $fullName   = $user->first_name . ' ' . $user->last_name;
                            $currentUrl = request()->fullUrl();
                        @endphp

                            @if(request()->is('*AssignedContact*'))
                                    <option value="{{$user->emp_id}}">
                                        {{$fullName}}
                                    </option>
                                @else
                                    <option value="{{$fullName}}">
                                        {{$fullName}}
                                    </option>
                                @endif
                        @endforeach
                    </select>
                    </div>

                    <div class="modal-footer">
                    <button class="btn btn-primary" id="confirmAssign">
                    Save Assignment
                    </button>
                    </div>

                </div>
            </div>
</div>
 <!-- Ending of Modal Assigning of PSC -->


 <!-- Modal to update the Status of Attendees -->
 <div class="modal fade" id="statusModal">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">You are Updating the status of</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; color: #000; cursor: pointer;">
    <span aria-hidden="true">&times;</span>
</button>

      </div>

      <div class="modal-body">
        <form id="yourFormId" enctype="multipart/form-data">

        <input type="hidden" id="company_id">
         <p id="CompanyName" class="text-white p-2" style="background-color:#1e1f71; color:whitesmoke;"></p>   
        <div class="mb-3">
            <label>Select Activity</label>
            <select class="form-control" id="lead_status">
                     <option value="">-- Select Category --</option>
                    @foreach($lead_agent_status as $lead_status)
                        <option value="{{ $lead_status->id }}" data-description="{{ $lead_status->description }}">
                            {{ $lead_status->lead_status }}
                        </option>
                    @endforeach
            </select>
        </div>

        <div class="mb-3" id="signedProposalFileWrapper" style="display:none;">
            <label>Upload Signed Proposal</label>
            <input type="file" class="form-control" id="signed_proposal_file" multiple>
        </div>

        <div class="mb-3" id="customerCodeWrapper" style="display:none;">
            <label>Customer Code</label>
            <input type="text" class="form-control" id="customer_code">
        </div>


        <div class="mb-3">
            <label>Description</label>
            <textarea class="form-control" id="description"></textarea>
        </div>

        

      </div>
      <!-- Ending of modal-body  -->

      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="saveStatus">
            Save
        </button>
      </div>
</form>

    </div>
  </div>
</div>

<!-- Ending of Modal Update Status Attendees -->


 <!-- Modal to update Address Modal -->
 <div class="modal fade" id="AddressModal">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Updating Address Form</h5>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; color: #000; cursor: pointer;">
      </div>

      <div class="modal-body">
        <input type="hidden" id="company_id">
         <p id="company_name" class="bg bg-success text-white p-2"></p>   
        <div class="mb-3">
            <label>Input Addres</label>
            <textarea class="form-control" id="ContactAddress"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="saveAddress">
            Save
        </button>
      </div>

    </div>
  </div>
</div>
<!-- Ending of Modal Update Address Modal -->




<!-- Modal to update Contact Person Details Modal -->
 <div class="modal fade" id="ContactPersonUpdateModal">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Update Contact Details Form</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; color: #000; cursor: pointer;">
    <span aria-hidden="true">&times;</span>
</button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="p_id">
         <p id="contact_company_name" class="bg bg-success text-white p-2"></p>   
    

        <div class="mb-3">
            <label>Fullname</label>
            <input type="text" class="form-control" id="participant_name">
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="text" class="form-control" id="participant_email">
        </div>

        <div class="mb-3">
            <label>Contact#</label>
            <input type="text" class="form-control" id="participant_contact">
        </div>

        


      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="SaveContactUpdates">
            Save
        </button>
      </div>

    </div>
  </div>
</div>
<!-- Ending update Contact Person Details Modal -->

<style>
.participant_checkbox{
    appearance: none;
    -webkit-appearance: none;
    width:18px;
    height:18px;
    border:2px solid red;
    border-radius:3px;
    background-color:white;
    cursor:pointer;
}
.participant_checkbox:checked{
    background-color:red;
}
.img-thumbnail{
    object-fit: cover;
}
</style>

<script>
// Removal of Assigned PSC

/* $('#bulkRemoveBtn').click(function() {
    // Kunin ang lahat ng checked o napiling company_id
    var selectedCompanies = [];
    $('.participant_checkbox:checked').each(function() {
        selectedCompanies.push($(this).val());
    });

    if (selectedCompanies.length === 0) {
        alert('Mangyaring pumili muna ng kumpanya na aalisin.');
        return;
    }

    // Gagamit ng prompt sa halip na simpleng confirm box
    var confirmationInput = prompt('BABALA: Nais mo bang alisin ang PSC sa mga napiling kumpanya?\n\nI-type ang salitang "REMOVE" sa ibaba para kumpirmahin:');

    // Kung pinindot ang Cancel o walang nilagay, hihinto ang script
    if (confirmationInput === null) {
        return; 
    }

    // I-validate kung tugma ang tinype (ginamitan ng .trim() para iwas error sa accidental space)
    if (confirmationInput.trim() !== 'REMOVE') {
        alert('Maling confirmation word. Hindi itinuloy ang pag-alis.');
        return;
    }

    // Kung tama ang tinype, tutuloy sa AJAX sa ibaba
    $.ajax({
        url: "{{ route('psc.bulk-remove') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            company_ids: selectedCompanies
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                location.reload(); 
            } else {
                alert('May nagpanggap na error: ' + response.message);
            }
        },
        error: function() {
            alert('Hindi matapos ang request. Subukan muli.');
        }
    });
}); */

$('#bulkRemoveBtn').click(function() {
    // Kunin ang lahat ng checked o napiling company_id
    var selectedCompanies = [];
    $('.participant_checkbox:checked').each(function() {
        selectedCompanies.push($(this).val());
    });

    if (selectedCompanies.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Babala',
            text: 'Mangyaring pumili muna ng kumpanya na aalisin.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // SweetAlert Input Prompt para sa "REMOVE" confirmation
    Swal.fire({
        title: 'Sigurado ka ba?',
        text: 'Nais mo bang alisin ang PSC sa mga napiling kumpanya? I-type ang "REMOVE" para kumpirmahin:',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'I-type ang REMOVE dito...',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Alisin',
        cancelButtonText: 'Kanselahin',
        inputValidator: (value) => {
            if (!value) {
                return 'Kailangan mong mag-input ng confirmation word!';
            }
            if (value.trim() !== 'REMOVE') {
                return 'Maling salita! Dapat ay "REMOVE" sa malalaking titik.';
            }
        }
    }).then((result) => {
        // Kung tama ang input at pinindot ang 'Alisin' button
        if (result.isConfirmed) {
            
            // Magpakita ng loading spinner habang nag-a-ajax
            Swal.fire({
                title: 'Nagpoproseso...',
                text: 'Mangyaring maghintay habang inaalis ang PSC.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('psc.bulk-remove') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    company_ids: selectedCompanies
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tagumpay!',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            location.reload(); // I-reload ang page matapos i-click ang OK
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hindi matapos ang request. Subukan muli.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    });
});



    



//Jquery to view files
$('.view-files-btn').click(function() {
    var companyId = $(this).data('companyid');
    var tbody = $('#filesTable tbody');
    
    // Linisin muna ang table at magpakita ng loading text
    tbody.html('<tr><td colspan="4" class="text-center">Loading files, please wait...</td></tr>');
    
    // Buksan ang modal
    $('#filesModal').modal('show');
    
    // Patakbuhin ang AJAX request
    $.ajax({
        url: "/company/files/" + companyId,
        type: "GET",
        dataType: "JSON",
        success: function(data) {
            tbody.empty(); // Alisin ang loading text
            
            if (data.length === 0) {
                tbody.html('<tr><td colspan="4" class="text-center text-muted">No files uploaded for this company.</td></tr>');
                return;
            }
            
            // I-loop ang bawat file na nanggaling sa controller query
            $.each(data, function(index, file) {
                // Siguraduhing tama ang storage path helper link mo sa windows/laragon
                var fileUrl = "{{ asset('storage') }}/" + file.file_path;
                var uploadedDate = file.uploaded_at ? new Date(file.uploaded_at).toLocaleDateString() : '--';
                var uploaderName = file.uploaded_by_name ? file.uploaded_by_name : 'System';

                var row = `<tr>
                    <td><i class="fas fa-file-pdf text-danger text-secondary me-2"></i>
                    
                    &nbsp;<a href="${fileUrl}" target="_blank">${file.file_name} </a></td>
                    <td>${uploaderName}</td>
                    <td>${uploadedDate}</td>
                    <td class="text-center">
                        <a href="${fileUrl}" target="_blank">
                            <i class="fas fa-eye" title="Open"></i> 
                        </a>
                    </td>
                </tr>`;
                
                tbody.append(row);
            });
        },
        error: function(xhr) {
            tbody.html('<tr><td colspan="4" class="text-center text-danger">Failed to fetch files. Please try again.</td></tr>');
        }
    });
});





//Use to update status of attendees
$('#saveStatus').click(function(){

    var id            = $('#company_id').val();
    var lead_status   = $('#lead_status').val();
    var description   = $('#description').val();
    var files         = $('#signed_proposal_file')[0].files;
    var customer_code = $('#customer_code').val();

    // Required only if status = 9
    if (lead_status == 9 && files.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'File Required',
            text: 'Please upload the signed proposal file.'
        });
        return;
    }

     if (lead_status == 10 && !customer_code) {
            Swal.fire({
                icon: 'warning',
                title: 'Customer Code Required',
                text: 'Please input the customer code'
            });
            return;
        }

    let formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('status', lead_status);
    formData.append('description', description);
    formData.append('customer_code', customer_code);

    // Attach files only if exists
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    $.ajax({
    
        url: "/AssignedContact/update-status/" + id,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response){

            $('#company_id').val('');
            $('#lead_status').val('').trigger('change');
            $('#description').val('');
            $('#customer_code').val('');
            $('#signed_proposal_file').val('');
            $('#signedProposalFileWrapper').hide();
            $('#customerCodeWrapper').hide();


            $('#statusModal').modal('hide');

            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Successfully Updated the lead status.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-center'
            });

           // loadCompanies();
                        setTimeout(function()
                        {
                         window.location.reload();
                        }, 2000);
        },
            error: function(xhr) {
                // Kung validation error (Status 422)
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    $.each(errors, function(key, value) {
                        errorMsg += value[0] + '<br>';
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMsg
                    });
                } else {
                    // Kung Server Error (Status 500 mula sa try-catch)
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: xhr.responseJSON.error || 'Something went wrong.'
                    });
                }
            }
    });

});

//Use to open the modal of Updating status
    $(document).on('click', '#UpdateStatus', function(){
//alert('Test');
 var id            = $(this).data('id');
 var cname         = $(this).data('cname');
 var lead_status   = $(this).data('lead_status');
 var StatusRemarks = $(this).data('remarks');

console.log(id)
    $('#CompanyName').text(cname);
    $('#company_id').val(id);
    $('#lead_status').val(lead_status);
    $('#description').val(StatusRemarks);

    
    $('#statusModal').modal('show');
    });

//Use to open the Modal Updating Contact Person Form
//By Clicking the Edit BUtton per Contact
$(document).on('click', '#UpdateContact', function(){

    var p_id        = $(this).data('id');
    var companyname = $(this).data('companyname');
    var name        = $(this).data('name');
    var email       = $(this).data('email');
    var contact     = $(this).data('contact');

    console.log(companyname);
    //alert(company_name)

    $('#contact_company_name').text(companyname);
    $('#p_id').val(p_id);
    $('#participant_name').val(name);
    $('#participant_email').val(email);
    $('#participant_contact').val(contact);

    
    $('#ContactPersonUpdateModal').modal('show');
});






$('#lead_status').on('change', function () {
    let selected_lead_status = $(this).val();
console.log(selected_lead_status);
    if (selected_lead_status == 9) {
        $('#signedProposalFileWrapper').show();
        $('#signed_proposal_file').prop('required', true);
    } 
    else {
        $('#signedProposalFileWrapper').hide();
        $('#signed_proposal_file').prop('required', false);
        $('#signed_proposal_file').val('');
    }

    if (selected_lead_status == 10) {
        $('#customerCodeWrapper').show();
        $('#customer_code').prop('required', true);
    } 
    else {
        $('#customerCodeWrapper').hide();
        $('#customer_code').prop('required', false);
        $('#customer_code').val('');
    }

    
});



//Ginamit ko ito para ma auto fill yung text area
document.getElementById('lead_status').addEventListener('change', function() {
    let selectedOption = this.options[this.selectedIndex];
    let description = selectedOption.getAttribute('data-description');

    document.getElementById('description').value = description ?? '';
});


//Use to Save Assigned PSC
$('#confirmAssign').click(function(){

    let selected = [];

    $('.participant_checkbox:checked').each(function(){
        selected.push($(this).val());
    });

    let psc_id = $('#psc_id').val();

    $.ajax({
        url: '/Attendance/bulk-assign',
        type: 'POST',
        data:{
            _token  : "{{ csrf_token() }}",
            attendee: selected,
            psc_id  : psc_id
        },
           success: function() {
                Swal.fire({
                    icon             : 'success',
                    title            : 'Saved!',
                    text             : 'Successfully Assigned Agent.',
                    timer            : 2000,
                    showConfirmButton: false,
                    toast            : true,
                    position         : 'top-center'
                }).then(() => {
                    // MAAARI MO ITONG ILAGAY DITO PARA MAG-RELOAD MATAPOS ANG TIMER (2000ms)
                    location.reload();
                });

                $('#assignModal').modal('hide');
                
                // KUNG GUSTO MO NAMANG MAG-RELOAD AGAD NANG HINDI HINAHANTAY ANG TIMER:
                // location.reload();
            }

    });

});


$(document).on('click','.btnAddContact', function(){

    let companyId = $(this).data('id');

    //window.location.href ="http://127.0.0.1:8000/participant/create?company_id=" + companyId;
     window.location.href = "/participant/create?company_id=" + companyId;

});


$('#bulkAssignBtn').click(function(){

    let selected = [];

    $('.participant_checkbox:checked').each(function(){
        selected.push($(this).val());
    });

    if(selected.length === 0){
            Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Please select at least one participant',
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            allowEscapeKey: false,
            backdrop: true
                });

        return;
    }

    $('#assignModal').modal('show');

});



//Use to view Photo in carousel design
$(document).on('click','.viewImages', function(){
    let participant_id = $(this).data('participant-id');
    $.get("/participants/images/"+participant_id, function(images){
        let html = '';
        $.each(images, function(i,img){
            html += `
            <div class="carousel-item ${i === 0 ? 'active' : ''}">
                <img src="/storage/participants/${img.image_name}" class="d-block w-100">
            </div>`;
        });
        $('#carouselImages').html(html);
        $('#imageCarouselModal').modal('show');
    });
});


//Use to open the Modal and display details about company
$(document).on('click', '#UpdateAddressModal', function(){

    var company_id         = $(this).data('company_id');
    var company_name = $(this).data('company_name');
    var status       = $(this).data('status');
    var caddress     = $(this).data('caddress');

console.log(company_id)

    $('#company_name').text(company_name);
    $('#company_id').val(company_id);
    $('#status').val(status);

     $('#ContactAddress').text(caddress);

    

    
    $('#AddressModal').modal('show');
});



//Use to open the Modal Updating Contact Person Form
//By Clicking the Edit BUtton per Contact
$(document).on('click', '#UpdateContact', function(){

    var p_id        = $(this).data('id');
    var companyname = $(this).data('companyname');
    var name        = $(this).data('name');
    var email       = $(this).data('email');
    var contact     = $(this).data('contact');

    console.log(companyname);
    //alert(company_name)

    $('#contact_company_name').text(companyname);
    $('#p_id').val(p_id);
    $('#participant_name').val(name);
    $('#participant_email').val(email);
    $('#participant_contact').val(contact);

    
    $('#ContactPersonUpdateModal').modal('show');
});


//Use to update CompanyAddress
$('#saveAddress').click(function(){

    let company_id = $('#company_id').val();
    let address    = $('#ContactAddress').val();

   console.log(company_id)

    $.ajax({
        url: '/companies/update-address',
        type: 'POST',
        data: {
            _token    : '{{ csrf_token() }}',
            company_id: company_id,
            address   : address
        },
        success: function(response){
           // $('#CompanyTbl').DataTable().ajax.reload(null, false);
           // alert('Address updated!');
           location.reload();

            Swal.fire({
                icon             : 'success',
                title            : 'Saved!',
                text             : 'Company Address has been updated!',
                timer            : 2000,
                showConfirmButton: false,
                toast            : true,
                position         : 'top-center'
                 });
            $('#AddressModal').modal('hide');
            $('#Address').val('');
          //  loadCompanies();
          //$('#CompanyTbl').DataTable().ajax.reload();
          loadCompanies();
          
        }
    });

});


//Use to update Contact Person Details
$('#SaveContactUpdates').click(function(){

    let p_id                = $('#p_id').val();
    let participant_name    = $('#participant_name').val();
    let participant_email   = $('#participant_email').val();
    let participant_contact = $('#participant_contact').val();

    $.ajax({
        url: '/companies/update-contactdetails',
        type: 'POST',
        data: {
            _token             : '{{ csrf_token() }}',
            p_id               : p_id,
            participant_name   : participant_name,
            participant_email  : participant_email,
            participant_contact: participant_contact
   
        },
        success: function(response){
          //  $('#CompanyTbl').DataTable().ajax.reload(null, false);
           // alert('Address updated!');
            Swal.fire({
                icon             : 'success',
                title            : 'Saved!',
                text             : 'Contact Details has been updated!',
                timer            : 2000,
                showConfirmButton: false,
                toast            : true,
                position         : 'top-center'
                 });
            $('#ContactPersonUpdateModal').modal('hide');
            $('#participant_name').val('');
            $('#participant_email').val('');
            $('#participant_contact').val('');
          //  loadCompanies();
          //$('#CompanyTbl').DataTable().ajax.reload();
          loadCompanies();
        }
    });

});

//Use to open modal for updating of Attendees Status
//Use to open the modal only
$(document).on('click', '.btnUpdateStatus', function(){

   
});

</script>

<style>

.progress{

height:8px;

border-radius:20px;

background:#edf2f7;

}

.progress-bar{

border-radius:20px;

transition:width .8s;

background:linear-gradient(90deg,#22c55e,#10b981);

}

.section-card{

background:#fff;

padding:14px;

border-radius:12px;

margin-bottom:14px;

box-shadow:0 3px 10px rgba(0,0,0,.05);

}

.company-icon{

width:52px;

height:52px;

border-radius:50%;

background:rgba(255,255,255,.15);

display:flex;

align-items:center;

justify-content:center;

color:#fff;

font-size:22px;

}



/* for LatestUpdate */
/* CSS Styling */
/* .company-header-card{

background:linear-gradient(135deg,#2b2d87,#312e81);

padding:18px;

min-height:105px;

} */

.company-header-card h6{

font-size:17px;

font-weight:700;

letter-spacing:.3px;

line-height:1.4;

margin-bottom:8px;

}

.company-header-card small{

font-size:12px;

opacity:.9;

line-height:1.5;

}

.company-source{

padding:8px 15px;

font-size:.72rem;

font-weight:600;

color:#fff;

letter-spacing:.5px;

}

.source-blue{

background:linear-gradient(135deg,#1d4ed8,#06b6d4);

}

.source-pink{

background:linear-gradient(135deg,#f97316,#ec4899);

}

.contact-card{

padding:12px;

border-radius:12px;

background:#fff;

margin-bottom:10px;

transition:.2s;

border:1px solid #edf2f7;

}

.contact-card:hover{

background:#f8fafc;

transform:translateX(3px);

}


.contact-name{

font-size:15px;

font-weight:700;

font-style:normal;

color:#1e293b;

}

.btn{

border-radius:10px;

font-weight:600;

}

.btn-outline-success:hover{

background:#22c55e;

color:white;

}

.btn-outline-dark:hover{

background:#1e293b;

color:white;

}

.agent-section{

border:none;

border-radius:14px;

box-shadow:0 4px 12px rgba(0,0,0,.05);

overflow:hidden;

}
.agent-section .border{

background:#fff7d6;

border:none!important;

padding:14px;

}
.agent-avatar{

width:40px;

height:40px;

border-radius:50%;

background:#2563eb;

display:flex;

align-items:center;

justify-content:center;

color:white;

font-size:18px;

}

.badge{

padding:7px 12px;

font-size:11px;

border-radius:20px;

}

.view-files-btn{

border-radius:8px;

padding:6px 14px;

font-weight:600;

}

.contact-scroll-box::-webkit-scrollbar{

width:6px;

}

.contact-scroll-box::-webkit-scrollbar-thumb{

background:#cbd5e1;

border-radius:20px;

}

.contact-scroll-box::-webkit-scrollbar-track{

background:transparent;

}

/* ==========================
   RESPONSIVE CARD
========================== */

.card-company{
    width:340px;
}

@media (max-width:1200px){
    .card-company{
        width:48%;
    }
}

@media (max-width:768px){
    .card-company{
        width:100%;
    }
}

.header-philconstruct{
    background: linear-gradient(135deg, #2b2d87, #312e81);
}

.header-inquiry{
    background: linear-gradient(135deg, #ec4899, #db2777);
}
/* Ending of NEw CSS */


    </style>

@endsection