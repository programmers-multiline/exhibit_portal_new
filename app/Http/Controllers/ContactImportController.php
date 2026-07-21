<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\ContactDuplicate;
use App\Models\Attendance;
use App\Models\Company;
use Carbon\Carbon;
use App\Models\ExhibitName;
use App\Http\Controllers\UserLoginController;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ExternalUser;
use App\Models\AssignedAgent;
use App\Models\AssignedAgentLog;
use App\Models\ContactsFile;
use App\Models\ContactsUpdate;
use App\Models\OpportunityLostReasonLog;
use App\Models\product_list;
use App\Models\ProductInquiryLogs;

class ContactImportController extends Controller
{

/*     public function ViewContacts(Request $request)
{
    $user = Auth::user();    
       
    if ($request->ajax()) {
        // 1. Simulan ang query gamit ang variable ($query) at huwag lagyan ng semicolon o get() sa dulo
        $query = DB::table('contacts')
            ->leftJoin('users', 'contacts.entry_by', '=', 'users.emp_id')
            ->leftJoin('company_list', 'company_list.id', '=', 'contacts.company_id')
            ->leftJoin('assigned_agent', 'company_list.id', '=', 'assigned_agent.company_id')
            ->leftJoin('users as ua', 'assigned_agent.psc_emp_id', '=', 'ua.emp_id')
            ->select([
                'contacts.id',
                'contacts.entry_by',
                'contacts.exhibit_name',
                'contacts.date',
                'contacts.time',
                'contacts.name AS contact_name',   
                'contacts.company_id',
                'company_list.company_name',
                'company_list.address',
                'contacts.title',
                'contacts.phone',
                'contacts.email', 
                'contacts.remarks', 
                'users.name AS Entry_by',
                'assigned_agent.psc_name',
                'ua.last_name'       
            ]) 
            ->where('company_status', '=', 1); 

        // 2. Date Filtering Logic para sa Contacts
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('contacts.date', [$request->startDate, $request->endDate]);
        } elseif ($request->filled('startDate')) {
            $query->where('contacts.date', '>=', $request->startDate);
        } elseif ($request->filled('endDate')) {
            $query->where('contacts.date', '<=', $request->endDate);
        }  

        // 3. Position Filter Logic (Makikita lang ng user ang sarili niyang entry kapag HINDI siya position 13 o 237)
        $query->when(!in_array($user->position_id, [13, 237, 158]), function ($q) use ($user) {
            return $q->where('contacts.entry_by', $user->emp_id);
        });
        
        // 4. I-pasa ang Query Builder ($query) nang direkta sa DataTables
        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                if (!empty($row->psc_name)) {
                    // Inayos ang font-size style na may 'px'
                    return '<span class="btn btn-sm btn btn-outline-success">'
                               .$row->last_name.
                           '</span>';
                } else if (in_array(auth()->user()->position_id, [13, 237, 158])) {
                    return '<input type="checkbox" class="participant_checkbox" value="'.$row->company_id.'">';
                } else {
                    return '--';
                }
            })
            ->addColumn('action', function($row){
                //return '<a href="#" class="btn btn-sm btn-primary">Edit</a>';
                return '<i class="fas fa-edit" data-id="'.$row->id.'" style="cursor:pointer; color:red; font-size:14px;" title="Click to Edit"></i>';
            })
            ->rawColumns(['checkbox','action']) 
            ->make(true);
    }

    $users = ExternalUser::getUsersWithCompanyAndDepartment();
    return view('contacts.viewcontacts', compact('users')); 
} */

public function ViewContacts(Request $request)
{
    $user          = Auth::user();
    $addressFilter = $request->addressFilter??'Test';


    if ($request->ajax()) {
        // 1. Simulan ang query gamit ang variable ($query) at huwag lagyan ng semicolon o get() sa dulo
        $query = DB::table('contacts')
            ->leftJoin('users', 'contacts.entry_by', '=', 'users.emp_id')
            ->leftJoin('company_list', 'company_list.id', '=', 'contacts.company_id')
            ->leftJoin('assigned_agent', 'company_list.id', '=', 'assigned_agent.company_id')
            ->leftJoin('users as ua', 'assigned_agent.psc_emp_id', '=', 'ua.emp_id')
            ->select([
                'contacts.id',
                'contacts.entry_by',
                'contacts.exhibit_name',
                'contacts.date',
                'contacts.time',
                'contacts.name AS contact_name',   
                'contacts.company_id',
                'company_list.company_name',
                'company_list.address',
                'contacts.title',
                'contacts.phone',
                'contacts.email', 
                'contacts.remarks', 
                'users.name AS Entry_by',
                'assigned_agent.psc_name',
                'ua.last_name'       
            ]) 
            ->where('company_list.company_status', '=', 1); // Nilagyan ng table prefix para iwas ambiguity

                            // Address Filter
                if ($addressFilter == 'with') {
                $query->whereRaw("
                    company_list.address IS NOT NULL
                    AND TRIM(company_list.address) <> ''
                ");
            }

            if ($addressFilter == 'without') {
                $query->whereRaw("
                    company_list.address IS NULL
                    OR TRIM(company_list.address) = ''
                ");
            }

        // 2. Date Filtering Logic para sa Contacts
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('contacts.date', [$request->startDate, $request->endDate]);
        } elseif ($request->filled('startDate')) {
            $query->where('contacts.date', '>=', $request->startDate);
        } elseif ($request->filled('endDate')) {
            $query->where('contacts.date', '<=', $request->endDate);
        }  

        // [DAGDAG] 3. PSC Assignment Filter Logic
        if ($request->filled('pscFilter')) {
            if ($request->pscFilter === 'unassigned') {
                // Kapag walang nakatalagang PSC, walang record sa left join table
                $query->whereNull('assigned_agent.company_id');
            } elseif ($request->pscFilter === 'assigned') {
                // Kapag mayroon nang nakatalagang PSC agent
                $query->whereNotNull('assigned_agent.company_id');
            }
        }

        // 4. Position Filter Logic (Makikita lang ng user ang sarili niyang entry kapag HINDI siya position 13 o 237)
        $query->when(!in_array($user->position_id, [13, 237, 158]), function ($q) use ($user) {
            return $q->where('contacts.entry_by', $user->emp_id);
        });
        
        // 5. I-pasa ang Query Builder ($query) nang direkta sa DataTables
        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                if (!empty($row->psc_name)) {
                    // Inayos ang font-size style na may 'px'
                    return '<span class="btn btn-sm btn btn-outline-success">'
                               .$row->last_name.
                           '</span>';
                } else if (in_array(auth()->user()->position_id, [13, 237, 158])) {
                    return '<input type="checkbox" class="participant_checkbox" value="'.$row->company_id.'">';
                } else {
                    return '--';
                }
            })
            ->addColumn('action', function($row){
                return '<i class="fas fa-edit" data-id="'.$row->id.'" style="cursor:pointer; color:red; font-size:14px;" title="Click to Edit"></i>';
            })
            ->rawColumns(['checkbox','action']) 
            ->make(true);
    }

