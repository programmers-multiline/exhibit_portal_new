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
                    <th>New Leads</th>
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

                    <td><span class="badge badge-warning text-dark px-3 py-2">{{ number_format($row->total_new_lead) }}</span></td>
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
                                html += '<tr>';
                                html += '<td class="text-left font-weight-bold text-dark">' + (row.company_name ?? '-') + '</td>';
                                html += '<td class="text-left">' + (row.address ?? '-') + '</td>';
                                
                                // Lagyan natin ng kulay ang Badge base sa status para mas magandang tingnan
                                var badgeClass = 'badge-secondary';
                                if(row.lead_status === 'New Lead') badgeClass = 'badge-warning text-dark';
                                else if(row.lead_status === 'Converted') badgeClass = 'badge-success';
                                else if(row.lead_status) badgeClass = 'badge-info';

                                html += '<td><span class="badge ' + badgeClass + ' px-2 py-1">' + (row.lead_status ?? 'No Status') + '</span></td>';
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
</script>


@endsection
