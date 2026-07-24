@extends('layouts.app')

@section('content')

<div class="card-box mb-30 p-4 shadow-sm">
    <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
        <h4 class="h5 text-dark font-weight-bold mb-0">
            <i class="fas fa-chart-bar text-primary mr-2"></i> Agent Performance Report
        </h4>
    </div>

    <!-- Main Summary Table -->
    <div class="table-responsive border rounded">
        <table class="table table-hover table-striped text-center align-middle mb-0">
            <thead class="bg-light text-secondary font-weight-bold">
                <tr>
                    <th class="text-left pl-3">Agent Name</th>
                    <th>Total Assigned</th>
                    <th>Total Leads Average</th>
                    <th>Active Leads</th>
                    <th>Converted</th>
                    <th class="bg-dark text-white">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agentReports as $row)
                <tr>
                    <td class="text-left pl-3 font-weight-bold text-secondary">{{ $row->agent_name }}</td>
                    
                    <!-- TRIGGER NG AJAX -->
                    <td class="font-weight-bold">
                        <a href="javascript:void(0);" class="fetch-agent-details text-primary" data-agent="{{ $row->psc_emp_id }}" data-pscname="{{ $row->agent_name }}">
                            <u>{{ number_format($row->total_assigned) }}</u>
                        </a>
                    </td>

                    <td><span class="badge badge-warning text-dark px-3 py-2">{{ number_format($row->average_percentage, 2) }}%</span></td>
                    <td><span class="badge badge-info px-3 py-2">{{ number_format($row->total_active_leads) }}</span></td>
                    <td><span class="badge badge-success px-3 py-2">{{ number_format($row->total_converted) }}</span></td>
                    <td class="table-dark font-weight-bold">{{ number_format($row->total_amount) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- SINGLE REUSABLE MODAL WITH DATATABLE -->
<div class="modal fade" id="agentDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color:#031e23; color:white;">
                <h5 class="modal-title" style="color:aliceblue;"><i class="fas fa-list mr-2"></i> Assigned Leads: <span id="modalAgentName"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive shadow-sm rounded border p-2">
                    <table class="table table-hover align-middle mb-0 w-100" id="leadsDataTable" style="font-size: 10px;">
                        <thead class="table-dark text-uppercase tracking-wider" style="font-size: 10px;">
                            <tr>
                                <th scope="col"  style="font-size: 10px;">Company Name</th>
                                <th scope="col"  style="font-size: 10px;">Address</th>
                                <th scope="col"  style="font-size: 10px;">Lead Status</th>
                                <th scope="col"  style="font-size: 10px;">Last Update Description</th>
                                <th scope="col"  style="font-size: 10px;">Update Date</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody" class="text-secondary fs-6" style="font-size: 10px;">
                            <!-- Dito papasok ang in-update na JavaScript code -->
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- MOdal for Viewing of Updates History -->

<!-- Modal para sa Pagtingin ng Updates History -->
<!-- Modal para sa Pagtingin ng Updates History (Naka-pwesto sa Kanan) -->
<div class="modal fade" id="ViewUpdateHistory" tabindex="-1" role="dialog" aria-hidden="true">
    <!-- Pinalitan ang modal-dialog para magkaroon ng custom styles -->
    <div class="modal-dialog modal-lg" role="document" style="
        margin-right: 20px; 
        margin-left: auto; 
        margin-top: 20%; 
        transform: none;">
        
        <div class="modal-content shadow-lg" style="border-radius: 8px;">
            <div class="modal-header bg-secondary text-white py-2">
                <h6 class="modal-title font-weight-bold mb-0">
                    <i class="fas fa-history mr-2"></i> Update History Logs
                </h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3" style="max-height: 400px; overflow-y: auto;">
                <!-- Dito papasok ang table mula sa AJAX -->
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


 <!-- Ending of Modal Viewing of Updates History -->

<script>
$(document).ready(function() {
    // I-initialize ang variable para sa DataTable instance
    var leadsTable = null;

    $('.fetch-agent-details').on('click', function() {
        var agentID = $(this).data('agent');
        var pscname = $(this).data('pscname');
        $('#modalAgentName').text(pscname);
        
        // 1. Kung may umiiral nang DataTable instance, sirain (destroy) muna ito para malinis ang memory
        if ($.fn.DataTable.isDataTable('#leadsDataTable')) {
            $('#leadsDataTable').DataTable().destroy();
        }

        // Magpakita ng loading text bago buksan ang modal
        $('#modalTableBody').html('<tr><td colspan="8" style="font-size: 10px;" class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading details, please wait...</td></tr>');
        $('#agentDetailsModal').modal('show');

        // 2. Patakbuhin ang AJAX Data Render
        $.ajax({
            url: "{{ route('reports.agent.details') }}",
            type: "GET",
            data: { psc_emp_id: agentID },
            dataType: "json",
            success: function(response) {
                var html = '';
                
              if(response.length > 0) {
                            $.each(response, function(index, row) {

                                var badgeClass2 = 'badge-secondary';
                                var customStyle = ''; // Gagawa ng lalagyan para sa custom color style

                                if (row.contact_source === 'Inquiry') {
                                    // Pwede mong palitan ang hex color base sa shade ng pink na gusto mo
                                    customStyle = 'style="background-color: #e685b5; color: white;"'; 
                                } else if (row.contact_source === 'PhilConstruct') {
                                    badgeClass2 = 'badge-success';
                                } else if (row.contact_source) {
                                    badgeClass2 = 'badge-info';
                                }

                                html += '<tr>';
                                // Idagdag ang customStyle variable sa loob ng <span> tag
                                html += '<td class="text-left "><span class="badge ' + badgeClass2 + ' px-2 py-1" ' + customStyle + '>' + (row.contact_source ?? 'No Status') + '</span><br><span class="font-weight-bold text-dark">' + (row.company_name ?? '-') + '</span></td>';

                                html += '<td class="text-left">' + (row.address ?? '-') + '</td>';
                                
                                // Lagyan natin ng kulay ang Badge base sa status para mas magandang tingnan
                                var badgeClass = 'badge-secondary';
                                if(row.lead_status === 'New Lead') badgeClass = 'badge-warning text-dark';
                                else if(row.lead_status === 'Converted') badgeClass = 'badge-success';
                                else if(row.lead_status) badgeClass = 'badge-info';

                                // Palitan ang lumang linya ng icon nito:
                                html += '<td><span class="badge ' + badgeClass + ' px-2 py-1">' + (row.lead_status ?? 'No Status') + '</span>';
                                html += ' <i class="fas fa-history text-primary updatehistory-btn ml-2" style="cursor:pointer;" data-companyid="' + row.company_id + '" title="View History"></i></td>';
                                html += '<td class="text-left"><small>' + (row.description ?? '-') + '</small></td>';
                                html += '<td>' + (row.update_date ?? '-') + '</td>';
                                html += '</tr>';
                            });
                        } else {
                            // Dahil 5 columns na lang ang dine-display natin ngayon, papalitan din ang colspan ng 5
                            html = '<tr><td colspan="5" class="text-center text-muted py-4">No records found for this agent.</td></tr>';
                        }

                
                // Ipasok ang mga bagong rows sa table body
                $('#modalTableBody').html(html);

                // 3. I-initialize ang DataTables pagkatapos ma-render ang mga bagong HTML rows
                if(response.length > 0) {
                    $('#leadsDataTable').DataTable({
                        "paging": true,
                        "lengthChange": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "pageLength": 10, // Bilang ng rows kada pahina
                        "order": [[0, "asc"]] // I-sort muna sa Company Name (unang column)
                    });
                }
            },
            error: function() {
                $('#modalTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle mr-2"></i> Error loading data. Please try again.</td></tr>');
            }
        });
    });
});

// Listener para sa pag-click ng History Icon
$(document).on('click', '.updatehistory-btn', function() {
    var companyId = $(this).data('companyid');
    
    // Loading state sa loob ng History Modal Body
    $('#ViewUpdateHistory .modal-body').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading history logs...</div>');
    $('#ViewUpdateHistory').modal('show');

    // Patakbuhin ang pangalawang AJAX para sa History
    $.ajax({
        url: "{{ route('reports.company.history') }}",
        type: "GET",
        data: { company_id: companyId },
        dataType: "json",
       success: function(historyData) {
    if(historyData.length > 0) {
        // Palitan ang pamagat ng Modal base sa unang nahanap na pangalan ng Kumpanya
        $('#ViewUpdateHistory .modal-header h5').html('<i class="fas fa-history mr-2"></i> Update History: ' + historyData[0].company_name);
        
        // Simula ng Timeline Container
        var historyHtml = '<div class="timeline-container px-2">';

        $.each(historyData, function(i, hRow) {
            // Pagpili ng kulay ng icon at text base sa lead status
            var statusColor = 'secondary';
            var iconClass = 'fa-circle';
            
            if(hRow.lead_status === 'New Lead') {
                statusColor = 'warning text-dark';
                iconClass = 'fa-star';
            } else if(hRow.lead_status === 'Converted') {
                statusColor = 'success';
                iconClass = 'fa-check-circle';
            } else if(hRow.lead_status) {
                statusColor = 'info';
                iconClass = 'fa-comments';
            }

            // Isang Card/Item sa loob ng Timeline
            historyHtml += '<div class="timeline-item d-flex mb-4 position-relative">';
            
            // Ang linyang nagdudugtong sa mga log (maliban sa huling item)
            if (i < historyData.length - 1) {
                historyHtml += '<div class="position-absolute bg-light" style="width: 2px; top: 24px; bottom: -24px; left: 11px; z-index: 1;"></div>';
            }

            // Icon ng Status
            historyHtml += '  <div class="mr-3 position-relative" style="z-index: 2;">';
            historyHtml += '    <span class="badge badge-' + (statusColor.includes('text-dark') ? 'warning' : statusColor) + ' rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 24px; height: 24px;">';
            historyHtml += '      <i class="fas ' + iconClass + '" style="font-size: 11px;"></i>';
            historyHtml += '    </span>';
            historyHtml += '  </div>';

            // Detalye ng Log (Kanan)
            historyHtml += '  <div class="flex-grow-1 bg-light rounded p-3 shadow-xs" style="border-left: 4px solid var(--' + (statusColor.includes('text-dark') ? 'warning' : statusColor) + ');">';
            historyHtml += '    <div class="d-flex justify-content-between align-items-center mb-1">';
            historyHtml += '      <span class="badge badge-' + statusColor + ' px-2 py-1 text-uppercase font-weight-bold" style="font-size: 9px;">' + (hRow.lead_status ?? 'No Status') + '</span>';
            historyHtml += '      <small class="text-muted font-italic"><i class="far fa-clock mr-1"></i>' + (hRow.update_date ?? '-') + '</small>';
            historyHtml += '    </div>';
            historyHtml += '    <p class="mb-2 text-dark font-weight-normal font-sm" style="font-size: 12px; line-height: 1.4;">' + (hRow.description ?? 'No description provided.') + '</p>';
            historyHtml += '    <div class="text-right border-top pt-1 mt-1" style="border-color: #e9ecef !important;">';
            historyHtml += '      <small class="text-secondary font-weight-bold" style="font-size: 10px;"><i class="fas fa-user-edit mr-1"></i>By: ' + (hRow.user_name ? hRow.user_name : (hRow.updated_by ?? '-')) + '</small>';
            historyHtml += '    </div>';
            historyHtml += '  </div>';
            
            historyHtml += '</div>'; // Wakas ng isang item
        });

        historyHtml += '</div>'; // Wakas ng Container
        
        $('#ViewUpdateHistory .modal-body').html(historyHtml);
    } else {
        $('#ViewUpdateHistory .modal-body').html('<div class="text-center py-4 text-muted"><i class="fas fa-folder-open fa-2x mb-2 d-block"></i>No history logs found.</div>');
    }
},

        error: function() {
            $('#ViewUpdateHistory .modal-body').html('<p class="text-center text-danger">Failed to fetch history. Please try again.</p>');
        }
    });
});


</script>




@endsection
