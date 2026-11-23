<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Repositories\WilayahRepository;

class LocalController extends Controller
{
public function provinsi(WilayahRepository $wilayah)
{
    return $wilayah->provinsi();
}

public function kota(Request $request, WilayahRepository $wilayah)
{
    $request->validate(['provinsi' => 'required|string|max:20']);
    return $wilayah->kota($request->provinsi);
}

public function kecamatan(Request $request, WilayahRepository $wilayah)
{
    $request->validate([
        'provinsi' => 'required|string|max:20',
        'kota'     => 'required|string|max:20',
    ]);
    return $wilayah->kecamatan($request->kota, $request->provinsi);
}

public function kelurahan(Request $request, WilayahRepository $wilayah)
{
    $request->validate([
        'provinsi'  => 'required|string|max:20',
        'kota'      => 'required|string|max:20',
        'kecamatan' => 'required|string|max:20',
    ]);
    return $wilayah->kelurahan($request->kecamatan, $request->kota, $request->provinsi);
}

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
                ->select('tbdkode as value', 'tbdket as label')
                ->orderBy('tbdket')
                ->get();
        });
    }
}