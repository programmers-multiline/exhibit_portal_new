@extends('layouts.app')

@section('content')
<style>
    /* Pinapaliit ang font ng buong DataTables wrapper */
    .dataTables_wrapper {
        font-size: 0.8rem !important; /* Mga 12px-13px */
    }
    
    /* Pinapaliit ang font at padding ng mga cells (th at td) */
    #ContactsTbl th, 
    #ContactsTbl td {
        font-size: 0.78rem !important; 
        padding: 5px 8px !important; /* Mas manipis na taas at babang espasyo */
    }

    /* Pinapaliit din ang text sa loob ng mga buttons (Excel, PDF) */
    .dt-buttons .btn {
        font-size: 0.75rem !important;
        padding: 4px 8px !important;
    }

    /* Pinapaliit ang search input box at pagination info */
    .dataTables_filter input,
    .dataTables_info,
    .dataTables_paginate {
        font-size: 0.75rem !important;
    }
</style>


<div class="card-box mb-30 p-3 ml-15">

   <h3>Contact List</h3> 

<div class="row p-2">
<div class="searchingDiv w-100  px-2">
    <!-- Ginawang iisang row ang buong filter at button container -->

<form id="filterForm" class="row g-3 align-items-center">

        
        <!-- Start Date Field -->
        <div class="col-3 col-md-3">
            <label for="start" class="form-label small fw-bold text-muted mb-1">Start Date</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-secondary border-end-0">
                    <i class="bi bi-calendar-event"></i>
                </span>
                <input type="date" id="start" name="startDate" class="form-control ps-1 shadow-none">
            </div>
        </div>

        <!-- End Date Field -->
        <div class="col-3 col-md-3 ">
            <label for="end" class="form-label small fw-bold text-muted mb-1">End Date</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-secondary border-end-0">
                    <i class="bi bi-calendar-check"></i>
                </span>
                <input type="date" id="end" name="endDate" class="form-control ps-1 shadow-none">
            </div>
        </div>

        <!-- Action Buttons (Filter & Clear) -->
                <!-- Action Buttons (Filter & Clear) -->
        <!-- Idinagdag ang align-items-center dito -->
        <div class="col-3 col-md-3 d-flex gap-2 align-items-center">
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center px-4 shadow-sm w-25">
                <i class="bi bi-filter-square me-2"></i> Filter
            </button>&nbsp;
            <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center px-3 w-25">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear
            </button>
        </div>

        <!-- Assign PSC Button -->
        <!-- Ginawang d-flex at align-items-center kasama ang justify-content-md-end para manatili sa kanan -->
       <!--  <div class="col-3 col-md-3 d-flex align-items-center justify-content-md-start">
            @if (in_array(auth()->user()->position_id, [13, 237]))
                <button type="button" class="btn btn-success d-inline-flex align-items-center justify-content-center px-4 shadow-sm text-white" id="">
                    <i class="bi bi-person-plus me-2"></i> Assign PSC
                </button>
            @endif
        </div> -->


    </form>
</div>


   </div>
   <!-- Ending of row -->


<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-4">
        <!-- Wrapper para sa responsive table para hindi masira ang layout -->
        <div class="table-responsive">
        <!-- Ilagay ito kahit saan sa iyong blade view -->
            <div id="assignPscWrapper" class="d-none ms-3">
                @if (in_array(auth()->user()->position_id, [13, 237,158]))
                    <button type="button" class="btn btn-success d-inline-flex align-items-center justify-content-center px-4 text-white shadow-sm" id="bulkAssignBtn" style="height: 38px; width:auto;">
                        <i class="bi bi-person-plus me-2"></i> Assign PSC
                    </button>
                @endif
            </div>

            <div class="form-group d-none ms-3" id="FilterPscWrapper">
               <!--  <label for="psc_filter">PSC Assignment:</label> -->
                <select id="psc_filter" class="form-control">
                    <option value="">All</option>
                    <option value="unassigned">No PSC Assigned (Wala Pa)</option>
                    <option value="assigned">With PSC Assigned</option>
                </select>
            </div>


            <table id="ContactsTbl" class="table table-striped table-hover align-middle nowrap w-100" style="margin-top: 15px !important;">
                <thead class="table-dark text-uppercase fs-3 tracking-wider">
                    <tr>
                        <th class="text-center" style="width: 40px;">PSC Name</th>
                        <th>Exhibit Name</th>
                        <th>Company Info</th>
                        <th>Contact Info</th>
                        <th>Remarks</th>
                        <th>Date Collected</th>
                        <th class="text-center" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody class="fs-6 text-secondary">
                    <!-- Kusang pupunuin ng Yajra DataTables Ajax -->
                </tbody>
            </table>
        </div>
    </div>
