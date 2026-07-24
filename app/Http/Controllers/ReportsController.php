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

public function getCompanyHistory(Request $request)
{
    $companyId = $request->company_id;

    $history = DB::table('contacts_update as c')
        ->leftJoin('company_list as cl', 'cl.id', '=', 'c.company_id')
        ->leftJoin('lead_agent_status as l', 'l.id', '=', 'c.status')
        ->leftJoin('users as u', 'u.emp_id', '=', 'c.updated_by') // Bagong join para sa pangalan ng user
        ->select(
            'cl.company_name',
            'c.company_id',
            'c.status',
            'l.lead_status',
            'c.description',
            'c.updated_by',
            'u.name as user_name', // Binigyan ng alias para hindi magkapalit sa company_name
            'c.update_date'
        )
        ->where('c.company_id', $companyId)
        ->orderBy('c.update_date', 'desc') // Pinakabagong update ang nasa itaas
        ->get();

      $product_inquiry = DB::table('product_inquiry_logs as pi')
                        ->leftJoin('product as p', 'pi.product_id', '=', 'p.id')
                        ->select(
                            'pi.company_id',
                            'pi.product_id',
                            'p.name',      // palitan kung iba ang column name
                            'pi.product_remarks'
                        )
                        ->where('pi.company_id', $companyId)
                        ->get();

    //return response()->json($history,$product_inquiry);
    return response()->json(['history'  => $history, 'products' => $product_inquiry
]);
}


    public function agentreport()
{
    $user = Auth::user();   
    
// 1. Subquery para makuha ang PINAKAHULING update lamang kada kumpanya
$latestUpdates = DB::table('contacts_update as cu')
    ->select('cu.company_id', 'cu.status')
    ->whereIn('cu.id', function($query) {
        $query->select(DB::raw('MAX(id)'))
              ->from('contacts_update')
              ->groupBy('company_id');
    });

// 2. Pangunahing query simula sa Users Table (para kasama ang walang leads)
$agentReports = DB::table('users as u')
    // LEFT JOIN sa assigned_agent para lumabas pa rin ang user kahit walang hawak na lead
    ->leftJoin('assigned_agent as a', 'a.psc_emp_id', '=', 'u.emp_id')
    // I-join ang subquery ng contacts_update
    ->leftJoinSub($latestUpdates, 'cu', function ($join) {
        $join->on('cu.company_id', '=', 'a.company_id');
    })
    ->leftJoin('lead_agent_status as l', 'l.id', '=', 'cu.status')
   
    ->select(
        'u.name as agent_name', // Kinuha sa users table
        'u.emp_id as psc_emp_id',
        'u.group_id as group_id',
      
        DB::raw('COUNT(DISTINCT a.company_id) as total_assigned'),
        DB::raw("COUNT(DISTINCT CASE WHEN l.lead_status = 'New Lead' THEN a.company_id END) as total_new_lead"),
        DB::raw("COUNT(DISTINCT CASE WHEN l.lead_status NOT IN ('New Lead', 'Converted') AND l.lead_status IS NOT NULL THEN a.company_id END) as total_active_leads"),
        DB::raw("COUNT(DISTINCT CASE WHEN l.lead_status = 'Converted' THEN a.company_id END) as total_converted"),
        DB::raw('COUNT(DISTINCT a.company_id) as total_amount'),
        
        // Ginamit ang NULLIF para maiwasan ang "Division by zero" error kung 0 ang leads ng PSC
        DB::raw('ROUND(
            COALESCE(SUM(l.status_percentage), 0) / NULLIF(COUNT(DISTINCT a.company_id), 0), 
            2
        ) as average_percentage')
    )
    ->whereNotNull('u.name')
    ->where('u.name', '<>', '')
    
    // MGA KONDISYON BASE SA POSITION_ID NG LOGGED-IN USER
    ->when($user->position_id == 157, function ($query) use ($user) {
        return $query->where('u.emp_id', $user->emp_id);
    })
    ->when(in_array($user->position_id, [13, 158]), function ($query) use ($user) {
        return $query->where('u.group_id', $user->emp_id);
    })
    
    // Naka-grupo muna sa group_id para magkakasama ang magkakateam
    ->groupBy('u.group_id', 'u.emp_id', 'u.name')
    ->orderBy('u.group_id', 'asc')
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
            ->leftJoin('contacts as co','co.company_id','=','c.id')
            ->select([
                'a.psc_emp_id',
                'c.company_name',
                'co.exhibit_name as contact_source',
                'c.id as company_id',
                'c.address',
                'l.lead_status',
                'StatusUpdate.description',
                'StatusUpdate.update_date'
            ])
            ->distinct()
            ->where('a.psc_emp_id', '=', $psc_emp_id)
            
            ->get();


        return response()->json($details);
    }
}
