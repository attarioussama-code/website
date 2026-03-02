<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\MonthPaper;

class MonthPaperController extends Controller
{
   public function downloadMonthPaper(Request $request)
{
    $type = $request->query('type');
    $account = $request->query('account');
    $subtype = $request->query('subType');
    $password = $request->query('password'); // ✅ استلام كلمة المرور

    if (!$type || !$account) {
        return response()->json(['error' => 'Missing parameters'], 400);
    }

    $monthPaper = MonthPaper::latest()->first();
    if (!$monthPaper) {
        return response()->json(['error' => 'No record found in DB'], 404);
    }

    if (mb_strpos($type, 'استاذ') !== false) {
        $file = $monthPaper->file1;
    } elseif (mb_strpos($type, 'عامل متقاعد') !== false) {
        $file = $monthPaper->file2;
    } elseif (mb_strpos($type, 'عامل دائم') !== false) {
        $file = $monthPaper->file3;
    } else {
        return response()->json(['error' => 'Invalid type'], 400);
    }

    if (!$file) {
        return response()->json(['error' => 'No file found in DB'], 404);
    }

    $fullPath = storage_path('app/public/' . $file);
    if (!file_exists($fullPath)) {
        return response()->json(['error' => 'File not found on disk'], 404);
    }

    // بناء الرابط لبايثون
    $params = [
            'type'      => $type,
            'account'   => $account,
            'file_path' => $fullPath,
            'password'  => $password, // ✅ تمرير كلمة المرور
        ];
    if ($type == 'عامل دائم') {
        $params['subType'] = $subtype;
    }

    $pythonUrl = 'http://172.16.61.27:5000/download?' . http_build_query($params);
    return redirect()->away($pythonUrl);
}



}
