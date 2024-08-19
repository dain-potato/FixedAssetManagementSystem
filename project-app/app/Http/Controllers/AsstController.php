<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Models\assetModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AsstController extends Controller
{
    //
    public function show(){
        $userDept = Auth::user()->dept_id;


        $asset = DB::table('asset')
                        ->join('department', 'asset.dept_ID', '=', 'department.id')
                        ->join('category', 'asset.ctg_ID', '=', 'category.id')
                        ->where('asset.dept_ID', $userDept)
                        ->select('asset.id', 'asset.code' , 'asset.name' ,'asset.image' ,'asset.cost' ,'asset.salvageVal' ,'asset.depreciation' ,'asset.usage_Lifespan','asset.status', 'category.name as category', 'department.name as department')
                        ->orderBy('asset.code', 'asc') // Group by the 'code' column
                        ->get();


        // dd($asset);

        return view("dept_head.asset" ,compact('asset'));
    }
    public function showForm(){

        $usrDPT = Auth::user()->dept_id;

        $departments = array('list' => DB::table('department')->get());
        $categories = array('ctglist' => DB::table('category')->where('dept_ID', $usrDPT)->get());
        $location = array('locs' => DB::table('location')->get());
        $model = array('mod' => DB::table('model')->get());
        $manufacturer = array('mcft' => DB::table('manufacturer')->get());



        return view('dept_head.createAsset',compact('departments' , 'categories','location' ,'model','manufacturer'));
    }
    public static function create(Request $request){
        $userDept = Auth::user()->dept_id;
        // dd($request);
        if(!$request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'name'=> 'required',
            'category'=> 'required',
            'cost'=>'required|numeric|min:0.01',
            'salvageVal'=> 'required|numeric|min:0.01',
            'usage' => 'required',
            'loc'=> 'required',
            'mod'=> 'required',
            'mcft'=> 'required',
        ])){
            return redirect()->back()->withError();
        }


        $custom = json_encode([
            'Storage'=> '1TB',
            'RAM'=> '16GB',
            'OS'=> 'Window 11',
        ]);

        //code
        $department = DB::table('department')->where('id',$userDept)->get();

        $departmentCode = $department[0]->name;
        $lastID =  department::where('name',$departmentCode)->max('assetSequence');
        $seq = $lastID ? $lastID + 1 : 1;
        $code = $departmentCode.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
        //image
        $imageName = $code.'.'.$request->image->extension;
        $request->image->storeAs('public/assets',$imageName);


        department::where('id',$userDept)->increment('assetSequence',1);

        //parsing Text to decimal
        //depreciation Straight line
        $depreciation = ($request->cost - $request->salvageVal) / $request->usage;

        DB::table('asset')->insert([
            'image'=>$request->imageName,
            'name' => $request->name,
            'cost' => $request->cost,
            'code' => $code,
            'ctg_ID' => $request->category,
            'depreciation'=>$depreciation,
            'salvageVal'=>$request->salvageVal,
            'usage_Lifespan'=>$request->usage,
            'custom_fields' =>$custom,
            'dept_ID' => $userDept,
            'loc_key' => $request->loc,
            'model_key' => $request->mod,
            'manufacturer_key' => $request->mcft,
            'created_at'=>now(),
        ]);

        return redirect()->route('/dept_head.asset');
    }
    public static function assetCount(){
        $userDept = Auth::user()->dept_id;

        $asset['active'] = DB::table('asset')->where('asset.status','=' , 'active')
                                             ->where("asset.dept_ID","=", $userDept)->count();
        $asset['um'] = DB::table('asset')->where('status','=' , 'under maintenance')
                                         ->where("asset.dept_ID","=", $userDept)->count();
        $asset['dispose'] = DB::table('asset')->where('status','=' , 'dispose')
                                              ->where("asset.dept_ID","=", $userDept)->count();
        $asset['deploy'] = DB::table('asset')->where('status','=' , 'deploy')
                                             ->where("asset.dept_ID","=", $userDept)->count();



        //FOR DASHBOARD CARDS
        return view('dept_head.Home' , ['asset' => $asset]);

    }
}