    $users = ExternalUser::getUsersWithCompanyAndDepartment();
    return view('contacts.viewcontacts', compact('users')); 
}






 public function ViewAttendance(Request $request)
{
   $user = Auth::user();  
   
    if ($request->ajax()) {

        // 1. Simulan ang query gamit ang variable name na $query (Huwag muna mag-get() o mag-semicolon)
        $query = DB::table('attendance')
                    ->leftJoin('users', 'attendance.entry_by', '=', 'users.emp_id')
                    ->leftJoin('company_list', 'company_list.id', '=', 'attendance.company_id')
                    ->leftJoin('assigned_agent', 'company_list.id', '=', 'assigned_agent.company_id')
                    ->leftJoin('users as ua', 'assigned_agent.psc_emp_id', '=', 'ua.emp_id')
                    ->select([
                        'attendance.id',
                        'attendance.entry_by',
                        'attendance.exhibit_name',
                        'attendance.date',
                        'attendance.time',
                        'attendance.name as contact_name',   
                        'attendance.company_id',
                        'company_list.company_name',
                        'company_list.address',
                        'attendance.title',
                        'attendance.phone',
                        'attendance.email as contact_email', 
                        'attendance.remarks',
                        'users.last_name as Entry_by',
                        'assigned_agent.psc_name',
                        'ua.last_name'         
                    ]) 
                     ->where('company_status', '=', 1); // May semicolon na rito para i-save sa variable
                   

        // 2. Date Filtering Logic (Ika-kabit sa $query variable)
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('attendance.date', [$request->startDate, $request->endDate]);
        } elseif ($request->filled('startDate')) {
            $query->where('attendance.date', '>=', $request->startDate);
        } elseif ($request->filled('endDate')) {
            $query->where('attendance.date', '<=', $request->endDate);
        }  

        // 3. Position Filter Logic (Ika-kabit pa rin sa $query variable)
        $query->when(!in_array($user->position_id, [13, 237,158]), function ($q) use ($user) {
            return $q->where('attendance.entry_by', $user->emp_id);
        });

        // 4. I-pasa ang Query object nang direkta sa DataTables (Wala nang ->get())
        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                if (!empty($row->psc_name)) {
                    return '<span class="btn btn-sm btn btn-outline-success">' // Dinagdagan ng 'px' ang font-size
                               .$row->last_name.
                           '</span>';
                } else if (in_array(auth()->user()->position_id, [13, 237, 158])) {
                    return '<input type="checkbox" class="participant_checkbox" value="'.$row->company_id.'" style="width: 14px; height: 14px; transform: scale(1.5); cursor: pointer;">';
                } else {
                    return '--';
                }
            })
            ->addColumn('action', function($row){
               // return '<a href="#" class="btn btn-sm btn-primary" data-id="'.$row->id.'">Edit</a>';
                return '<i class="fas fa-edit" data-id="'.$row->id.'" style="cursor:pointer; color:red;"></i>';
            })
            ->rawColumns(['checkbox','action']) 
            ->make(true);
    }

    $users = ExternalUser::getUsersWithCompanyAndDepartment(); 
    return view('attendance.index', compact('users')); 
}


    //
      // Ipapalabas ang upload form
    public function showForm()
    {
   
        return view('contacts/import');
    }


    public function import(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'file' => 'required|mimes:csv,txt|max:10240'
    ]);

    // 🟢 1. KUNIN ANG AKTIBONG EXHIBIT NAME
    $activeExhibit = ExhibitName::where('exhibit_status', 'Active')->first();
    
    // Tiyaking may active exhibit para maiwasan ang error, o magtakda ng default value
    $exhibitName = $activeExhibit ? $activeExhibit->exhibit_name : 'No Active Exhibit';

    $file = $request->file('file');
    $handle = fopen($file->getRealPath(), 'r');

    // Basahin at linisin ang headers
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        return response()->json(['status' => 'error', 'message' => 'Walang makuhang header sa CSV.'], 422);
    }
    $headers = array_map(function($header) {
        return strtolower(trim($header));
    }, $headers);

    $dateIndex  = array_search('date', $headers);
    $timeIndex  = array_search('time', $headers);
    
    // 🟢 ISINAAYOS: Dynamic check para sa notes o name column para sa notes field
    $notesIndex = array_search('notes', $headers);
    if ($notesIndex === false) {
        $notesIndex = array_search('name', $headers);
    }

    $textIndex = array_search('text', $headers);
    if ($textIndex === false) {
        $textIndex = array_search('codecontent', $headers);
    }

    if ($textIndex === false) {
        fclose($handle);
        return response()->json(['status' => 'error', 'message' => 'Hindi nahanap ang vCard column.'], 422);
    }

    $totalUploaded  = 0;
    $totalNew       = 0;
    $totalDuplicate = 0;
    $totalSkipped   = 0;

    while (($row = fgetcsv($handle)) !== FALSE) {
        if (empty($row) || !isset($row[$textIndex])) {
            continue;
        }

        $rawDate   = ($dateIndex !== false && isset($row[$dateIndex])) ? trim($row[$dateIndex]) : null;
        $time      = ($timeIndex !== false && isset($row[$timeIndex])) ? trim($row[$timeIndex]) : null;
        $vcardText = trim($row[$textIndex]);
        
        // 🟢 ISINAAYOS: Kunin ang text mula sa notes/name column
        $notes     = ($notesIndex !== false && isset($row[$notesIndex])) ? trim($row[$notesIndex]) : null;

        // Panigurado: Kung ang nakuha nating notes ay naglalaman ng vCard string, i-null ito
        if ($notes && str_contains(strtoupper($notes), 'BEGIN:VCARD')) {
            $notes = null;
        }

        $date = null;

        // Linisin ang Excel "###" artifacts o kakaibang symbols sa date
        if (!empty($rawDate) && !str_starts_with($rawDate, '###')) {
            if (empty($time) && str_contains($rawDate, ' ')) {
                $dateTimeParts = explode(' ', $rawDate);
                $rawDate = $dateTimeParts[0];
                $time = $dateTimeParts[1] ?? null;
            }

            try {
                $date = Carbon::parse($rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null; 
            }
        }

        $parsedVcard = $this->parseVcard($vcardText);
        $name        = $parsedVcard['FN'] ?? null;
        $phone       = $parsedVcard['CELL'] ?? null;
        
        // Kunin ang company name mula sa vCard ORG tag
        $companyName = isset($parsedVcard['ORG']) ? trim($parsedVcard['ORG']) : null;

        if (empty($name) || empty($phone)) {
            $totalSkipped++;
            continue;
        }

        // 🟢 ISINAAYOS: Simulan ang companyId bilang null para iwas crash kapag walang kumpanya ang vCard
        $companyId = null;

        // 🟢 2. INSERTION SA COMPANY LIST (IWAS DUPLICATE / AUTO SKIP KUNG MERON NA)
        if (!empty($companyName)) {
             $company = Company::firstOrCreate([
                'company_name' => $companyName
            ]);
             $companyId = $company->id;
        }

        // 🟢 3. ISAMA ANG EXHIBIT NAME AT NOTES SA DATA ARRAY
        $contactData = [
            'entry_by'     => $user->emp_id,
            'exhibit_name' => $exhibitName,
            'date'         => $date,
            'time'         => $time,
            'name'         => $name,
            'company_id'   => $companyId,
            'company'      => $companyName,
            'title'        => $parsedVcard['TITLE'] ?? null,
            'phone'        => $phone,
            'email'        => $parsedVcard['EMAIL'] ?? null,
            'remarks'      => $notes,
        ];

        // Suriin ang duplicate gamit ang Name at Phone
        $isDuplicate = Contact::where('name', $name)
                              ->where('phone', $phone)
                              ->exists();

        if ($isDuplicate) {
            ContactDuplicate::create($contactData);
            $totalDuplicate++;
        } else {
            Contact::create($contactData);
            $totalNew++;
        }

        // Check kung may existing attendance na sa parehong araw
        $isDuplicateContact = Attendance::where('name', $name)
                              ->where('phone', $phone)
                              ->where('date', $date)
                              ->exists();

        if (!$isDuplicateContact) {
            Attendance::create($contactData);
        } 
                              
        $totalUploaded++;
    }

    fclose($handle);

    return response()->json([
        'status'          => 'success',
        'total_uploaded'  => $totalUploaded,
        'total_new'       => $totalNew,
        'total_duplicate' => $totalDuplicate,
        'total_skipped'   => $totalSkipped,
        'message'         => 'Matagumpay na natapos ang pagproseso sa iyong CSV file!'
    ], 200);
}



    private function parseVcard($vcardString)
    {
        $data = [];
        $lines = explode("\n", str_replace("\r", "", $vcardString));

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'FN:')) {
                $data['FN'] = trim(substr($line, 3));
            } elseif (str_starts_with($line, 'ORG:')) {
                $data['ORG'] = trim(substr($line, 4));
            } elseif (str_starts_with($line, 'TITLE:')) {
                $data['TITLE'] = trim(substr($line, 6));
            } elseif (str_contains($line, 'TEL;') && str_contains($line, 'cell:')) {
                $parts = explode(':', $line);
                $data['CELL'] = trim(end($parts));
            } elseif (str_contains($line, 'EMAIL;')) {
                $parts = explode(':', $line);
                $data['EMAIL'] = trim(end($parts));
            }
        }

        return $data;
    }

