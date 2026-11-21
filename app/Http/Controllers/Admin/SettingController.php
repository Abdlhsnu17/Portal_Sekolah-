<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return redirect()->to(route('security.index').'#settings');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ticker_enabled' => ['nullable', 'boolean'],
            'ticker_text' => ['required', 'string', 'max:500'],
        ]);

        SiteSetting::setValue('ticker_enabled', $request->boolean('ticker_enabled') ? '1' : '0');
        SiteSetting::setValue('ticker_text', trim($data['ticker_text']));

        return redirect()->to(route('security.index').'#settings')
            ->with('success', 'Pengaturan tulisan berjalan berhasil disimpan.');
    }
}
