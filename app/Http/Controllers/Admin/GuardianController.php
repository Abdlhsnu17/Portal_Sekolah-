<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Student; use App\Models\User; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class GuardianController extends Controller { public function index(){return view('admin.guardians.index',['guardians'=>User::where('role','wali')->with('students')->orderBy('name')->get(),'students'=>Student::where('status','aktif')->orderBy('nama')->get()]);} public function link(Request $r,User $guardian){abort_unless($guardian->isGuardian(),404);$d=$r->validate(['student_id'=>['required',Rule::exists('students','id')]]);$guardian->students()->syncWithoutDetaching([$d['student_id']]);return back()->with('success','Siswa ditautkan ke akun wali.');} }
