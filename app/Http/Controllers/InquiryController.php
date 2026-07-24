<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ExternalUser;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
     $user = Auth::user();  

    if ($request->ajax()) {

    //dd($request->all());

       $data = DB::connection('mysql_third')->table('rfq_tbl_inquiry AS inq')
    ->leftJoin('rfq_tbl_inquirer AS inqr', 'inqr.inquirer_id', '=', 'inq.inquirer_id')
    ->leftJoin('rfq_tbl_products AS prod', 'prod.product_id', '=', 'inq.product_id')
    ->leftJoin('rfq_category AS cat', 'cat.category_code', '=', 'inqr.category')
    ->leftJoin('rfq_tbl_pages AS comp', 'comp.company_id', '=', 'inq.company_id') // Bagong join table base sa iyong query
    ->leftJoin('rfq_events as e', 'inq.event','=','e.event_id')
    ->select([
        'inqr.inquirer_id AS inquirer_id',
        'inqr.client_name AS client_name',
        'inqr.contact_email AS client_email', 
        'inqr.company_name AS client_company', 
        'inqr.location AS client_address',
        'inqr.contact_no AS client_contact_no',
        'inq.inquiry_date AS inquiry_date',
        'inq.assigned_psc AS assigned_psc',
        'inq.assigned_psc_name AS assigned_psc_name',
        'e.event_code',
        'prod.product_name',
        'cat.category_name',
        'comp.c_id AS company_id',         // Alias para sa comp.c_id
        'comp.company_name AS company_name' // Alias para sa comp.company_name
    ])
    ->where('inq.status', 1)
    ->where('inqr.status', 1)
    ->where('prod.status', 1)
    ->where('comp.c_id', $user->company_id)
    


    ->orderBy('inqr.inquirer_id', 'DESC');

                    // Date Filter
                if ($request->filled('startDate') && $request->filled('endDate')) {

                    $data->whereBetween('inq.inquiry_date', [
                        $request->startDate . ' 00:00:00',
                        $request->endDate . ' 23:59:59'
                    ]);

                } elseif ($request->filled('startDate')) {

                    $data->whereDate('inq.inquiry_date', '>=', $request->startDate);

                } elseif ($request->filled('endDate')) {

                    $data->whereDate('inq.inquiry_date', '<=', $request->endDate);

                }

                // PSC Filter
                if ($request->filled('pscFilter')) {

                    if ($request->pscFilter == 'assigned') {

                        $data->whereNotNull('inq.assigned_psc');

                    } elseif ($request->pscFilter == 'unassigned') {

                        $data->whereNull('inq.assigned_psc');

                    }

                }


        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function($row){
                return '<button class="btn btn-sm btn-primary">View</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
  $users = ExternalUser::getUsersWithCompanyAndDepartment();

  
 
    return view('inquiries.index', compact('users')); // Siguraduhing tama ang view path mo dito
    }
}

