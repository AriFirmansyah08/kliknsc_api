<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    // GET /api/wilayah/provinsi
    public function provinsi()
    {
        return Cache::remember('wilayah.provinsi', 86400, fn () =>
            DB::table('provinsi')
                ->whereNotNull('dtmkode')
                ->select('dtmkode as value', 'dtmket as label')
                ->orderBy('dtmket')
                ->get()
        );
    }

    // GET /api/wilayah/kota?provinsi=DTMKODE_PROVINSI
    public function kota(Request $request)
    {
        $request->validate(['provinsi' => 'required|string|max:20']);

        return Cache::remember('wilayah.kota.' . $request->provinsi, 86400, fn () =>
            DB::table('kota')
                ->where('tbldtmkode', $request->provinsi)
                ->whereNotNull('tblkode')
                ->select('tblkode as value', 'tblket as label')
                ->orderBy('tblket')
                ->get()
        );
    }

    // GET /api/wilayah/kecamatan?kota=TBLKODE_KOTA
    public function kecamatan(Request $request)
    {
        $request->validate(['kota' => 'required|string|max:20']);

        return Cache::remember('wilayah.kecamatan.' . $request->kota, 86400, fn () =>
            DB::table('kecamatan')
                ->where('tbdtblkode', $request->kota)
                ->whereNotNull('tbdkode')
                ->select('tbdkode as value', 'tbdket as label')
                ->orderBy('tbdket')
                ->get()
        );
    }

    // GET /api/wilayah/kelurahan?kecamatan=TBDKODE_KECAMATAN
    public function kelurahan(Request $request)
    {
        $request->validate(['kecamatan' => 'required|string|max:20']);

        return Cache::remember('wilayah.kelurahan.' . $request->kecamatan, 86400, fn () =>
            DB::table('kelurahan')
                ->where('tbttbdkode', $request->kecamatan)
                ->whereNotNull('tbtkode')
                ->select('tbtkode as value', 'tbtket as label')
                ->orderBy('tbtket')
                ->get()
        );
    }
}