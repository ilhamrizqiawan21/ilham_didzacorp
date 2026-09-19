<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\PengumpulanTugas;
use App\Models\SikapSosial;
use App\Models\SikapSpiritual;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RekapController extends Controller
{
    public function absensi(Request $request)
    {
        $request->validate(['kelas_id' => 'nullable|exists:kelas,id', 'bulan' => 'nullable|date_format:Y-m', 'semester' => 'nullable|in:1,2']);
        $kelasList = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(); $kelasId = $request->input('kelas_id'); $bulan = $request->input('bulan', date('Y-m')); $taAktif = TahunAjaran::getAktif(); $semester = $request->input('semester', \App\Models\Pengaturan::getValue('semester_aktif', '1')); $rekap = []; $tanggalList = []; $kelasNama = '';
        if ($kelasId && $taAktif) {
            $kelas = Kelas::find($kelasId); $kelasNama = $kelas ? "{$kelas->tingkat} {$kelas->nama_kelas}" : ''; $siswaList = Siswa::with('user')->where('kelas_id', $kelasId)->where('status', 'aktif')->orderBy('nis')->get();
            $tanggalList = Absensi::whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $taAktif->id)->where('semester', $semester))->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))])->orderBy('tanggal')->pluck('tanggal')->unique()->map(fn($d) => $d->format('Y-m-d'))->values();
            $absensiData = Absensi::whereIn('siswa_id', $siswaList->pluck('id'))->whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $taAktif->id)->where('semester', $semester))->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))])->get()->groupBy('siswa_id');
            foreach ($siswaList as $s) { $row = ['nis'=>$s->nis,'nama'=>$s->user->nama_lengkap ?? '-','absensi'=>[],'hadir'=>0,'sakit'=>0,'izin'=>0,'alpha'=>0]; foreach ($tanggalList as $tgl) { $st = $absensiData->get($s->id, collect())->firstWhere('tanggal', $tgl)?->status; $row['absensi'][$tgl] = $st; if ($st && isset($row[$st])) $row[$st]++; } $rekap[] = $row; }
        }
        return Inertia::render('Admin/Rekap', compact('kelasList','rekap','tanggalList','kelasNama','bulan','kelasId','semester') + ['type'=>'absensi','title'=>'Rekap Absensi']);
    }

    public function nilai(Request $request)
    {
        $request->validate(['kelas_id'=>'nullable|exists:kelas,id','semester'=>'nullable|in:1,2']); $kelasList = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(); $kelasId = $request->input('kelas_id'); $taAktif = TahunAjaran::getAktif(); $semester = $request->input('semester', \App\Models\Pengaturan::getValue('semester_aktif','1')); $rekap=[]; $mapelList=[]; $kelasNama='';
        if ($kelasId && $taAktif) { $kelas=Kelas::find($kelasId); $kelasNama=$kelas?"{$kelas->tingkat} {$kelas->nama_kelas}":''; $siswaList=Siswa::with('user')->where('kelas_id',$kelasId)->where('status','aktif')->orderBy('nis')->get(); $mapelList=KelasMapel::with('mataPelajaran')->where('kelas_id',$kelasId)->where('tahun_ajaran_id',$taAktif->id)->where('semester',$semester)->join('mata_pelajaran','mata_pelajaran.id','=','kelas_mapel.mapel_id')->orderBy('mata_pelajaran.urutan')->orderBy('mata_pelajaran.nama_mapel')->select('kelas_mapel.*')->get()->map(fn($km)=>(object)['id'=>$km->mapel_id,'kelas_mapel_id'=>$km->id,'nama_mapel'=>$km->mataPelajaran?->nama_mapel ?? '-']); $nilaiData=NilaiAkhir::whereIn('siswa_id',$siswaList->pluck('id'))->where('tahun_ajaran_id',$taAktif->id)->where('semester',$semester)->get()->groupBy('siswa_id'); foreach($siswaList as $s){$row=['nis'=>$s->nis,'nama'=>$s->user->nama_lengkap ?? '-','nilai'=>[]];$sn=$nilaiData->get($s->id,collect());foreach($mapelList as $mp){$row['nilai'][$mp->id]=$sn->firstWhere('kelas_mapel_id',$mp->kelas_mapel_id)?->rata_akhir;}$valid=array_filter($row['nilai'],fn($v)=>!is_null($v));$row['rata']=count($valid)?round(array_sum($valid)/count($valid),2):null;$rekap[]=$row;}}
        return Inertia::render('Admin/Rekap', compact('kelasList','rekap','mapelList','kelasNama','kelasId','semester') + ['type'=>'nilai','title'=>'Rekap Nilai']);
    }

    public function sikap(Request $request)
    {
        $request->validate(['kelas_id'=>'nullable|exists:kelas,id','semester'=>'nullable|in:1,2']); $kelasList=Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(); $kelasId=$request->input('kelas_id'); $taAktif=TahunAjaran::getAktif(); $semester=$request->input('semester',\App\Models\Pengaturan::getValue('semester_aktif','1')); $rekap=[];$kelasNama='';$labelNilai=[1=>'TB',2=>'KB',3=>'C',4=>'B',5=>'SB'];
        if($kelasId&&$taAktif){$kelas=Kelas::find($kelasId);$kelasNama=$kelas?"{$kelas->tingkat} {$kelas->nama_kelas}":'';$siswaList=Siswa::with('user')->where('kelas_id',$kelasId)->where('status','aktif')->orderBy('nis')->get();$ids=KelasMapel::where('kelas_id',$kelasId)->where('tahun_ajaran_id',$taAktif->id)->where('semester',$semester)->pluck('id');$spData=SikapSpiritual::whereIn('siswa_id',$siswaList->pluck('id'))->where('tahun_ajaran_id',$taAktif->id)->where('semester',$semester)->whereIn('kelas_mapel_id',$ids)->get()->groupBy('siswa_id')->map(fn($r)=>collect(['taqwa','kejujuran','disiplin','sabar','syukur','tawadhu'])->mapWithKeys(fn($f)=>[$f=>$labelNilai[(int)round($r->avg($f))]??'-'])->all());$soData=SikapSosial::whereIn('siswa_id',$siswaList->pluck('id'))->where('tahun_ajaran_id',$taAktif->id)->where('semester',$semester)->whereIn('kelas_mapel_id',$ids)->get()->groupBy('siswa_id')->map(fn($r)=>collect(['empati','kerjasama','toleransi','percaya_diri','komunikasi'])->mapWithKeys(fn($f)=>[$f=>$labelNilai[(int)round($r->avg($f))]??'-'])->all());foreach($siswaList as $s)$rekap[]=['nis'=>$s->nis,'nama'=>$s->user->nama_lengkap??'-','spiritual'=>$spData->get($s->id),'sosial'=>$soData->get($s->id)];}
        return Inertia::render('Admin/Rekap', compact('kelasList','rekap','kelasNama','kelasId','semester') + ['type'=>'sikap','title'=>'Rekap Sikap']);
    }

    public function tugas(Request $request)
    {
        $request->validate(['kelas_id'=>'nullable|exists:kelas,id','semester'=>'nullable|in:1,2']);$kelasList=Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();$kelasId=$request->input('kelas_id');$taAktif=TahunAjaran::getAktif();$semester=$request->input('semester',\App\Models\Pengaturan::getValue('semester_aktif','1'));$tugasList=[];$kelasNama='';
        if($kelasId&&$taAktif){$kelas=Kelas::find($kelasId);$kelasNama=$kelas?"{$kelas->tingkat} {$kelas->nama_kelas}":'';$totalSiswa=Siswa::where('kelas_id',$kelasId)->where('status','aktif')->count();$tugasList=Tugas::with(['kelasMapel.mataPelajaran','kelasMapel.guru'])->whereHas('kelasMapel',fn($q)=>$q->where('kelas_id',$kelasId)->where('tahun_ajaran_id',$taAktif->id)->where('semester',$semester))->withCount(['pengumpulan as sudah_kumpul'=>fn($q)=>$q->whereIn('status',PengumpulanTugas::STATUS_SUBMITTED)->whereHas('siswa',fn($s)=>$s->where('kelas_id',$kelasId)->where('status','aktif'))])->orderByDesc('created_at')->get()->map(function($t)use($totalSiswa){$t->total_siswa=$totalSiswa;return $t;});}
        return Inertia::render('Admin/Rekap', compact('kelasList','tugasList','kelasNama','kelasId','semester') + ['type'=>'tugas','title'=>'Rekap Tugas']);
    }
}