</div>


</div>

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
 @foreach($users as $user)
    @php
        $fullName   = $user->first_name . ' ' . $user->last_name;
        $currentUrl = request()->fullUrl();
    @endphp

          @if(request()->is('*viewcontacts*'))
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


@endsection


@section('scripts')

 <script>
const startDateInput = document.getElementById('start');
const endDateInput   = document.getElementById('end');

 // When start date changes, end date cannot be earlier than start date
startDateInput.addEventListener('change', function() {
    if (this.value) {
     endDateInput.min = this.value;
     }
   });

// When end date changes, start date cannot be later than end date
 endDateInput.addEventListener('change', function() {
     if (this.value) {
     startDateInput.max = this.value;
    }
});
</script>


<script>
//Use to multiple select participants for assigning of PSC
$('#bulkAssignBtn').click(function(){

    //let selected = [];
    let selected = selectedCompanies;

   // alert(selected);

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
//
//Use to Save Assigned PSC    


$('#confirmAssign').click(function(){

   // let selected = [];
    let selected = selectedCompanies;

    // Get selected participants
    $('.participant_checkbox:checked').each(function(){
        selected.push($(this).val());
    });

    let psc_id = $('#psc_id').val();

  //  alert(psc_id)

    if(selected.length === 0){
        alert("Please select participants");
        return;
    }

    if(psc_id === ""){
        alert("Please select PSC");
        return;
    }

    // AJAX Save
    $.ajax({
        url: "/Attendance/bulk-assign",
        type: "POST",
        data:{
            _token  : $('meta[name="csrf-token"]').attr('content'),
            attendee: selected,
            psc_id  : psc_id
        },
        success:function(response){


            $('#assignModal').modal('hide');
            // IDAGDAG ITO PARA MA-RESET ANG SELECTION
          // $('#psc_id').val(''); 

            // Reload Table
           // $('#ContactsTbl').DataTable().ajax.reload(null,false);
            location.reload(); 

            //alert("Successfully Assigned!");
             Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Successfully Assigned PSC.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-center'
            });

        },
        error:function(){
            alert("Error saving assignment");
        }
    }); 

});