public function bulkAssign(Request $request)
{
    $request->validate([
        'attendee' => 'required|array',
        'psc_id'   => 'required'
    ]);

    DB::beginTransaction();

    try {


        $user = Auth::user();

        // 🔥 Get PSC info
        $psc = User::where('emp_id', $request->psc_id)->first();

        if (!$psc) {
            throw new \Exception('PSC not found');
        }

        $psc_name = $psc->first_name . ' ' . $psc->last_name;

        foreach ($request->attendee as $company_id) {

            $existing = AssignedAgent::where('company_id', $company_id)->first();

            if ($existing) {

                // ✅ LOG muna bago update
                AssignedAgentLog::create([
                    'company_id'      => $company_id,
                    'old_psc_emp_id'  => $existing->psc_emp_id,
                    'old_psc_name'    => $existing->psc_name,
                    'new_psc_emp_id'  => $request->psc_id,
                    'new_psc_name'    => $psc_name,
                    'changed_by'      => $user->emp_id,
                    'created_at'      => now()
                ]);

                // ✅ UPDATE existing
                $existing->update([
                    'psc_emp_id'  => $request->psc_id,
                    'psc_name'    => $psc_name,
                    'assigned_by' => $user->emp_id,
                    'company_id'  => $company_id,
                    'updated_at'  => now()
                ]);
              // dd($request->psc_id); 

            } else {

                //dd($company_id);
                // ✅ INSERT new
                AssignedAgent::create([
                    'company_id'  => $company_id,
                    'psc_emp_id'  => $request->psc_id,
                    'psc_name'    => $psc_name,
                    'assigned_by' => $user->emp_id,
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }
            //Update assigned PSC
            Company::where('id', $company_id)
                ->update([
                    'assigned_psc' => $request->psc_id
                ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'PSC Assigned/Updated Successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



//Use to Assigned PSC
public function bulkAssignInquiry(Request $request)
{
   // dd($request);

    $request->validate([
        'attendee' => 'required|array',
        'psc_id'   => 'required'
    ]);
   

    DB::beginTransaction();

    try {
        $user = Auth::user();

        // 1. Kunin ang impormasyon ng PSC (Employee)
        $psc = User::where('emp_id', $request->psc_id)->first();

        if (!$psc) {
            throw new \Exception('PSC not found');
        }

        $psc_name = $psc->first_name . ' ' . $psc->last_name;

        // 2. I-loop ang malinis na array ng objects mula sa AJAX
        foreach ($request->attendee as $attendee) {

            // Kinukuha ang mga detalye na nanggaling mismo sa HTML data-attributes
            $html_company_name = $attendee['company_name'] ?? null;
            $html_address      = $attendee['address'] ?? null;
            $html_client_name  = $attendee['contactname'] ?? null;
            $html_email        = $attendee['contactemail'] ?? null;
            $html_phone        = $attendee['contactnumber'] ?? null;
            $inquirer_id       = $attendee['inquirer_id'] ?? null;
         

            // Laktawan ang loop kung blangko ang pangalan ng kumpanya para iwas DB crash
            if (empty($html_company_name)) {
                continue;
            }

            // 3. I-insert o i-update muna ang Kumpanya sa company_list table gamit ang Pangalan
            $companyRecord = Company::updateOrCreate(
                ['company_name' => $html_company_name], 
                [
                    'address'      => $html_address,    
                    'assigned_psc' => $request->psc_id
                ]
            );

            // 4. Kunin ang Auto-Increment ID mula sa bagong insert o umiiral na kumpanya
            $company_id = $companyRecord->id;

            // 5. I-proseso ang AssignedAgent at Logs gamit ang nakuha nating totoong $company_id
            $existing = AssignedAgent::where('company_id', $company_id)->first();

            if ($existing) {
                // ✅ LOG muna bago mag-update
                AssignedAgentLog::create([
                    'company_id'      => $company_id, 
                    'old_psc_emp_id'  => $existing->psc_emp_id,
                    'old_psc_name'    => $existing->psc_name,
                    'new_psc_emp_id'  => $request->psc_id,
                    'new_psc_name'    => $psc_name,
                    'changed_by'      => $user->emp_id,
                    'created_at'      => now()
                ]);

                // ✅ UPDATE ang kasalukuyang psc assignment
                $existing->update([
                    'psc_emp_id'  => $request->psc_id,
                    'psc_name'    => $psc_name,
                    'assigned_by' => $user->emp_id,
                    'company_id'  => $company_id,
                    'updated_at'  => now()
                ]);

            } else {
                // ✅ INSERT ng bagong agent assignment record
                AssignedAgent::create([
                    'company_id'  => $company_id, 
                    'psc_emp_id'  => $request->psc_id,
                    'psc_name'    => $psc_name,
                    'assigned_by' => $user->emp_id,
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }

        // ✅ 6. VALIDATE at UPDATE o INSERT sa Contacts table
            Contact::updateOrCreate(
                [
                    'email'      => $html_email, // Hahanapin kung may umiiral nang katulad na email
                    'company_id' => $company_id  // At nakakabit sa kumpanyang ito
                ],
                [
                    'entry_by'     => $user->emp_id,          
                    'exhibit_name' => 'Inquiry',
                    'date'         => now()->format('Y-m-d'), 
                    'time'         => now()->format('H:i:s'), 
                    'name'         => $html_client_name,      
                    'company'      => $html_company_name,     
                    'phone'        => $html_phone,            
                    'updated_at'   => now()
                ]
            );
//dd($inquiry_id);
                // Update the Inquiry table para malaman kung sinong PSC ang nailagay
             if ($inquirer_id) {
                DB::connection('mysql_third')
                    ->table('rfq_tbl_inquiry')
                    ->where('inquirer_id', $inquirer_id)
                    ->update([
                        'assigned_psc'      => $request->psc_id,
                        'assigned_psc_name' => $psc_name 
                    ]);
                             }

        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'PSC Assigned/Updated and Contact Saved Successfully'
        ]);

    } catch (\Exception $e) {
        DB::rollback();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}






public function UpdateContactDetails(Request $request)
{
    $request->validate([
        'participant_name'    => 'required|string',
        'participant_contact' => 'required|string'
    ]);

    Contact::where('id', $request->p_id)
        ->update([
            'participant_name'    => $request->participant_name,
            'participant_email'   => $request->participant_email,
            'participant_contact' => $request->participant_contact
        ]);

    return response()->json([
        'success' => true
    ]);
}



//Use to update the Status of Exhibit Attendee
// Uploading ng file on the same table
public function ContactUpdateStatus(Request $request, $id)
{


    $request->validate([
    'status'          => 'required',
    'product_inquiry' => 'required_if:status,4',
    'customer_code'   => 'required_if:status,10',
    'ReasonOfLost'    => 'required_if:status,11',
    'files'           => 'required_if:status,9|array',             // Dinagdagan ng |array
    'files.*'         => 'file|mimes:pdf,jpg,png,jpeg|max:2048',
    ]);


    try {

        DB::beginTransaction();

        $Contact = Contact::findOrFail($id);
        $user        = Auth::user();

        ContactsUpdate::create([
            'company_id'  => $id,
            'status'      => $request->status,
            'description' => $request->description,
            'updated_by'  => $user->emp_id,
            'update_date' => now()
        ]);

        $Contact->update([
            'last_update_date' => now(),
            'status'           => $request->status,
            'assigned_psc'     => $user->emp_id,
            'description'      => $request->description
        ]);


         if ((int)$request->status === 4) {
            
            if ($request->filled('product_inquiry')) {

                    foreach ($request->product_inquiry as $productId) {

                        ProductInquiryLogs::create([
                            'company_id'      => $id,
                            'product_id'      => $productId,
                            'product_remarks' => $request->description, // o $request->product_remarks kung meron
                            'created_by'      => $user->emp_id,
                        ]);

                    }
                }
        }


        // 🔥 ONLY RUN IF STATUS = 9
         if ($request->status == 9) {

            if (!$request->hasFile('files')) {
                throw new \Exception("Signed proposal file is required.");
            }

            foreach ($request->file('files') as $file) {
                
                // Siguraduhing valid ang file bago i-store
                if ($file->isValid()) {
                    $path = $file->store('contact_files', 'public');

                    ContactsFile::create([
                        'company_id'  => $id,
                        'status_id'   => $request->status,
                        'file_path'   => $path,
                        'file_name'   => $file->getClientOriginalName(),
                        'file_type'   => $file->getClientMimeType(),
                        'uploaded_by' => $user->emp_id,
                        'uploaded_at' => now()
                    ]);
                }
            }
        }
       // 🔥 ONLY RUN IF STATUS = 9




        // Para ito sa pag update ng status to Converted, kailagan mailagay yung customer code na galing sa SAP
        if ((int)$request->status === 10) {
                // Kunin ang mismong Company object gamit ang relationship method
                $company = $Contact->company()->first();

                if (!$company) {
                    throw new \Exception("No company linked to this participant.");
                }

                // Suriin kung ginagamit na ng iba ang customer code gamit ang ID ng kumpanya
                $codeExists = \App\Models\Company::where('customer_code', $request->customer_code)
                    ->where('id', '!=', $company->id) 
                    ->exists();

                if ($codeExists) {
                    throw new \Exception("The customer code '{$request->customer_code}' already exists.");
                }

                $company->update([
                    'customer_code' => $request->customer_code,
                    'updated_at' => now()
                ]);
            }



        // 🔥 ONLY RUN IF STATUS = 11 (Lost)
            if ((int)$request->status === 11) {
                OpportunityLostReasonLog::create([
                    'company_id'   => $id,
                    'reason_id'    => $request->ReasonOfLost,
                    'lost_remarks' => $request->description,
                    'created_by'   => $user->emp_id

                ]);
            }


        DB::commit();

        return response()->json(['success' => true]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}



}
