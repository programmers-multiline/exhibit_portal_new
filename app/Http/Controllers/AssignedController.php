<?php

namespace App\Http\Controllers;


use App\Models\Participants;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ParticipantImage;
use App\Models\ParticipantsUpdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ExternalUser;
use App\Models\AssignedAgent;
use App\Models\AssignedAgentLog;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ParticipantsImport;
use Illuminate\Support\Facades\Auth;

use App\Mail\ParticipantBrochureMail;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\ParticipantFile;
use App\Models\product_list;




class AssignedController extends Controller
{


       public function index(Request $request)
        {
              $user       = Auth::user();

             // dd($user->position_id);
            $group_id   = $user->group_id;

$rawResults = DB::table('company_list as cm') // 1. Main table is already cm
    ->select([
        'cm.id as company_id', // Fixed: Changed from c.id to cm.id
        'cm.company_name',
        'cm.address',
        'cm.assigned_psc',
        'c.id as Contactid',
        'c.entry_by',
        'c.name as ContactPerson',
        'c.phone as ContactPhone',
        'c.email as ContactEmail',
        'a.psc_name as AgentName',
        'cu.status as ContactUpdate',
        'cu.description as UpdateRemarks',
        'cu.created_at as UpdateTime',
        'cf.file_path as file_path',
        'l.lead_status',
        'u.position_id',
        'l.status_percentage',
        'u.group_id'
    ])
    // 2. Removed the duplicate leftJoin('company_list as cm'...) from here
    ->leftJoin('contacts as c', 'c.company_id', '=', 'cm.id')
    ->leftJoin('contacts_update as cu', 'cu.company_id', '=', 'cm.id')
    ->leftJoin('contacts_files as cf', 'cf.company_id', '=', 'cu.company_id')
    ->leftJoin('assigned_agent as a', 'a.company_id', '=', 'cm.id')
    ->leftJoin('lead_agent_status as l', 'l.id', '=', 'cu.status')
    ->leftJoin('users as u', 'u.emp_id', '=', 'a.psc_emp_id')
    ->where(function($query) {
        $query->whereNotNull('a.psc_name')
              ->where('a.psc_name', '<>', '');
    })
    // KONDISYON 1: Kung ang position_id ay 157
    ->when($user->position_id == 157, function ($query) use ($user) {
        return $query->where('cm.assigned_psc', $user->emp_id);
    })
    // KONDISYON 2: Kung ang position_id ay 13
   ->when(in_array($user->position_id, [13, 158]), function ($query) use ($user) {
    return $query->whereIn('u.group_id', [$user->emp_id]);
})

    ->get();
//dd($rawResults);


                             /*  --WHERE cm.company_name = 'ERGOTECH'  -- O gamitin ang dynamic filter mo */

// I-group ang mga contact persons sa loob ng iisang object ng kompanya
$companies = collect($rawResults)->groupBy('company_id')->map(function ($rows) {
    $first = $rows->first();
     $last = $rows->last();
    //dd($first->UpdateRemarks);
    return [
        'company_id'        => $first->company_id,
        'company_name'      => $first->company_name,
        'address'           => $first->address ?? 'No Address',
        'AgentName'         => $first->AgentName ?? 'No Agent Assigned',
        'assigned_agent_id' => $first->psc_emp_id ?? $first->assigned_psc,
        'ContactUpdate'     => $last->ContactUpdate ?? 'No Update Yet',
        'lead_status'       => $last->lead_status ?? 'No Update Yet',
        'status_percentage' => $last->status_percentage ?? 'No Percent',
        'UpdateRemarks'     => $last->UpdateRemarks ?? 'No Remarks Available',
        'UpdateTime'        => $last->UpdateTime ?? '--',
        'file_path'         => $last->file_path ?? 'No File',
        

        'contacts'          => $rows->map(function($row) {
            return [
                'id'    => $row->Contactid,
                'name'  => $row->ContactPerson,
                'phone' => $row->ContactPhone,
                'email' => $row->ContactEmail
            ];
        })->filter(fn($c) => !empty($c['name']))->values()
    ];
}); // 💡 TINANGGAL ANG ->first() DITO PARA MAKUHA LAHAT NG KOMPANYA

 $lead_agent_status = DB::table('lead_agent_status')->get();
 $user_group        = DB::table('users')->where('group_id',$user->emp_id)->get();
 $contact_files     = DB::table('contacts_files')->get();

 //use to refresh the Card page
 if ($request->ajax()) {
    return response()->json([
        'companies' => $companies->values() // .values() para maging malinis na array sa JS
    ]);
}

return view('assigned.index', compact('companies','lead_agent_status','user_group','user','contact_files'));
   
       // return view('assigned.index');
    
        }

       
public function getCompanyFiles($company_id)
{
    $files = DB::table('contacts_files as cf')
        ->select([
            'cf.file_path',
            'cf.file_name',
            'u.name as uploaded_by_name',
            'cf.uploaded_at' // Pinalitan ko ng uploaded_at base sa model mo kanina, baguhin kung created_at talaga sa db
        ])
        ->leftJoin('users as u', 'u.emp_id', '=', 'cf.uploaded_by')
        ->where('cf.company_id', $company_id)
        ->get();

    return response()->json($files);
}


//removal function
public function bulkRemovePsc(Request $request)
{
    $companyIds = $request->input('company_ids');
    $currentUser = Auth::user();

    if (empty($companyIds)) {
        return response()->json(['success' => false, 'message' => 'Walang napiling kumpanya.']);
    }

       try {
        DB::transaction(function () use ($companyIds, $currentUser) {
            foreach ($companyIds as $companyId) {
                // 1. Kunin muna ang kasalukuyang nakatalagang agent (para sa prev_psc_num)
                $assignedAgent = DB::table('assigned_agent')
                    ->where('company_id', $companyId)
                    ->first();

                if ($assignedAgent) {
                    // 2. Ipasok ang log sa removed_psc_logs table
                    DB::table('removed_psc_logs')->insert([
                        'company_id'     => (int)$companyId,
                        'prev_psc_num'   => (int)$assignedAgent->psc_emp_id,
                        'removed_by' => (int)$currentUser->emp_id,
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);

                    // 3. Alisin ang record mula sa assigned_agent table
                    DB::table('assigned_agent')
                        ->where('company_id', $companyId)
                        ->delete();
                }
            }
        }); 

        return response()->json(['success' => true, 'message' => 'Matagumpay na naalis at nailog ang mga napiling PSC.']);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Nagkaroon ng problema: ' . $e->getMessage()]);
    }

}







}