//Use to view Photo in carousel design
$(document).on('click','.viewImages', function(){

    let participant_id = $(this).data('id');

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


                   


$(document).ready(function(){

    LoadContacts();

    $('.searchingDiv form').on('submit', function(e) {
        e.preventDefault(); // Pigilan ang page reload
        $('#ContactsTbl').DataTable().draw(); // I-refresh ang data gamit ang bagong dates
    });

 // Reset filter form at i-refresh ang table data
    $('#resetFilterBtn').on('click', function() {
        $('#start').val(''); // Burahin ang start date
        $('#end').val('');   // Burahin ang end date

        // 2. Tanggalin o i-reset ang HTML validation attributes kung mayroon man
    $('#start').removeAttr('max').removeAttr('min');
    $('#end').removeAttr('max').removeAttr('min');
    selectedCompanies = []; // <-- LINISIN ANG ARRAY NG MGA CHECKBOXES

        $('#ContactsTbl').DataTable().draw(); // I-refresh ang DataTables
    });   
   

});//Ending of Document Ready


/* var selectedCompanies = [];
function LoadContacts()
{
   $('#ContactsTbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ route('contacts.viewcontacts') }}",
        data: function (d) {
            d.startDate = $('#start').val();
            d.endDate = $('#end').val();
        }
    },

    columns: [
        { data: 'checkbox', name: 'checkbox'},
        { data: 'exhibit_name', name: 'contacts.exhibit_name' },
     // PINAGSAMANG COMPANY AT ADDRESS
    { 
        data: 'company_name', 
        name: 'company_list.company_name',
        render: function(data, type, row) {
            return `
                <div class="company-info-block">
                    <div class="company-title" style="color:#01134A; font-weight:bold;">${row.company_name || ''}</div>
                    <div class="company-address">
                        <i class="fas fa-map-marker-alt address-icon text-primary"></i> ${row.address || '—'}
                        <i class="fas fa-edit" data-idcom="${row.id}" style="color:red;cursor:pointer;"></i>
                    </div>
                </div>
            `;
        }
    },

        // PINAGSAMANG COLUMN PARA SA CONTACT INFO
    { 
        data: 'contact_name', 
        name: 'contacts.name',
        render: function(data, type, row) {
            return `
                <div class="contact-info-block">
                    <div class="contact-name" style="color:#8F6E03; font-weight:bold;">${row.contact_name || ''}</div>
                    <div class="contact-item">
                        <i class="fas fa-envelope contact-icon" style="color:#01454A;"></i> ${row.email || '—'}
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone contact-icon" style="color:#402101;"></i> ${row.phone || '—'}
                        <i class="fas fa-edit" data-idcom="${row.id}" style="color:red;cursor:pointer;"></i>
                    </div>
                </div>
            `;
        }
    },

        { data: 'remarks', name: 'contacts.remarks' },
        { data: 'date', name: 'contacts.date' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
    ],

    // DITO ILALAGAY ANG LOGIC PARA SA WORD BREAK AT ALIGNMENT
    columnDefs: [
        {
            targets: [2,3,4], // Index ng columns na apektado (0-based index: 3 = address, 5 = email)
            className: 'address-wrap' // Custom CSS class na gagawin natin sa ibaba
        },
        {
            targets: 6, // Index ng columns na apektado (0-based index: 3 = address, 5 = email)
            className: 'action_col1' // Custom CSS class na gagawin natin sa ibaba
        }
    ],

    initComplete: function() {
        // Gawing flexbox ang container ng "Show entries"
        $('.dataTables_length').addClass('d-flex align-items-center');

        // Ilipat ang wrapper at puwersahin ang espasyo gamit ang inline style css
        $('#assignPscWrapper').removeClass('d-none')
                            .css('margin-left', '20px') 
                            .appendTo('.dataTables_length');
    }
});

} */

// 1. Dito itatabi ang lahat ng ID ng mga naka-check na kumpanya
var selectedCompanies = [];

function LoadContacts()
{
   $('#ContactsTbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ route('contacts.viewcontacts') }}",
        data: function (d) {
            d.startDate = $('#start').val();
            d.endDate   = $('#end').val();
            d.pscFilter = $('#psc_filter').val();
        }
    },

    columns: [
        { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false}, // Dagdagan ng orderable/searchable false
        { data: 'exhibit_name', name: 'contacts.exhibit_name' },
        { 
            data: 'company_name', 
            name: 'company_list.company_name',
            render: function(data, type, row) {
                return `
                    <div class="company-info-block">
                        <div class="company-title" style="color:#01134A; font-weight:bold;">${row.company_name || ''}</div>
                        <div class="company-address">
                            <i class="fas fa-map-marker-alt address-icon text-primary"></i> ${row.address || '—'}
                            <i class="fas fa-edit" data-idcom="${row.id}" style="color:red;cursor:pointer;"></i>
                        </div>
                    </div>
                `;
            }
        },
        { 
            data: 'contact_name', 
            name: 'contacts.name',
            render: function(data, type, row) {
                return `
                    <div class="contact-info-block">
                        <div class="contact-name" style="color:#8F6E03; font-weight:bold;">${row.contact_name || ''}</div>
                        <div class="contact-item">
                            <i class="fas fa-envelope contact-icon" style="color:#01454A;"></i> ${row.email || '—'}
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone contact-icon" style="color:#402101;"></i> ${row.phone || '—'}
                            <i class="fas fa-edit" data-idcom="${row.id}" style="color:red;cursor:pointer;"></i>
                        </div>
                    </div>
                `;
            }
        },
        { data: 'remarks', name: 'contacts.remarks' },
        { data: 'date', name: 'contacts.date' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
    ],

    columnDefs: [
        { targets: 2, className: 'address-wrap' },
        { targets: 6, className: 'action_col1' }
    ],

  /*   initComplete: function() {
        $('.dataTables_length').addClass('d-flex align-items-center');
        $('#assignPscWrapper').removeClass('d-none').css('margin-left', '20px').appendTo('.dataTables_length');
        $('#FilterPscWrapper').removeClass('d-none').css('margin-left', '80px').appendTo('.dataTables_length');
        
    }, */

initComplete: function() {
    // 1. Gumawa ng parent row na may flexbox at justify-content-between para itulak ang search sa kanan at filters sa kaliwa
    var mainRow = $('<div class="d-flex justify-content-between align-items-center flex-wrap mb-2" style="width: 100%;"></div>');
    
    // 2. Gumawa ng kaliwang grupo para sa iyong mga filters at dropdowns
    var leftGroup = $('<div class="d-flex align-items-center flex-wrap"></div>');
    
    // 3. Ipasok ang "Show entries" at ang iyong mga custom wrappers sa kaliwang grupo
    $('.dataTables_length').appendTo(leftGroup).css({'margin-right': '15px', 'margin-bottom': '0px'});
    
    if ($('#assignPscWrapper').length) {
        $('#assignPscWrapper').removeClass('d-none').css({'margin-right': '15px', 'margin-bottom': '0px'}).appendTo(leftGroup);
    }
    
    if ($('#FilterPscWrapper').length) {
        $('#FilterPscWrapper').removeClass('d-none').css({'margin-right': '15px', 'margin-bottom': '0px'}).appendTo(leftGroup);
    }

    // 4. Kunin ang orihinal na Search bar at alisin ang mga default margin nito
    var rightGroup = $('.dataTables_filter');
    rightGroup.css({'margin-bottom': '0px', 'float': 'none'});

    // 5. I-append ang kaliwang grupo at kanang grupo (Search) sa ating mainRow container
    leftGroup.appendTo(mainRow);
    rightGroup.appendTo(mainRow);

    // 6. I-pwesto ang buong mainRow sa pinakataas ng DataTables wrapper
    mainRow.prependTo('#ContactsTbl_wrapper');
    
    // 7. Linisin ang mga natitirang walang lamang rows na iniwan ng default DataTables layout
    $('#ContactsTbl_wrapper .row:first').remove();
}


,

    // 2. DITO ANG LOGIC PARA PANATILIHING NAKA-CHECK KAPAG NAG-NEXT PAGE
    drawCallback: function(settings) {
        $('.participant_checkbox').each(function() {
            var id = $(this).val();
            // Kung ang ID ay nasa loob ng array natin, lagyan ito ng checked attribute
            if (selectedCompanies.indexOf(id) !== -1) {
                $(this).prop('checked', true);
            }
        });
    }
   });
}

// 3. EVENT LISTENER KAPAG NI-CHECK O INALIS ANG CHECK NG USER
$(document).on('change', '.participant_checkbox', function() {
    var id = $(this).val();

    if ($(this).is(':checked')) {
        // Kung ni-check at wala pa sa array, idagdag ito
        if (selectedCompanies.indexOf(id) === -1) {
            selectedCompanies.push(id);
        }
    } else {
        // Kung inalisan ng check, tanggalin sa array
        var index = selectedCompanies.indexOf(id);
        if (index !== -1) {
            selectedCompanies.splice(index, 1);
        }
    }
    
    console.log("Selected IDs across all pages:", selectedCompanies); // Pwede mong tingnan sa console inspect
});

// Makikinig ito sa tuwing babaguhin ang seleksyon sa dropdown
$('#psc_filter').on('change', function() {
    // I-trigger ang muling pag-load at pag-filter ng DataTables gamit ang bagong halaga
    $('#ContactsTbl').DataTable().draw();
});



</script>

<style>
/* I-apply ito para sa magandang text wrapping ng address */
.address-wrap {
    min-width: 250px !important;    /* Pinakamababang lapad para hindi maging vertical ang letra */
    max-width: 350px !important;    /* Pinakamalapad na pwedeng abutin */
    white-space: normal !important; /* Pinapayagan ang pagbaba sa susunod na linya */
    word-break: keep-all !important;/* Hindi puputulin ang mismong salita sa gitna ng letra */
    overflow-wrap: break-word !important; /* Bababa lang ang buong salita kapag kulang ang espasyo */
}
.action_col1
{
    text-align: center;
}

</style>
@endsection