<?php

namespace App\Http\Controllers;

use App\Models\Participants;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Company;
use App\Models\ExternalUser;
use Illuminate\Support\Facades\DB;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

Paginator::useBootstrap();

class CompanyController extends Controller
{

public function getContacts($id)
{
    $contacts = DB::table('participants')
        ->where('company_id', $id)
        ->select(
            'id',
            'participant_name',
            'participant_email',
            'participant_contact',
            'participant_position'
        )
        ->orderBy('participant_name', 'asc')
        ->get();

    return response()->json($contacts);
}

public function Contacts_details($id)
{
    $contacts = DB::table('participants')
        ->where('id', $id)
        ->select(
            'id',
            'participant_name',
            'participant_email',
            'participant_contact',
            'participant_position'
        )
        ->orderBy('participant_name', 'asc')
        ->get();

    return response()->json($contacts);
}



public function getCompany($id)
{
  $company = DB::table('company_list')
    ->leftJoin('ph_address as p', 'company_list.city_province_code', '=', 'p.cor_code')
    ->where('company_list.id', $id)
    ->select(
        'company_list.*',
        'p.address_name as city_province'
    )
    ->first();

return response()->json($company);
    
}

 

public function updateAddress(Request $request)
{
    
    //dd($user->emp_id);
    $request->validate([
        'company_id' => 'required|string',
        'address'    => 'required|string'
    ]);

    // Gagamit ng transaction para ligtas ang sabay na operasyon
    DB::transaction(function () use ($request) {
        
        // 1. Kunin muna ang kasalukuyang address (ito ang magiging previous_address)
        $company = DB::table('company_list')
            ->where('id', $request->company_id)
            ->first();

        if ($company) {
            $previousAddress = $company->address;

            // 2. I-update ang address sa company_list
            DB::table('company_list')
                ->where('id', $request->company_id)
                ->update([
                    'address' => $request->address
                ]);

            $user  = Auth::user();
            // 3. I-insert ang log sa update_address_logs table
            DB::table('update_address_logs')->insert([
               'company_id'       => $request->company_id,
               'previous_address' => blank($previousAddress) ? 'No Previous Address' : $previousAddress,
               'current_address'  => $request->address,
               'updated_by'       => $user->emp_id,                                                        // Palitan ng ID ng naka-login na user
               'created_at'       => now(),
               'updated_at'       => now(),
            ]);
        }
    });

    return response()->json(['success' => true]);
}



public function UpdateContactDetails(Request $request)
{
    $request->validate([
        'participant_name'    => 'required|string',
        'participant_contact' => 'required|string'
    ]);

    Participants::where('id', $request->p_id)
        ->update([
            'participant_name'    => $request->participant_name,
            'participant_email'   => $request->participant_email,
            'participant_contact' => $request->participant_contact
        ]);

    return response()->json([
        'success' => true
    ]);
}

public function saveCompany(Request $request)
{
    $request->validate([
        'company_name' => 'required|unique:company_list,company_name'
    ]);

    $company = \App\Models\Company::create([
        'company_name' => $request->company_name
    ]);

    return response()->json([
        'success' => true,
        'company' => $company
    ]);
}    

public function index(Request $request)
{

$user  = Auth::user();
$users = ExternalUser::getUsersWithCompanyAndDepartment();
 //console.log($users);
//dd($users);
//$empIds = $users->pluck('emp_id');
    
//dd('Test');

    if ($request->ajax()) {
    $participants = Participants::select(
        'participants.company_id',
        'participants.participant_company',
        'participants.participant_name',
        'participants.participant_contact',
        'participants.participant_email',
        'company_list.address',
        'company_list.company_name',
        'assigned_agent.psc_name'
    )
    ->leftJoin('company_list', 'participants.company_id', '=', 'company_list.id')
    ->leftJoin('assigned_agent', 'company_list.id', '=', 'assigned_agent.company_id')
   // ->whereNotNull('participants.company_id')
    ->where('assigned_agent.assigned_by',$user->emp_id)
    ->orderBy('participants.participant_company')
    ->get();

// ✅ tamang grouping
$grouped = $participants->groupBy('company_id');

$data = [];

foreach ($grouped as $companyId => $contacts) {

    $contactList = '';

    foreach ($contacts as $index => $contact) {
        $contactList .= ($index + 1) . '. '
            . $contact->participant_name . ' - '
            . $contact->participant_contact . ' - '
            . $contact->participant_email . '<br>';
    }

    $data[] = [
        // ✅ kunin name from first row
        'checkbox'        => '<input type="checkbox" class="participant_checkbox" value="'.$companyId.'">',
        'company_name'    => $contacts->first()->company_name  ?? '',
        'contact_persons' => $contactList,
        'company_address' => $contacts->first()->address ?? '',
        
        // optional: agent
        'psc_name' => $contacts->pluck('psc_name')
                              ->filter()
                              ->unique()
                              ->implode(', ')
    ];
}

    return DataTables::of($data)
    ->addIndexColumn()
    ->rawColumns(['checkbox', 'contact_persons'])
    ->make(true);
    }

  /*   $users = \App\Models\User::whereIn('department_id',[16, 64, 27])
    ->where('company_id', 3)
    ->where('status', 1)
    ->orderBy('first_name')->get(); */

    return view('companies.index', compact('users'));
   // return view('products.create', compact('lead_agent_status'));

    //return view('companies.index');
}

/* public function companyCard()
{
    return view('companies.company_card');
} */

