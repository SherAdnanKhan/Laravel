<?php

namespace App\Http\Controllers\Companies;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\Company\CreateRequest;
use App\Http\Requests\Company\ImportRequest;
use App\Http\Requests\Company\UpdateRequest;
use Illuminate\Validation\ValidationException;
use DataTables;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $info = auth()->user()->info;
        return view('pages.account.companies.create', compact('info'));
    }

    public function getCompanies(Request $request)
    {
        if ($request->ajax()) {
            $data = Company::select('*');
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                    if ($request->ISO9001 == 'ISO9001' && $request->CE == '' && $request->ETL == '') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->ISO9001%");
                        });
                    }
                    if ($request->ISO9001 == '' && $request->CE == 'CE' && $request->ETL == '') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->CE%");
                        });
                    }
                    if ($request->ISO9001 == '' && $request->CE == '' && $request->ETL == 'ETL') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->ETL%");
                        });
                    }
                    if ($request->ISO9001 == 'ISO9001' && $request->CE == 'CE' && $request->ETL == '') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->ISO9001%")
                            ->orWhere('certification', 'LIKE', "%$request->CE%");
                        });
                    }
                    if ($request->ISO9001 == 'ISO9001' && $request->CE == '' && $request->ETL == 'ETL') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->ISO9001%")
                            ->orWhere('certification', 'LIKE', "%$request->ETL%");
                        });
                    }
                    if ($request->ISO9001 == '' && $request->CE == 'CE' && $request->ETL == 'ETL') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->CE%")
                            ->orWhere('certification', 'LIKE', "%$request->ETL%");
                        });
                    }
                    if ($request->ISO9001 == 'ISO9001' && $request->CE == 'CE' && $request->ETL == 'ETL') {
                        $instance->where(function ($w) use ($request) {
                            $w->orWhere('certification', 'LIKE', "%$request->ISO9001%")
                                ->orWhere('certification', 'LIKE', "%$request->CE%")
                                ->orWhere('certification', 'LIKE', "%$request->ETL%");
                        });
                    }


                    if (!empty($request->get('search'))) {
                        $instance->where(function ($w) use ($request) {
                            $search = $request->get('search');
                            $w->orWhere('manufacture', 'LIKE', "%$search%")
                                ->orWhere('number_of_employeer', 'LIKE', "%$search%")
                                ->orWhere('country', 'LIKE', "%$search%")
                                ->orWhere('property', 'LIKE', "%$search%")
                                ->orWhere('certification', 'LIKE', "%$search%")
                                ->orWhere('main_market', 'LIKE', "%$search%")
                                ->orWhere('contact_link', 'LIKE', "%$search%")
                                ->orWhere('distance', 'LIKE', "%$search%");
                        });
                    }
                })
                ->rawColumns(['certificate'])
                ->make(true);
        }

        return view('pages.log.audit.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $info = auth()->user()->info;
        return view('pages.account.companies.settings', compact('info'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateRequest $request)
    {
        Company::create($request->validated());
        $notification = array(
            'message' => 'Company created successfully!',
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }

    public function import(ImportRequest $request)
    {
        $row = $this->spreadsheet_to_array($request->csv_file);

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($row); $i++) {
                $data = [
                    'manufacture' => $row[$i][0],
                    'products' => $row[$i][1],
                    'number_of_employeer' => $row[$i][2],
                    'country' => $row[$i][3],
                    'property' => $row[$i][4],
                    'certification' => $row[$i][5],
                    'main_market' => $row[$i][6],
                    'contact_link' => $row[$i][7],
                    'distance' => $row[$i][8],
                ];
                if (!empty($row[$i][0])) {
                    Company::create($data);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            $notification = array(
                'message' => 'Please only import excel/Xlsx file!',
                'alert-type' => 'failure'
            );
            return back()->with($notification);
        }
        $notification = array(
            'message' => 'Companies file imported successfully!',
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Company $company)
    {
        $company->update($request->validated());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return redirect('log/audit');
    }

    public function getDownload()
    {
        //PDF file is stored under project/public/download/info.pdf
        $file = public_path() . "/First companies.xlsx";

        $headers = array(
            'Content-Type: application/xlsx',
        );
        return Response::download($file, 'companies.xlsx', $headers);
    }


    function spreadsheet_to_array($file, $nullValue = null, $calculateFormulas = true, $formatData = false)
    {
        $results = [];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $results = $worksheet->toArray($nullValue, $calculateFormulas, $formatData);
        }
        // save memory
        $spreadsheet->__destruct();
        $spreadsheet = NULL;
        unset($spreadsheet);
        return $results;
    }
}
