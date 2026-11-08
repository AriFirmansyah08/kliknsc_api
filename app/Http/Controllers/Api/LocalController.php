<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LocalController extends Controller
{
    public function pekerjaan()
    {
        return Cache::remember('dtdiri.pekerjaan', 86400, function () {
            return DB::table('pekerjaan')
                ->select('tbdkode as value', 'tbdket as label')
                ->orderBy('tbdket')
                ->get();
        });
    }

    public function subpekerjaan(Request $request)
    {
        $request->validate([
            'pekerjaan' => 'required|string',
        ]);

        $kode = $request->pekerjaan;

        return Cache::remember("dtdiri.subpekerjaan.$kode", 86400, function () use ($kode) {
            return DB::table('subpekerjaan')
                ->where('tbttbdkode', $kode)
                ->select('tbtkode as value', 'tbtket as label')
                ->orderBy('tbtket')
                ->get();
        });
    }

    public function bank()
    {
        return Cache::remember('dtdiri.bank', 86400, function () {
            return DB::table('bank')
                ->select('tbdket as value', 'tbdket as label')
                ->orderBy('tbdket')
                ->get();
        });
    }
}