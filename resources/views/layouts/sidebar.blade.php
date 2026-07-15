<div class="left-side-bar">
		<div class="brand-logo">
			<a href="index.html">
				<img src="{{ asset('vendors/images/eventlogo12.png') }}" class="dark-logo">
				<img src="{{ asset('vendors/images/eventlogo12.png') }}" class="light-logo">
			</a>
			<div class="close-sidebar" data-toggle="left-sidebar-close">
				<i class="ion-close-round"></i>
			</div>
		</div>
		<div class="menu-block customscroll">
			<div class="sidebar-menu">
				<ul id="accordion-menu">
				
					<!-- <li class="dropdown {{ request()->is('participant/create') ? 'active' : '' }}">
						<a href="/participants/add_participant" class="dropdown-toggle no-arrow">
							<span class="micon dw dw-invoice"></span><span class="mtext">Add Contact</span>
						</a>
					</li> -->
					
					<li class="dropdown {{ request()->is('company_card') || request()->is('contacts') || request()->is('participants') ? 'active' : '' }}">
						<a href="javascript:;" class="dropdown-toggle">
							<!-- <span class="micon dw dw-library"></span> -->
							<i class="micon fas fa-bullseye"></i>
							<span class="mtext">Leads</span>
						</a>
						<ul class="submenu">
							<li class="{{ request()->is('AssignedContact') ? 'active' : '' }}"><a href="/AssignedContact">My Leads</a></li>
						<!-- 	<li class="{{ request()->is('company_card') ? 'active' : '' }}"><a href="/company_card">Per Company</a></li>
							<li class="{{ request()->is('contacts') ? 'active' : '' }}"><a href="/contacts">Per Contacts</a></li>
							<li class="{{ request()->is('participants') ? 'active' : '' }}"><a href="/participants">Partcipants</a></li> -->
						</ul>
					</li>

					<!-- <li class="{{ request()->is('client') || request()->is('client/*') ? 'active' : '' }}">
						<a href="{{ url('client') }}" class="dropdown-toggle no-arrow">
							<span class="micon dw dw-invoice"></span>
							<span class="mtext">Client List</span>
						</a>
					</li> -->
					<li class="{{ request()->is('import') || request()->is('import/*') ? 'active' : '' }}">
						<a href="{{ url('import') }}" class="dropdown-toggle no-arrow">
							<i class=" micon fas fa-upload"></i>
							<span class="mtext">import {{auth()->user()->position_id}}</span>
						</a>
					</li>

					@if(auth()->user() && (auth()->user()->position_id == 13 || auth()->user()->position_id == 158))
					<!-- <li class="{{ request()->is('Attendance') || request()->is('Attendance/*') ? 'active' : '' }}">
						<a href="{{ url('Attendance') }}" class="dropdown-toggle no-arrow">
							<i class="micon fas fa-list-alt"></i>
							<span class="mtext">Attendance</span>
						</a>
					</li> -->

					
					<li class="{{ request()->is('viewcontacts') || request()->is('viewcontacts/*') ? 'active' : '' }}">
						<a href="{{ url('viewcontacts') }}" class="dropdown-toggle no-arrow">
					
							<i class="micon fas fa-address-book"></i>
							<span class="mtext">Contacts</span>
						</a>
					</li>
					@endif
				
					<li>
						<div class="dropdown-divider"></div>
					</li>

					<li class="dropdown {{ request()->is('introduction') || request()->is('getting-started') || request()->is('color-settings') ? 'active' : '' }}">
					<a href="javascript:;" class="dropdown-toggle">
						<span class="micon dw dw-edit-2"></span><span class="mtext">Reports</span>
					</a>
					<ul class="submenu">
						<li class="{{ request()->is('introduction') ? 'active' : '' }}">
							<a href="/reports">Summary Report</a>
						</li>
						<li class="{{ request()->is('/reports/agent-performance') ? 'active' : '' }}">
							<a href="/reports/agent-performance">Agent Report</a>
						</li>
						<!-- <li class="{{ request()->is('color-settings') ? 'active' : '' }}">
							<a href="color-settings.html">Summary Report Per Agent</a>
						</li> -->
					</ul>
					</li>
				<!-- 	<li>
						<a href="https://dropways.github.io/deskapp-free-single-page-website-template/" target="_blank" class="dropdown-toggle no-arrow">
							<span class="micon dw dw-paper-plane1"></span>
							<span class="mtext">Landing Page <img src="vendors/images/coming-soon.png" alt="" width="25"></span>
						</a>
					</li> -->
				</ul>
			</div>
		</div>
	</div>

	<style>
/* Submenu container */
.sidebar-menu .submenu {
    padding-left: 0;
}

/* Submenu items */
.sidebar-menu .submenu li a {
    padding   : 6px 55px ;     
    /* font-size : 13px;        
    text-align: center;      
    display   : block; */
}

/* Optional: adjust spacing ng li */
.sidebar-menu .submenu li {
    margin: 2px 0;
}
.sidebar-menu .submenu li a:hover {
    background-color: #f5f9ff;
	color: #007bff;
	font-weight: 800;
}
.sidebar-menu .submenu li a {
    border-radius: 4px;
}
.sidebar-menu ul li .submenu li.active > a {
    background-color: #e7f1ff;
    color: #007bff;
    border-left: 3px solid #007bff;
	font-weight: 800;
}
	</style>
	<script>
		$(document).ready(function () {

    let currentUrl = window.location.pathname;

    $('#accordion-menu a').each(function () {
        let link = $(this).attr('href');

        if (link === currentUrl) {

            // highlight current
            $(this).parent().addClass('active');

            // highlight parent dropdown
            $(this).closest('.dropdown').addClass('active');

            // keep submenu open
            $(this).closest('.submenu').show();
        }
    });

});
	</script>