<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index()
    {
        return view('tenant.index');
    }

    public function store(Request $request)
    {
        $tenant = Tenant::create([
            'id' => $request->name,
        ]);
        $tenant->domains()->create([
            'domain' => $request->name . '.localhost',
        ]);
        error_log($request->name);

        return redirect()->route('tenant.index')->with('success', 'Tenant created successfully');
    }
}
