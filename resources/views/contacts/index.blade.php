@extends('layouts.app')

@section('content')
<div class="mobile-menu-overlay"></div>
	<!-- <div class="main-container">
		<div class="pd-ltr-10 xs-pd-10-10">
			<div class="min-height-200px"> -->
				<div class="container pd-0">
					<div class="page-header">
						<div class="row">
							<div class="col-md-12 col-sm-12">
								<div class="title">
									<h4>Contact Directory</h4>
								</div>
								<nav aria-label="breadcrumb" role="navigation">
									<ol class="breadcrumb">
										<li class="breadcrumb-item"><a href="index.html">Home</a></li>
										<li class="breadcrumb-item active" aria-current="page">Contact Directory</li>
									</ol>
								</nav>
							</div>
						</div>
					</div>
                    <div class="mb-4">
                        <input 
                            type="text" 
                            id="crmSearchInput"
                            class="form-control"
                            placeholder="Search Name, Email, Company or Agent..."
                        >
                    </div>
					<div class="contact-directory-list">
						<ul class="row">
                              @foreach($contacts as $contact)  
							<li class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
								<div class="contact-directory-box">
									<div class="contact-dire-info text-center">
										<div class="contact-avatar">
							<span>
								@if($contact->image_name)
									<img src="{{ asset('storage/participants/'.$contact->image_name) }}">
								@else
								<img src="{{ asset('storage/participants/profile.png') }}" alt="{{ $contact->participant_name }}">
								@endif
							</span>
										</div>
										<div class="contact-name">
											<h4>{{ $contact->participant_name }}</h4>
											<p>{{ $contact->participant_email }}</p>
											<p>{{ $contact->id }}</p>
											<div class="work text-success"><i class="ion-android-person"></i> {{ $contact->participant_position }}</div>
										</div>
								
										<div class="profile-sort-desc p-0 text-left">
											<p><i class="far fa-building"></i> {{ $contact->participant_company }}</p>
                                            <p><i class="far fa-address-book"></i> {{ $contact->participant_contact }}</p>
                                            <p><i class="fas fa-map-marker"></i> {{ $contact->participant_address }}</p>
											
                                            <p class="text-success font-weight-bold"><i class="far fa-user"></i> {{ $contact->assigned_psc }}</p>
										</div>
									</div>
									<div class="view-contact">
										<a href="#">View Profile</a>
									</div>
								</div>
							</li>
                             @endforeach
							
						</ul>
					</div>
				</div>
			<!-- </div> -->
			<div class="footer-wrap pd-20 mb-20 card-box">
				<!-- DeskApp - Bootstrap 4 Admin Template By <a href="https://github.com/dropways" target="_blank">Ankit Hingarajiya</a> -->
			</div>
		<!-- </div>
	</div> -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

let searchTimeout;

$('#crmSearchInput').on('keyup', function(){

    clearTimeout(searchTimeout);

    let search = $(this).val();

    searchTimeout = setTimeout(function(){

        $.ajax({
            url: "/contacts/search",
            method: "GET",
            data: { search: search },

            success: function(response){

                let html = '';

                response.forEach(contact => {

                    html += `
                    <li class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                        <div class="contact-directory-box">

                            <div class="contact-dire-info text-center">
                                <div class="contact-avatar">
											<span>
												<img src="storage/participants/${contact.participant_photo ?? 'profile.png'}" alt="${contact.participant_name ?? ''}">
												
											</span>
								</div>
                                <div class="contact-name">
                                    <h4>${contact.participant_name ?? ''}</h4>
                                    <p>${contact.participant_email ?? ''}</p>
                                    <div class="text-success">
                                        ${contact.participant_position ?? ''}
                                    </div>
                                </div>

                                <div class="profile-sort-desc text-left">
                                    <p><i class="far fa-building"></i>
                                    ${contact.participant_company ?? ''}</p>

                                    <p><i class="far fa-address-book"></i>
                                    ${contact.participant_contact ?? ''}</p>

                                    <p class="text-success font-weight-bold"><i class="far fa-user"></i>
                                    ${contact.assigned_psc ?? ''}</p>
                                </div>

                            </div>

                        </div>
                    </li>`;
                });

                $('.contact-directory-list ul').html(html);

            }
        });

    }, 500); // ⭐ Debounce delay

});

</script>

@endsection