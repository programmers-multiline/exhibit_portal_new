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

   <h3>Inquiry List</h3> 

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
                    <button type="button" class="btn btn-success d-inline-flex align-items-center justify-content-center px-4 text-white shadow-sm" 
                    data-inquiry="10001"
                    id="bulkAssignBtn" style="height: 38px; width:auto;">
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


       <table id="ContactsTbl" class="table table-hover align-middle w-100" style="margin-top: 15px !important; border-collapse: separate; border-spacing: 0 8px;">
            <thead style="background-color: #2e3641; color: #ffffff;" class="text-uppercase small fw-bold">
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th>Company Info</th>
                    <th>Contact Info</th>
                    <th style="padding: 12px 15px;">Category</th>
                    <th>Product Inquiry</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody class="fs-6 text-dark">
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

<input type="text" id="source_type" hidden>

<div class="modal-body">
<select id="psc_id" class="form-control">
 @foreach($users as $user)
    @php
        $fullName   = $user->first_name . ' ' . $user->last_name;
        $currentUrl = request()->fullUrl();
    @endphp

          @if(request()->is('*inquiries*'))
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
    let source_data = $(this).data('inquiry');
    let selected    = selectedCompanies;

   // alert(selected);
   console.log(source_data);
   $('#source_type').val(source_data);

 $('.participant_checkbox:checked').each(function(){
    selected.push({
        inquirer_id  : $(this).data('inquirer_id'),      // Idinagdag ang 'id:' key dito
        company_name : $(this).data('companyname'),
        address      : $(this).data('companyaddress'),
        contactname  : $(this).data('contactname'),
        contactemail : $(this).data('contactemail'),
        contactnumber: $(this).data('contactnumber')
    }); // Isinara nang tama ang curly brace at panaklong
});


