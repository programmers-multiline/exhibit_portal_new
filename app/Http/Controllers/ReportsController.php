<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    //
    public function index()
    {
       $reports = DB::table('attendance')
            ->selectRaw("
                YEAR(date) as year_per_participant,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'WorldBex' THEN attendance.company_id END) as worldbex,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHILCONSTRUCT' THEN attendance.company_id END) as philconstruct,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHA' THEN attendance.company_id END) as pha,
                COUNT(DISTINCT attendance.company_id) as total_leads
            ")
            ->leftJoin('contacts_update', 'contacts_update.company_id', '=', 'attendance.company_id')
            ->leftJoin('lead_agent_status', 'lead_agent_status.id', '=', 'contacts_update.status')
            ->groupBy(DB::raw("YEAR(date)"))
            ->orderBy('year_per_participant', 'desc')
            ->get();


       // return view('reports.index', compact('reports'));

          $reports_per_WorldBex = DB::table('attendance')
            ->selectRaw("
                YEAR(date) AS year_per_exhibit,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'WorldBex' THEN attendance.company_id END) AS worldbex_attendees,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'WorldBex' AND lead_status='New Lead' THEN attendance.company_id END) AS 'New_Lead',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'WorldBex' AND lead_status<>'New Lead' AND lead_status<>'Converted' THEN attendance.company_id END) AS 'Active_Leads',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'WorldBex' AND lead_status='Converted' THEN attendance.company_id END) AS 'Converted',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'WorldBex' THEN attendance.company_id END) AS total_leads
            ")
            ->leftJoin('contacts_update', 'contacts_update.company_id', '=', 'attendance.company_id')
            ->leftJoin('lead_agent_status', 'lead_agent_status.id', '=', 'contacts_update.status')
            ->groupBy(DB::raw("YEAR(date)"))
            ->orderBy('year_per_exhibit', 'desc')
            ->get();




          $reports_per_PhilConstruct = DB::table('attendance')
            ->selectRaw("
                YEAR(date) AS year_per_exhibit,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PhilConstruct' THEN attendance.company_id END) AS PhilConstruct_attendees,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PhilConstruct' AND lead_status='New Lead' THEN attendance.company_id END) AS 'New_Lead',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PhilConstruct' AND lead_status<>'New Lead' AND lead_status<>'Converted' THEN attendance.company_id END) AS 'Active_Leads',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PhilConstruct' AND lead_status='Converted' THEN attendance.company_id END) AS 'Converted',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PhilConstruct' THEN attendance.company_id END) AS total_leads
            ")
            ->leftJoin('contacts_update', 'contacts_update.company_id', '=', 'attendance.company_id')
            ->leftJoin('lead_agent_status', 'lead_agent_status.id', '=', 'contacts_update.status')
            ->groupBy(DB::raw("YEAR(date)"))
            ->orderBy('year_per_exhibit', 'desc')
            ->get();



             $reports_per_PHA = DB::table('attendance')
            ->selectRaw("
                YEAR(date) AS year_per_exhibit,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHA' THEN attendance.company_id END) AS PHA_attendees,
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHA' AND lead_status='New Lead' THEN attendance.company_id END) AS 'New_Lead',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHA' AND lead_status<>'New Lead' AND lead_status<>'Converted' THEN attendance.company_id END) AS 'Active_Leads',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHA' AND lead_status='Converted' THEN attendance.company_id END) AS 'Converted',
                COUNT(DISTINCT CASE WHEN exhibit_name = 'PHA' THEN attendance.company_id END) AS total_leads
            ")
            ->leftJoin('contacts_update', 'contacts_update.company_id', '=', 'attendance.company_id')
            ->leftJoin('lead_agent_status', 'lead_agent_status.id', '=', 'contacts_update.status')
            ->groupBy(DB::raw("YEAR(date)"))
            ->orderBy('year_per_exhibit', 'desc')
            ->get();

            


       return view('reports.index', compact('reports', 'reports_per_WorldBex', 'reports_per_PhilConstruct','reports_per_PHA' ));
    }