    public function companyCard()
{
    //$users = ExternalUser::getUsersWithCompanyAndDepartment();
     $users = ExternalUser::getUsersWithCompanyAndDepartment();
    //dd($user->emp_id);

    $lead_agent_status = DB::table('lead_agent_status')->get();

    return view('companies.company_card', compact('users','lead_agent_status'));
}


    public function companyCardList(Request $request)
{
    //$emp_id = session('emp_id');
    $emp_id   = auth()->user()->emp_id;
    $group_id = auth()->user()->group_id;
  // dd($emp_id);
    $search  = $request->search;
    $viewAll = $request->view_all;
    
//,'p.address_name as city_province'
//Ito naka Left join
$companies = \App\Models\Company::select(
    'company_list.id',
    'company_list.company_name',
    'company_list.address',
    'p.address_name as city_province')
    ->join('assigned_agent as a', 'a.company_id', '=', 'company_list.id')
    ->join('users as b', 'b.emp_id', '=', 'a.psc_emp_id')
   ->leftJoin('ph_address as p', 'p.cor_code', '=', 'company_list.city_province_code')
    ->where('a.psc_emp_id', $emp_id)
    ->orWhere('a.assigned_by', $emp_id)
    ->when($search, function($query) use ($search){
        $query->where('company_name','like',"%{$search}%")
              ->orWhereHas('participants', function($q) use ($search){
                  $q->where('participant_name','like',"%{$search}%")
                    ->orWhere('participant_email','like',"%{$search}%")
                    ->orWhere('participant_contact','like',"%{$search}%");
        });
    })
   
    ->with(['participants.images','assignedAgent','participants.updates'])


    ->paginate($viewAll ? 1000 : 6);
 
 //dd($companies);
   $companies->getCollection()->transform(function($company) {

    //$assignedAgentId = $company->assignedAgent->psc_emp_id ?? 512;

   $companyId = $company->id ?? null;

    $latestUpdates = [];

    if($companyId){
       /*  $updates = DB::table('participants_update')
            ->where('updated_by', $assignedAgentId)
            ->orderByDesc('update_date')
            ->limit(1) // optional
            ->get(); */
    $updates = DB::table('participants_update as pu')
    ->leftJoin('lead_agent_status as las', 'pu.status', '=', 'las.id') 
   ->where('pu.participant_id', $companyId)
    //->where('pu.participant_id ', $assignedAgentId)
    ->orderByDesc('pu.update_date')
    ->limit(1)
    ->select(
        'pu.status',
        'las.lead_status as lead_status',
        'pu.description',
        'pu.update_date'
    )
    ->get();

        foreach($updates as $u){
            $latestUpdates[] = [
                'status'      => $u->status,
                'description' => $u->description,
                'lead_status' => $u->lead_status,
                'update_date' => $u->update_date,
            ];
        }

       // dd($updates);
    }

    $company->latest_updates = $latestUpdates;

    return $company;
});
    //Ending

    return response()->json($companies);
}


}