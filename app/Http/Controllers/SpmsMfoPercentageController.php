<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrSetting;

class SpmsMfoPercentageController extends Controller
{
    public function mfoSettings(Request $request)
    {
        $mfosettings = PrSetting::all();
        return view('drive.pmt-mfo-settings', compact('mfosettings'));
    }

    public function mfoSettingsCreate(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'core_mfo1' => 'nullable|numeric',
            'core_mfo2' => 'nullable|numeric',
            'core_mfo3' => 'nullable|numeric',
            'strategic_mfo4' => 'nullable|numeric',
            'strategic_mfo5' => 'nullable|numeric',
            'support_mfo4' => 'nullable|numeric',
            'support_mfo5' => 'nullable|numeric',
        ]);

        $validated['core_sum'] = ($validated['core_mfo1'] ?? 0) + ($validated['core_mfo2'] ?? 0) + ($validated['core_mfo3'] ?? 0);
        $validated['strat_sum'] = ($validated['strategic_mfo4'] ?? 0) + ($validated['strategic_mfo5'] ?? 0);
        $validated['support_sum'] = ($validated['support_mfo4'] ?? 0) + ($validated['support_mfo5'] ?? 0);

        PrSetting::create($validated);

        return redirect()->back()->with('success', 'MFO settings added successfully.');
    }

    public function mfoSettingsUpdate(Request $request)
    {
        $validated = $request->validate([
            'mfo_id' => 'required|exists:pr_settings,id',
            'category' => 'required|string|max:255',
            'core_mfo1' => 'nullable|numeric',
            'core_mfo2' => 'nullable|numeric',
            'core_mfo3' => 'nullable|numeric',
            'strategic_mfo4' => 'nullable|numeric',
            'strategic_mfo5' => 'nullable|numeric',
            'support_mfo4' => 'nullable|numeric',
            'support_mfo5' => 'nullable|numeric',
        ]);

        $validated['core_sum'] = ($validated['core_mfo1'] ?? 0) + ($validated['core_mfo2'] ?? 0) + ($validated['core_mfo3'] ?? 0);
        $validated['strat_sum'] = ($validated['strategic_mfo4'] ?? 0) + ($validated['strategic_mfo5'] ?? 0);
        $validated['support_sum'] = ($validated['support_mfo4'] ?? 0) + ($validated['support_mfo5'] ?? 0);

        $mfo = PrSetting::findOrFail($validated['mfo_id']);
        $mfo->update($validated);

        return redirect()->back()->with('success', 'MFO settings updated successfully.');
    }

    public function mfoSettingsEdit(Request $request, $id)
    {
        $mfoEdit = PrSetting::find($id);
        $mfosettings = PrSetting::all();
        return view('drive.pmt-mfo-settings', compact('mfosettings', 'mfoEdit'));
    }
}