//Agent Report
   public function agentreport()
{
    $user = Auth::user();   
    
    // Subquery para makuha ang PINAKAHULING update lamang kada kumpanya (iwas double counting)
    $latestUpdates = DB::table('contacts_update as cu')
        ->select('cu.company_id', 'cu.status')
        ->whereIn('cu.id', function($query) {
            $query->select(DB::raw('MAX(id)'))
                  ->from('contacts_update')
                  ->groupBy('company_id');
        });

    $agentReports = DB::table('assigned_agent as a')
        // I-join ang subquery sa halip na ang buong table
        ->leftJoinSub($latestUpdates, 'cu', function ($join) {
            $join->on('cu.company_id', '=', 'a.company_id');
        })
        ->leftJoin('lead_agent_status as l', 'l.id', '=', 'cu.status')
        ->leftJoin('users as u', 'u.emp_id', '=', 'a.psc_emp_id')
        ->select(
            'a.psc_name as agent_name',
            'a.psc_emp_id as psc_emp_id',
            DB::raw('COUNT(DISTINCT a.company_id) as total_assigned'),
            DB::raw("COUNT(DISTINCT CASE WHEN l.lead_status = 'New Lead' THEN a.company_id END) as total_new_lead"),
            DB::raw("COUNT(DISTINCT CASE WHEN l.lead_status NOT IN ('New Lead', 'Converted') AND l.lead_status IS NOT NULL THEN a.company_id END) as total_active_leads"),
            DB::raw("COUNT(DISTINCT CASE WHEN l.lead_status = 'Converted' THEN a.company_id END) as total_converted"),
            DB::raw('COUNT(DISTINCT a.company_id) as total_amount') 
        )
        ->whereNotNull('a.psc_name')
        ->where('a.psc_name', '<>', '')
        // Tiyakin kung emp_id o group_id dapat ang ikukumpara rito:
       // ->where('u.group_id', '=', $user->emp_id) 
           // KONDISYON 1: Kung ang position_id ay 157
        ->when($user->position_id == 157, function ($query) use ($user) {
                return $query->where('a.psc_emp_id', $user->emp_id);
            })
            // KONDISYON 2: Kung ang position_id ay 13
        ->when(in_array($user->position_id, [13, 158]), function ($query) use ($user) {
            return $query->whereIn('u.group_id', [$user->emp_id]);
        })
        ->groupBy('a.psc_name', 'a.psc_emp_id')
        // Gumamit ng DB::raw sa orderBy para sa mga computed fields sa MySQL strict mode
        ->orderBy(DB::raw('COUNT(DISTINCT a.company_id)'), 'desc')
        ->get();

    return view('reports.agent', compact('agentReports'));
}


    public function getAssignedDetails(Request $request)
    {
        $psc_emp_id = $request->get('psc_emp_id');

        if (!$psc_emp_id) {
            return response()->json(['error' => 'Agent Employee ID is required'], 400);
        }

        // 1. Gawin muna ang subquery para sa pinakahuling contacts_update
        $subquery = DB::table('contacts_update')
            ->select([
                'company_id',
                'status',
                'description',
                'update_date',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY company_id ORDER BY id DESC) as rn')
            ]);

        // 2. Buuin ang main query gamit ang leftJoinSub
        $details = DB::table('assigned_agent as a')
            ->leftJoin('company_list as c', 'c.id', '=', 'a.company_id')
            ->leftJoinSub($subquery, 'StatusUpdate', function ($join) {
                $join->on('StatusUpdate.company_id', '=', 'a.company_id')
                    ->where('StatusUpdate.rn', '=', 1);
            })
            ->leftJoin('lead_agent_status as l', 'l.id', '=', 'StatusUpdate.status')
            ->select([
                'a.psc_emp_id',
                'c.company_name',
                'c.address',
                'l.lead_status',
                'StatusUpdate.description',
                'StatusUpdate.update_date'
            ])
            ->where('a.psc_emp_id', '=', $psc_emp_id)
            ->get();


        return response()->json($details);
    }
}
