@extends('layouts.app')

@section('content')

<div class="container-fluid mt-4">
<div class="container mt-5">
    <h2 class="mb-4">Inquiry Masterlist</h2>
    <table id="tbl_inquiry" class="table table-striped table-hover align-middle nowrap w-100" style="margin-top: 15px !important;">
        <thead>
            <tr>
                <th>No</th>
                <th>Client Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Address</th>
                <th>Contact No</th>
                <th>Product</th>
                <th>Category</th>
                <th>Inquiry Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
</div>
<!-- Ending of container-fluid  -->



<script type="text/javascript">
$(document).ready(function(){
loadAttendance();
});

function loadAttendance()
{
    // ISANG MAHIGPIT NA CHECK KUNG MERON NANG KASALUKUYANG DATATABLE
    if ($.fn.dataTable.isDataTable('#tbl_inquiry')) {
        $('#tbl_inquiry').DataTable().destroy(); // Manu-manong burahin ang lumang instance
        $('#tbl_inquiry').empty(); // Linisin ang HTML cache ng table
    }

    // Muling itayo ang table mula sa simula
    $('#tbl_inquiry').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inquiries.index') }}",
            data: function (d) {
                d.startDate = $('#start').val();
                d.endDate = $('#end').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'client_name', name: 'inqr.client_name'},
            {data: 'client_email', name: 'inqr.contact_email'},
            {data: 'client_company', name: 'inqr.company_name'},
            {data: 'client_address', name: 'inqr.location'},
            {data: 'client_contact_no', name: 'inqr.contact_no'},
            {data: 'product_name', name: 'prod.product_name'},
            {data: 'category_name', name: 'cat.category_name'},
            {data: 'inquiry_date', name: 'inq.inquiry_date'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
}


</script>




@endsection