//console.log(selected)

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

    let selected          = typeof selectedCompanies !== 'undefined' ? [...selectedCompanies] : [];
    let source_type       = $('#source_type').val();
    let psc_id            = $('#psc_id').val();
    //let psc_selected_name = $('#psc_id option:selected').text().trim();  // 1. Kunin ang pangalan at linisin ang mga spaces gamit ang .trim()

   // console.log(psc_selected_name)

    $('.participant_checkbox:checked').each(function(){
        let idValue = $(this).val();
        let compNameValue = $(this).data('companyname');
        
        // Pinahusay na tsek: titingnan kung tugma sa id o sa company name mapa-string man o object ang laman ng array
        let exists = selected.some(function(item) {
            if (typeof item === 'object' && item !== null) {
                return item.id == idValue || item.company_name == compNameValue;
            } else {
                return item == idValue;
            }
        });
        
        if (!exists) {
            selected.push({
                inquirer_id      : idValue,
                company_name     : compNameValue,
                companyaddress   : $(this).data('companyaddress'),
                contactname      : $(this).data('contactname'),
                contactemail     : $(this).data('contactemail'),
                contactnumber    : $(this).data('contactnumber')
            });
        }
    });

    // 🔥 HAKBANG 1: Salain ang array para tanggalin ang mga lumang plain string IDs ('3581', '3579')
    // Ipapasa lang nito ang mga valid objects na may kumpletong detalye mula sa HTML data-attributes
    let cleanAttendeeData = selected.filter(function(item) {
        return typeof item === 'object' && item !== null && item.hasOwnProperty('company_name');
    });

    if(source_type == '' || source_type == null) {
        source_type = 10000;
    }

    // Makita mo rito sa console na 2 malilinis na object na lang ang laman nito sa halip na apat
    console.log("Ito ang ipapadala sa Controller:", cleanAttendeeData); 

    if(cleanAttendeeData.length === 0){
        alert("Please select participants");
        return;
    }

    if(psc_id === ""){
        alert("Please select PSC");
        return;
    }

    $.ajax({
        url: "/inquiry/bulk-assign",
        type: "POST",
        data:{
            _token           : $('meta[name="csrf-token"]').attr('content'),
            attendee         : cleanAttendeeData,                              // 🔥 Binago: Ginamit ang sinalang malinis na array ng objects
            psc_id           : psc_id,
            source_type      : source_type
        },
       success: function(response) {
                // 1. Show the success alert immediately
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000, // Automatically closes alert after 2 seconds
                    showConfirmButton: false
                });

                // 2. Wait 2 seconds, then hide modal and reload table
                setTimeout(function() {
                    $('#assignModal').modal('hide');
                    $('#ContactsTbl').DataTable().ajax.reload(null, false);
                }, 2000); 
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



// 1. Dito itatabi ang lahat ng ID ng mga naka-check na kumpanya
var selectedCompanies = [];


function LoadContacts()
{
    $('#ContactsTbl').DataTable({
        destroy   : true,   // Iniiwasan ang reinitialization error
        processing: true,
        serverSide: true,
        ajax      : {
            url: "{{ route('inquiries.index') }}",
            data: function (d) {
                console.log("Start:", $('#start').val());
                console.log("End:", $('#end').val());

                d.startDate = $('#start').val();
                d.endDate   = $('#end').val();
                d.pscFilter = $('#psc_filter').val();
            }
        },
        columns: [
                    {
                data: 'assigned_psc_name',
                name: 'inq.assigned_psc_name',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    // Suriin kung walang laman (null, undefined, o empty string) 
                        if (data === null || data === undefined || String(data).trim() === '')
                             {
                        // Magpapakita ng checkbox kung WALANG LAMAN ang assigned_psc
                        return `<div class="form-check d-flex justify-content-center">
                                    <input class               = "participant_checkbox form-check-input border-secondary contact-checkbox"
                                           data-companyname    = "${row.client_company}"
                                           data-companyaddress = "${row.client_address}"
                                           data-inquirer_id    = "${row.inquirer_id}"
                                           data-contactname    = "${row.client_name}"
                                           data-contactemail   = "${row.client_email}"
                                           data-contactnumber  = "${row.client_contact_no}"
                                           type                = "checkbox" value = "${row.inquirer_id}">
                                </div>`;
                    } else {
                        // Magpapakita ng badge o pangalan kung MAY LAMAN ang assigned_psc
                        return `<span class="badge bg-light text-success border border-success px-2 py-1">${data}</span>`;
                    }
                }
            },
             {
                data: 'client_company',
                name: 'inqr.company_name',
                render: function(data, type, row) {
                    let company = data ? `<strong class="text-primary d-block mb-1" style="color: #0b2545 !important;">${data.toUpperCase()}</strong>` : '<span class="text-muted d-block mb-1">N/A</span>';
                    let address = row.client_address ? `<span class="text-muted d-flex align-items-start small" style="font-size: 0.85rem;"><i class="bi bi-geo-alt-fill text-primary me-1 mt-1"></i>&nbsp; ${row.client_address}</span>` : '<span class="text-muted small"><i class="bi bi-geo-alt-fill text-primary me-1"></i> - </span>';
                    return `<div>${company}${address}</div>`;
                }
            },
            {
                data: 'client_name',
                name: 'inqr.client_name',
                render: function(data, type, row) {
                    let name = `<strong class="d-block mb-1 text-warning" style="color: #bfa15f !important;">${data ? data.toUpperCase() : ''}</strong>`;
                    let email = row.client_email ? `<span class="d-block text-secondary small mb-1"><i class="bi bi-envelope-fill me-1"></i> ${row.client_email}</span>` : '';
                    let contact = row.client_contact_no ? `<span class="d-block text-secondary small"><i class="bi bi-telephone-fill me-1"></i> ${row.client_contact_no}</span>` : '';
                    return `<div>${name}${email}${contact}</div>`;
                }
            },
            {
                data: 'inquirer_id', // Kung walang PSC name, gagawing Checkbox gaya ng nasa larawan
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    // Halimbawa: Kung may pscFilter o pscName, gawing green badge. Kung wala, checkbox.
                    if(row.category_name) { // Palitan ng totoong psc column kung meron na sa query
                        return `<span class="badge bg-white text-primary border border-primary mb-2">Source:${row.event_code}</span><br><span class="badge bg-white text-success border border-success px-3 py-2 fs-6 fw-normal"> ${row.category_name}</span>`;
                    }
                    return `<div class="form-check">--</div>`;
                }
            },
            {
                data: 'product_name',
                name: 'prod.product_name',
                render: function(data, type, row) {
                    return `<span class="text-secondary">${data || 'PhilConstruct'}</span>`;
                }
            },
            {
                data: 'inquiry_date',
                name: 'inq.inquiry_date',
                render: function(data) {
                    if(!data) return '';
                    // I-format ang date para maging YYYY-MM-DD katulad ng nasa pic
                    return `<span class="text-secondary small">${data.split(' ')[0]}</span>`;
                }
            }
        ]
        ,

    columnDefs: [
        { targets: 1, className: 'companyinfo-wrap' },
        { targets: 3, className: 'companyinfo-wrap' }
    ],
    
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
    },
        // Opsyonal: Pagandahin ang itsura ng table rows gamit ang CSS padding
        createdRow: function(row, data, dataIndex) {
            $(row).addClass('bg-white shadow-sm');
            $('td', row).css({
                'padding': '15px 10px',
                'vertical-align': 'middle',
                'border-top': '1px solid #e9ecef',
                'border-bottom': '1px solid #e9ecef'
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
.companyinfo-wrap {
    min-width: 200px !important;    /* Pinakamababang lapad para hindi maging vertical ang letra */
    max-width: 300px !important;    /* Pinakamalapad na pwedeng abutin */
    white-space: normal !important; /* Pinapayagan ang pagbaba sa susunod na linya */
    word-break: keep-all !important;/* Hindi puputulin ang mismong salita sa gitna ng letra */
    overflow-wrap: break-word !important; /* Bababa lang ang buong salita kapag kulang ang espasyo */
}

    /* Baguhin ang kulay ng Table Header base sa image */
    #ContactsTbl thead th {
        background-color: #2b3541 !important;
        color: #a4b0be !important;
        font-weight: 600;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        border: none;
    }
    
    /* Gawing bahagyang may awang ang mga rows para maging card-like ang porma */
    #ContactsTbl tbody tr {
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    #ContactsTbl tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    /* Estilo para sa mga Bootstrap icons */
    .bi-geo-alt-fill, .bi-envelope-fill, .bi-telephone-fill {
        color: #4b7bec !important;
    }
</style>


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