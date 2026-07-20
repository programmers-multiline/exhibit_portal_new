<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //For Build Query
use Yajra\DataTables\Facades\DataTables;

class CustomerList extends Controller
{
    //
   public function index()
    {
        return view('client.index');
    }

   


        public function ClientList()
        {
            $client_list = DB::table('customer_list as c')
                ->leftJoin('users as u', 'u.emp_id', '=', 'c.psc_emp_id')
                ->select([
                    'c.customer_code',
                    'c.customer_name',
                    'c.customer_address',
                    'c.contact_person',
                    'c.email',
                    'c.mob_num',
                    'c.psc_name',
                    'c.assigned_date',
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as full_name")
                ])
                ->where('c.customer_code', 'NOT LIKE', 'V%');

        // return DataTables::of($client_list)->make(true);
        return DataTables::of($client_list)
            ->filterColumn('full_name', function($query, $keyword) {
                $sql = "CONCAT(u.first_name, ' ', u.last_name) LIKE ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->make(true);   
        }



  public function client_card()
    {
        return view('client.client_card');
    }
       

 public function ClientCardList(Request $request)
{
    $query = DB::table('customer_list as c')
        ->leftJoin('users as u', 'u.emp_id', '=', 'c.psc_emp_id')
        ->select([
            'c.customer_code',
            'c.customer_name',
            'c.customer_address',
            'c.contact_person',
            'c.email',
            'c.mob_num',
            'c.psc_name',
            'c.assigned_date',
            DB::raw("CONCAT(u.first_name, ' ', u.last_name) as full_name")
        ]);

    // 🔍 SERVER-SIDE SEARCH
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('c.customer_code', 'like', "%$search%")
              ->orWhere('c.customer_name', 'like', "%$search%")
              ->orWhere('c.customer_address', 'like', "%$search%")
              ->orWhere('c.contact_person', 'like', "%$search%")
              ->orWhere('c.email', 'like', "%$search%")
              ->orWhere('c.mob_num', 'like', "%$search%")
              ->orWhere('c.psc_name', 'like', "%$search%")
              ->orWhere(DB::raw("CONCAT(u.first_name, ' ', u.last_name)"), 'like', "%$search%");
        });
    }

    // 📄 PAGINATION (important for 10k+)
    $data = $query->orderBy('c.customer_name', 'asc')
                  ->paginate(12);

    return response()->json($data);
}
}
