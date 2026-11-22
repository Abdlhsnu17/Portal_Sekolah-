<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdmissionPath;
use App\Models\AdmissionPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionSettingController extends Controller {
    public function index() { return view('admin.admission-settings.index', ['periods'=>AdmissionPeriod::latest('starts_on')->get(), 'paths'=>AdmissionPath::orderBy('name')->get()]); }
    public function storePeriod(Request $request) { $data=$request->validate(['name'=>['required','string','max:100'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],'quota'=>['nullable','integer','min:1'],'is_active'=>['nullable','boolean']]); DB::transaction(function() use($data) { if(!empty($data['is_active'])) AdmissionPeriod::query()->update(['is_active'=>false]); AdmissionPeriod::create($data); }); return back()->with('success','Periode PSB ditambahkan.'); }
    public function activatePeriod(AdmissionPeriod $admissionPeriod) { DB::transaction(function() use($admissionPeriod) { AdmissionPeriod::query()->update(['is_active'=>false]); $admissionPeriod->update(['is_active'=>true]); }); return back()->with('success','Periode PSB diaktifkan.'); }
    public function storePath(Request $request) { AdmissionPath::create($request->validate(['name'=>['required','string','max:100','unique:admission_paths,name'],'quota'=>['nullable','integer','min:1']])); return back()->with('success','Jalur pendaftaran ditambahkan.'); }
}
