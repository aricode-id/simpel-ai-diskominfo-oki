@php
    $formData = $formData ?? ($report->form_data ?? []);
    $pembuat = $report->user ?? Auth::user();
    $pembuatNipBersih = trim($pembuat->nip ?? '');
    $isPembuatTenagaAhli = (($pembuat->status_pegawai ?? 'ASN') === 'Tenaga Ahli') 
        || empty($pembuatNipBersih) 
        || $pembuatNipBersih === '-' 
        || str_starts_with(strtoupper($pembuatNipBersih), 'TA-')
        || !preg_match('/^[0-9\.\s]+$/', $pembuatNipBersih);

    // Data Pimpinan (Mengetahui)
    $pakaiTtd = ($formData['pakai_ttd'] ?? 'tidak');
    $ttdNama = trim($formData['ttd_nama'] ?? '');
    $ttdJabatan = strtoupper(trim($formData['ttd_jabatan'] ?? 'KEPALA BIDANG'));
    if (empty($ttdJabatan)) {
        $ttdJabatan = 'KEPALA BIDANG';
    }
    $ttdNipBersih = trim($formData['ttd_nip'] ?? '');

    // Fallback jika pakai_ttd == ya tapi ttd_nama kosong
    if ($pakaiTtd === 'ya' && empty($ttdNama)) {
        $bidangPimpinan = $report->bidang ?? ($pembuat->bidang ?? null);
        $kabid = \App\Http\Controllers\ReportController::findKepalaBidang($bidangPimpinan);
        if ($kabid) {
            $ttdNama = $kabid->name;
            $ttdNipBersih = trim($kabid->nip ?? '');
            $ttdJabatan = strtoupper(trim($kabid->jabatan ?? ('KEPALA BIDANG ' . $bidangPimpinan)));
        } else {
            $ttdNama = '..........................................';
        }
    }

    $isTtdTenagaAhli = empty($ttdNipBersih) 
        || $ttdNipBersih === '-' 
        || str_starts_with(strtoupper($ttdNipBersih), 'TA-') 
        || !preg_match('/^[0-9\.\s]+$/', $ttdNipBersih);

    $getImageUrl = function($path) {
        if (!$path) return null;
        $filename = basename($path);
        return asset('storage/reports_assets/' . $filename);
    };
    $formatTglIndo = function($tgl) {
        if (!$tgl) return '-';
        $timestamp = strtotime($tgl);
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return $hari[date('w', $timestamp)] . ", " . date('j', $timestamp) . " " . $bulan[date('n', $timestamp)] . " " . date('Y', $timestamp);
    };
    $tglSaja = function($tgl) {
        if (!$tgl) return '-';
        $timestamp = strtotime($tgl);
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return date('j', $timestamp) . " " . $bulan[date('n', $timestamp)] . " " . date('Y', $timestamp);
    };
    $mapBidang = [
        'Sekretariat' => 'SEKRETARIAT',
        'Statistik'   => 'BIDANG PENGELOLAAN DATA INFORMASI PUBLIK DAN STATISTIK',
        'PKP'         => 'BIDANG PENGELOLAAN KOMUNIKASI PUBLIK',
        'TIK'         => 'BIDANG INFRASTRUKTUR TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)',
        'E-Gov'       => 'BIDANG LAYANAN E-GOVERNMENT',
        'Persandian'  => 'BIDANG KEAMANAN INFORMASI DAN PERSANDIAN',
    ];
    $namaBidangResmi = $mapBidang[$report->bidang] ?? ('BIDANG ' . strtoupper($report->bidang ?? 'DISKOMINFO'));
@endphp

<!-- Lembar Kertas Dokumen Word A4 -->
<div class="bg-white text-slate-800 p-6 md:p-12 shadow-2xl rounded-sm border border-slate-300 max-w-3xl mx-auto font-sans leading-relaxed text-sm select-text">

    {{-- KOP SURAT RESMI OKI (Tidak tampil pada Laporan Harian) --}}
    @if(($formData['pakai_kop'] ?? 'tidak') === 'ya' && $report->jenis_dokumen !== 'Laporan Harian')
        <div class="flex items-center gap-4 pb-3 border-b-4 border-double border-slate-900 mb-6">
            <div class="shrink-0 w-20 text-center">
                <img src="{{ asset('logo_oki.png') }}" alt="Logo OKI" class="w-16 h-20 object-contain mx-auto" onerror="this.style.display='none'">
            </div>
            <div class="text-center flex-1">
                <h4 class="text-xs md:text-sm font-bold uppercase tracking-wider text-slate-900">Pemerintah Kabupaten Ogan Komering Ilir</h4>
                <h2 class="text-base md:text-lg font-black uppercase text-slate-950 tracking-wide mt-0.5">Dinas Komunikasi dan Informatika</h2>
                <p class="text-[10px] md:text-xs text-slate-700 mt-1">Jalan Letjen Yusuf Singedekane Nomor 01, Jua – jua, Kota Kayuagung (30616)</p>
                <p class="text-[10px] md:text-xs text-slate-700">Laman: <span class="text-blue-700 underline">web.kaboki.go.id</span>, Pos-el: <span class="text-blue-700 underline">diskominfooki2@gmail.com</span></p>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- 1. PREVIEW: LAPORAN MINGGUAN (FORMAT NASKAH DINAS RESMI A–E + TABEL + GRID) --}}
    {{-- ========================================================================= --}}
    @if($report->jenis_dokumen === 'Laporan Mingguan')
        @php
            $mingguanData = $formData['mingguan'] ?? [];
            $ringkasanAi = $formData['mingguan_ringkasan'] ?? [];
            $mingguanDates = [];

            // Petakan hasil tabel dari AI berdasarkan hari_idx & keg_idx jika tersedia
            $tabelAiMap = [];
            if (!empty($ringkasanAi['tabel_kegiatan']) && is_array($ringkasanAi['tabel_kegiatan'])) {
                foreach ($ringkasanAi['tabel_kegiatan'] as $tRow) {
                    if (isset($tRow['hari_idx']) && isset($tRow['keg_idx'])) {
                        $tabelAiMap[$tRow['hari_idx'] . '_' . $tRow['keg_idx']] = $tRow;
                    }
                }
            }

            $barisTabelMingguan = [];
            $lampiranFotosMingguan = [];

            foreach ($mingguanData as $mIndex => $hariItem) {
                $namaHari = $hariItem['hari'] ?? '';
                foreach ($hariItem['kegiatan'] ?? [] as $kIndex => $kegItem) {
                    $rawJudul = trim($kegItem['judul'] ?? '');
                    $rawHasil = trim($kegItem['hasil_kegiatan'] ?? '');
                    if (empty($rawJudul) && empty($rawHasil)) {
                        continue;
                    }

                    if (!empty($kegItem['tanggal'])) {
                        $mingguanDates[] = strtotime($kegItem['tanggal']);
                    }

                    $aiRow = $tabelAiMap[$mIndex . '_' . $kIndex] ?? [];
                    $judulFinal = !empty($rawJudul)
                        ? $rawJudul
                        : (!empty($aiRow['judul']) ? $aiRow['judul'] : 'Pelaksanaan Tugas ' . $namaHari);

                    $uraianFinal = !empty($aiRow['uraian'])
                        ? $aiRow['uraian']
                        : (!empty($kegItem['hasil_kegiatan_ai']) ? $kegItem['hasil_kegiatan_ai'] : ($rawHasil ?: $judulFinal));

                    $outputManual = trim($kegItem['output'] ?? '');
                    $outputFinal = !empty($outputManual)
                        ? $outputManual
                        : (!empty($aiRow['output']) ? $aiRow['output'] : 'Kegiatan terlaksana dengan baik sesuai target.');

                    $tglKegTeks = !empty($kegItem['tanggal'])
                        ? $formatTglIndo($kegItem['tanggal'])
                        : ($namaHari ?: '-');

                    $waktuKegTeks = '';
                    if (!empty($kegItem['waktu_mulai'])) {
                        $waktuKegTeks = $kegItem['waktu_mulai'] . (!empty($kegItem['waktu_selesai']) ? ' – ' . $kegItem['waktu_selesai'] . ' WIB' : ' WIB');
                    }

                    $barisTabelMingguan[] = [
                        'hari_tanggal' => $tglKegTeks,
                        'waktu'        => $waktuKegTeks,
                        'lokasi'       => trim($kegItem['lokasi'] ?? ''),
                        'judul'        => $judulFinal,
                        'uraian'       => $uraianFinal,
                        'output'       => $outputFinal,
                    ];

                    foreach (['foto_1_path', 'foto_2_path', 'foto_3_path', 'foto_4_path'] as $fotoKey) {
                        if (!empty($kegItem[$fotoKey])) {
                            $lampiranFotosMingguan[] = [
                                'url'     => $getImageUrl($kegItem[$fotoKey]),
                                'judul'   => $judulFinal,
                                'tanggal' => $tglKegTeks,
                            ];
                        }
                    }
                }
            }

            $bulanIndoHeader = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            if (count($mingguanDates) > 0) {
                $minTs = min($mingguanDates);
                $maxTs = max($mingguanDates);
                if (date('Y-m-d', $minTs) === date('Y-m-d', $maxTs)) {
                    $periodeMingguanTeks = 'Periode ' . date('j', $minTs) . ' ' . $bulanIndoHeader[(int)date('n', $minTs)] . ' ' . date('Y', $minTs);
                } elseif (date('Y-m', $minTs) === date('Y-m', $maxTs)) {
                    $periodeMingguanTeks = 'Periode ' . date('j', $minTs) . ' – ' . date('j', $maxTs) . ' ' . $bulanIndoHeader[(int)date('n', $maxTs)] . ' ' . date('Y', $maxTs);
                } elseif (date('Y', $minTs) === date('Y', $maxTs)) {
                    $periodeMingguanTeks = 'Periode ' . date('j', $minTs) . ' ' . $bulanIndoHeader[(int)date('n', $minTs)] . ' – ' . date('j', $maxTs) . ' ' . $bulanIndoHeader[(int)date('n', $maxTs)] . ' ' . date('Y', $maxTs);
                } else {
                    $periodeMingguanTeks = 'Periode ' . date('j', $minTs) . ' ' . $bulanIndoHeader[(int)date('n', $minTs)] . ' ' . date('Y', $minTs) . ' – ' . date('j', $maxTs) . ' ' . $bulanIndoHeader[(int)date('n', $maxTs)] . ' ' . date('Y', $maxTs);
                }
            } else {
                $periodeMingguanTeks = 'Periode ' . $tglSaja($report->tanggal);
            }

            $teksPendahuluan = trim($ringkasanAi['pendahuluan'] ?? '')
                ?: 'Laporan kegiatan mingguan ini disusun sebagai bentuk pertanggungjawaban dan dokumentasi pelaksanaan tugas pokok serta fungsi pada ' . ucwords(strtolower($namaBidangResmi)) . ' Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir selama ' . $periodeMingguanTeks . '.';

            $teksKendala = trim($formData['mingguan_kendala'] ?? '')
                ?: (trim($ringkasanAi['kendala'] ?? '')
                ?: 'Selama pelaksanaan kegiatan pada minggu berjalan tidak ditemukan kendala teknis maupun administratif yang signifikan; seluruh agenda dapat dilaksanakan sesuai jadwal.');

            $teksTindakLanjut = trim($formData['mingguan_tindak_lanjut'] ?? '')
                ?: (trim($ringkasanAi['tindak_lanjut'] ?? '')
                ?: 'Melanjutkan pemantauan berkala, optimalisasi layanan bidang, serta koordinasi lanjutan sesuai program kerja minggu berikutnya.');

            $teksPenutup = trim($ringkasanAi['penutup'] ?? '')
                ?: 'Demikian laporan pelaksanaan kegiatan mingguan ini disampaikan sebagai bahan evaluasi dan pertanggungjawaban kepada pimpinan. Atas perhatian dan arahannya diucapkan terima kasih.';
        @endphp

        {{-- Judul Naskah Dinas Laporan Mingguan --}}
        <div class="text-center mb-6">
            <h3 class="font-black text-base uppercase tracking-wider text-slate-950 border-b-2 border-slate-900 inline-block pb-1">LAPORAN KEGIATAN MINGGUAN</h3>
            <p class="font-bold text-xs md:text-sm uppercase tracking-wide text-slate-900 mt-1.5">{{ $namaBidangResmi }}</p>
            <p class="font-bold text-xs uppercase tracking-wide text-slate-700 mt-0.5">{{ strtoupper($periodeMingguanTeks) }}</p>
        </div>

        <div class="space-y-5 text-xs text-slate-800 leading-relaxed">
            {{-- POIN A: PENDAHULUAN --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-1.5">A. Pendahuluan</h4>
                <p class="text-justify pl-4">{{ $teksPendahuluan }}</p>
            </div>

            {{-- POIN B: PELAKSANAAN KEGIATAN (TABEL) --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-2">B. Pelaksanaan Kegiatan</h4>
                <div class="pl-4">
                    <p class="mb-2.5 text-justify">Adapun rincian pelaksanaan kegiatan selama {{ $periodeMingguanTeks }} disajikan pada tabel berikut:</p>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-slate-900 text-[11px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-950 font-bold text-center">
                                    <th class="border border-slate-900 px-2 py-2 w-9">No.</th>
                                    <th class="border border-slate-900 px-2.5 py-2 w-36">Hari / Tanggal</th>
                                    <th class="border border-slate-900 px-2.5 py-2">Uraian Kegiatan</th>
                                    <th class="border border-slate-900 px-2.5 py-2 w-44">Hasil / Output</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($barisTabelMingguan as $idxRow => $row)
                                    <tr class="align-top">
                                        <td class="border border-slate-900 px-2 py-2 text-center font-semibold">{{ $idxRow + 1 }}.</td>
                                        <td class="border border-slate-900 px-2.5 py-2">
                                            <div class="font-semibold text-slate-900">{{ $row['hari_tanggal'] }}</div>
                                            @if(!empty($row['waktu']))
                                                <div class="text-[10px] text-slate-600 mt-0.5">{{ $row['waktu'] }}</div>
                                            @endif
                                            @if(!empty($row['lokasi']))
                                                <div class="text-[10px] text-slate-600 italic mt-0.5">{{ $row['lokasi'] }}</div>
                                            @endif
                                        </td>
                                        <td class="border border-slate-900 px-2.5 py-2 text-justify">
                                            <div class="font-bold text-slate-950 mb-0.5">{{ $row['judul'] }}</div>
                                            <div class="text-slate-700 whitespace-pre-line">{{ $row['uraian'] }}</div>
                                        </td>
                                        <td class="border border-slate-900 px-2.5 py-2 text-justify text-slate-800 whitespace-pre-line">
                                            {{ $row['output'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="border border-slate-900 px-3 py-4 text-center italic text-slate-500">
                                            Belum ada rincian kegiatan yang dicatat pada minggu ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- POIN C: HASIL YANG DICAPAI --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-1.5">C. Hasil yang Dicapai</h4>
                <div class="pl-4">
                    @if(!empty($ringkasanAi['hasil_dicapai']))
                        @if(is_array($ringkasanAi['hasil_dicapai']))
                            <ol class="list-decimal pl-5 space-y-1 text-justify">
                                @foreach($ringkasanAi['hasil_dicapai'] as $itemHasil)
                                    <li>{{ $itemHasil }}</li>
                                @endforeach
                            </ol>
                        @else
                            <p class="text-justify whitespace-pre-line">{{ $ringkasanAi['hasil_dicapai'] }}</p>
                        @endif
                    @elseif(count($barisTabelMingguan) > 0)
                        <ol class="list-decimal pl-5 space-y-1 text-justify">
                            @foreach($barisTabelMingguan as $row)
                                <li><span class="font-semibold">{{ $row['judul'] }}:</span> {{ $row['output'] }}</li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-justify">Seluruh agenda kerja mingguan telah dilaksanakan sesuai dengan target yang ditetapkan.</p>
                    @endif
                </div>
            </div>

            {{-- POIN D: KENDALA DAN TINDAK LANJUT --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-1.5">D. Kendala dan Tindak Lanjut</h4>
                <div class="pl-4 space-y-2">
                    <div>
                        <p class="font-bold text-slate-900">1. Kendala yang Ditemukan</p>
                        <p class="pl-4 text-justify whitespace-pre-line">{{ $teksKendala }}</p>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900">2. Rencana Tindak Lanjut</p>
                        <p class="pl-4 text-justify whitespace-pre-line">{{ $teksTindakLanjut }}</p>
                    </div>
                </div>
            </div>

            {{-- POIN E: PENUTUP --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-1.5">E. Penutup</h4>
                <p class="pl-4 text-justify">{{ $teksPenutup }}</p>
            </div>
        </div>

        {{-- Tanda Tangan Laporan Mingguan: Kiri Mengetahui (Pimpinan), Kanan Yang Menyusun --}}
        @php
            $tglTtdMingguan = count($mingguanDates) > 0 ? $tglSaja(date('Y-m-d', max($mingguanDates))) : $tglSaja($report->tanggal);
        @endphp
        @if($pakaiTtd === 'ya')
            <div style="margin-top: 36px; padding-top: 12px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; width: 100%; font-size: 12px; line-height: 1.5; color: #0f172a;">
                {{-- Kiri: Mengetahui (Pimpinan) --}}
                <div style="width: 50%; text-align: center;">
                    <p style="visibility: hidden;">Kayuagung, {{ $tglTtdMingguan }}</p>
                    <p style="margin-top: 2px;">Mengetahui,</p>
                    <p style="margin-top: 2px; font-weight: 700;">{{ strtoupper($ttdJabatan) }}</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $ttdNama }}</p>
                    @if(!$isTtdTenagaAhli && !empty($ttdNipBersih) && $ttdNipBersih !== '-')
                        <p style="color: #475569;">NIP. {{ $ttdNipBersih }}</p>
                    @endif
                </div>

                {{-- Kanan: Yang Menyusun --}}
                <div style="width: 50%; text-align: center;">
                    <p>Kayuagung, {{ $tglTtdMingguan }}</p>
                    <p style="margin-top: 2px;">Yang Menyusun,</p>
                    <p style="margin-top: 2px; visibility: hidden;">Penyusun</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $pembuat->name ?? '-' }}</p>
                    @if(!$isPembuatTenagaAhli && !empty($pembuatNipBersih))
                        <p style="color: #475569;">NIP. {{ $pembuatNipBersih }}</p>
                    @endif
                </div>
            </div>
        @else
            <div style="margin-top: 36px; padding-top: 12px; display: flex; justify-content: flex-end; width: 100%;">
                <div style="width: 260px; text-align: center; font-size: 12px; line-height: 1.5; color: #0f172a;">
                    <p>Kayuagung, {{ $tglTtdMingguan }}</p>
                    <p style="margin-top: 2px;">Yang Menyusun,</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $pembuat->name ?? '-' }}</p>
                    @if(!$isPembuatTenagaAhli && !empty($pembuatNipBersih))
                        <p style="color: #475569;">NIP. {{ $pembuatNipBersih }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- LAMPIRAN DOKUMENTASI KEGIATAN (GRID 2 KOLOM) --}}
        @if(count($lampiranFotosMingguan) > 0)
            <div class="mt-10 pt-8 border-t-2 border-dashed border-slate-300">
                <div class="text-center mb-5">
                    <h4 class="font-black text-xs md:text-sm uppercase tracking-wider text-slate-950 border-b border-slate-900 inline-block pb-0.5">
                        LAMPIRAN DOKUMENTASI KEGIATAN
                    </h4>
                    <p class="text-[11px] font-semibold text-slate-600 mt-1">{{ $namaBidangResmi }} • {{ $periodeMingguanTeks }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($lampiranFotosMingguan as $fIdx => $fotoItem)
                        <div class="border border-slate-300 rounded-lg overflow-hidden bg-slate-50 flex flex-col">
                            <div class="w-full h-44 bg-slate-200 overflow-hidden">
                                <img src="{{ $fotoItem['url'] }}" alt="Dokumentasi {{ $fIdx + 1 }}" class="w-full h-full object-cover" onerror="this.parentElement.parentElement.style.display='none'">
                            </div>
                            <div class="p-2.5 text-center border-t border-slate-200 bg-white flex-1 flex flex-col justify-center">
                                <p class="text-[11px] font-bold text-slate-900 leading-snug">Gambar {{ $fIdx + 1 }}. {{ $fotoItem['judul'] }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $fotoItem['tanggal'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    {{-- ========================================================================= --}}
    {{-- 2. PREVIEW: LAPORAN HARIAN (FORMAT E-KINERJA POIN A–C + GRID 2 KOLOM)     --}}
    {{-- ========================================================================= --}}
    @elseif($report->jenis_dokumen === 'Laporan Harian')
        @php
            $kegiatansHarian = $formData['kegiatan'] ?? [];
            $ringkasanHarian = $formData['harian_ringkasan'] ?? [];
            $harianDates = [];
            $barisHarian = [];
            $lampiranFotosHarian = [];

            foreach ($kegiatansHarian as $kIndex => $keg) {
                $rawJudul = trim($keg['judul'] ?? '');
                $rawHasil = trim($keg['hasil_kegiatan'] ?? '');
                $aiHasil  = trim($keg['hasil_kegiatan_ai'] ?? '');
                if (empty($rawJudul) && empty($rawHasil) && empty($aiHasil)) {
                    continue;
                }

                $tglItem = !empty($keg['tanggal']) ? $keg['tanggal'] : $report->tanggal;
                if ($tglItem) {
                    $harianDates[] = strtotime($tglItem);
                }

                $tglKegHarian = $formatTglIndo($tglItem);
                $waktuKegHarian = !empty($keg['waktu_mulai'])
                    ? $keg['waktu_mulai'] . (!empty($keg['waktu_selesai']) ? ' – ' . $keg['waktu_selesai'] . ' WIB' : ' WIB')
                    : '-';

                $judulHarian = !empty($rawJudul) ? $rawJudul : 'Pelaksanaan Kegiatan ' . ($kIndex + 1);
                $uraianHarian = !empty($aiHasil) ? $aiHasil : (!empty($rawHasil) ? $rawHasil : $judulHarian);
                $outputHarian = trim($keg['output_ai'] ?? ($keg['output'] ?? ''))
                    ?: 'Kegiatan terlaksana dengan tertib dan sesuai target harian.';

                $barisHarian[] = [
                    'tanggal' => $tglKegHarian,
                    'waktu'   => $waktuKegHarian,
                    'lokasi'  => trim($keg['lokasi'] ?? '-'),
                    'judul'   => $judulHarian,
                    'uraian'  => $uraianHarian,
                    'output'  => $outputHarian,
                ];

                foreach (['foto_1_path', 'foto_2_path', 'foto_3_path', 'foto_4_path'] as $fotoKey) {
                    if (!empty($keg[$fotoKey])) {
                        $lampiranFotosHarian[] = [
                            'url'     => $getImageUrl($keg[$fotoKey]),
                            'judul'   => $judulHarian,
                            'tanggal' => $tglKegHarian,
                        ];
                    }
                }
            }

            $tglUtamaTs = count($harianDates) > 0 ? max($harianDates) : strtotime($report->tanggal);
            $tglHeaderHarian = count($harianDates) > 0
                ? $formatTglIndo(date('Y-m-d', min($harianDates)))
                : $formatTglIndo($report->tanggal);
            $tglTtdHarian = $tglSaja(date('Y-m-d', $tglUtamaTs));

            $teksPenutupHarian = trim($ringkasanHarian['penutup'] ?? '')
                ?: 'Demikian laporan kegiatan harian ini dibuat dengan sebenarnya sebagai bahan pertanggungjawaban pelaksanaan tugas kepada pimpinan. Atas perhatiannya diucapkan terima kasih.';
        @endphp

        {{-- Judul Naskah Dinas Laporan Harian --}}
        <div class="text-center mb-6">
            <h3 class="font-black text-base uppercase tracking-wider text-slate-950 border-b-2 border-slate-900 inline-block pb-1">LAPORAN KEGIATAN HARIAN</h3>
            <p class="font-bold text-xs uppercase tracking-wide text-slate-700 mt-1.5">{{ strtoupper($tglHeaderHarian) }}</p>
        </div>

        <div class="space-y-5 text-xs text-slate-800 leading-relaxed">
            {{-- POIN A: IDENTITAS PELAKSANA --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-1.5">A. Identitas Pelaksana</h4>
                <div class="pl-4">
                    <table class="w-full text-xs">
                        <tr>
                            <td class="font-semibold w-36 py-0.5 text-slate-700">Nama Pegawai</td>
                            <td class="w-3">:</td>
                            <td class="font-bold text-slate-950">{{ $pembuat->name ?? '-' }}</td>
                        </tr>
                        @if(!$isPembuatTenagaAhli)
                            <tr>
                                <td class="font-semibold py-0.5 text-slate-700">NIP</td>
                                <td>:</td>
                                <td class="font-semibold text-slate-900">{{ $pembuatNipBersih }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="font-semibold py-0.5 text-slate-700">Jabatan / Status</td>
                            <td>:</td>
                            <td class="font-semibold text-slate-900">{{ $pembuat->jabatan ?? ($isPembuatTenagaAhli ? 'Tenaga Ahli' : 'Staf Pelaksana') }}</td>
                        </tr>
                        <tr>
                            <td class="font-semibold py-0.5 text-slate-700">Unit Kerja</td>
                            <td>:</td>
                            <td class="font-semibold text-slate-900">{{ ucwords(strtolower($namaBidangResmi)) }}</td>
                        </tr>
                        <tr>
                            <td class="font-semibold py-0.5 text-slate-700">Hari / Tanggal</td>
                            <td>:</td>
                            <td class="font-semibold text-slate-900">{{ $tglHeaderHarian }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- POIN B: PELAKSANAAN KEGIATAN (TABEL RESMI) --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-2">B. Pelaksanaan Kegiatan</h4>
                <div class="pl-4">
                    <p class="mb-2.5 text-justify">Adapun rincian pelaksanaan kegiatan harian pada {{ $tglHeaderHarian }} adalah sebagai berikut:</p>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-slate-900 text-[11px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-950 font-bold text-center">
                                    <th class="border border-slate-900 px-2 py-2 w-9">No.</th>
                                    <th class="border border-slate-900 px-2.5 py-2 w-36">Waktu &amp; Lokasi</th>
                                    <th class="border border-slate-900 px-2.5 py-2">Uraian Kegiatan</th>
                                    <th class="border border-slate-900 px-2.5 py-2 w-40">Hasil / Output</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($barisHarian as $idxRow => $row)
                                    <tr class="align-top">
                                        <td class="border border-slate-900 px-2 py-2 text-center font-semibold">{{ $idxRow + 1 }}.</td>
                                        <td class="border border-slate-900 px-2.5 py-2">
                                            <div class="font-semibold text-slate-900">{{ $row['waktu'] }}</div>
                                            @if(!empty($row['lokasi']) && $row['lokasi'] !== '-')
                                                <div class="text-[10px] text-slate-600 italic mt-0.5">{{ $row['lokasi'] }}</div>
                                            @endif
                                        </td>
                                        <td class="border border-slate-900 px-2.5 py-2 text-justify">
                                            <div class="font-bold text-slate-950 mb-0.5">{{ $row['judul'] }}</div>
                                            <div class="text-slate-700 whitespace-pre-line">{{ $row['uraian'] }}</div>
                                        </td>
                                        <td class="border border-slate-900 px-2.5 py-2 text-justify text-slate-800 whitespace-pre-line">
                                            {{ $row['output'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="border border-slate-900 px-3 py-4 text-center italic text-slate-500">
                                            Tidak ada rincian kegiatan harian yang dicatat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- POIN C: HASIL YANG DICAPAI DAN PENUTUP --}}
            <div>
                <h4 class="font-bold text-xs uppercase text-slate-950 mb-1.5">C. Hasil yang Dicapai dan Penutup</h4>
                <div class="pl-4 space-y-2.5">
                    @if(!empty($ringkasanHarian['hasil_dicapai']))
                        @if(is_array($ringkasanHarian['hasil_dicapai']))
                            <ol class="list-decimal pl-5 space-y-1 text-justify">
                                @foreach($ringkasanHarian['hasil_dicapai'] as $itemHasil)
                                    <li>{{ $itemHasil }}</li>
                                @endforeach
                            </ol>
                        @else
                            <p class="text-justify whitespace-pre-line">{{ $ringkasanHarian['hasil_dicapai'] }}</p>
                        @endif
                    @elseif(count($barisHarian) > 0)
                        <ol class="list-decimal pl-5 space-y-1 text-justify">
                            @foreach($barisHarian as $row)
                                <li><span class="font-semibold">{{ $row['judul'] }}:</span> {{ $row['output'] }}</li>
                            @endforeach
                        </ol>
                    @endif
                    <p class="text-justify">{{ $teksPenutupHarian }}</p>
                </div>
            </div>
        </div>

        {{-- Tanda Tangan Laporan Harian: 2 kolom sejajar jika TTD Mengetahui, 1 kolom di kanan jika tanpa TTD Mengetahui --}}
        @if($pakaiTtd === 'ya')
            <div style="margin-top: 36px; padding-top: 12px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; width: 100%; font-size: 12px; line-height: 1.5; color: #0f172a;">
                {{-- Kiri: Mengetahui (Pimpinan) --}}
                <div style="width: 50%; text-align: center;">
                    <p style="visibility: hidden;">Kayuagung, {{ $tglTtdHarian }}</p>
                    <p style="margin-top: 2px;">Mengetahui,</p>
                    <p style="margin-top: 2px; font-weight: 700;">{{ strtoupper($ttdJabatan) }}</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $ttdNama }}</p>
                    @if(!$isTtdTenagaAhli && !empty($ttdNipBersih) && $ttdNipBersih !== '-')
                        <p style="color: #475569;">NIP. {{ $ttdNipBersih }}</p>
                    @endif
                </div>

                {{-- Kanan: Yang Menyusun --}}
                <div style="width: 50%; text-align: center;">
                    <p>Kayuagung, {{ $tglTtdHarian }}</p>
                    <p style="margin-top: 2px;">Yang Menyusun,</p>
                    <p style="margin-top: 2px; visibility: hidden;">Penyusun</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $pembuat->name ?? '-' }}</p>
                    @if(!$isPembuatTenagaAhli && !empty($pembuatNipBersih))
                        <p style="color: #475569;">NIP. {{ $pembuatNipBersih }}</p>
                    @endif
                </div>
            </div>
        @else
            <div style="margin-top: 36px; padding-top: 12px; display: flex; justify-content: flex-end; width: 100%;">
                <div style="width: 260px; text-align: center; font-size: 12px; line-height: 1.5; color: #0f172a;">
                    <p>Kayuagung, {{ $tglTtdHarian }}</p>
                    <p style="margin-top: 2px;">Yang Menyusun,</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $pembuat->name ?? '-' }}</p>
                    @if(!$isPembuatTenagaAhli && !empty($pembuatNipBersih))
                        <p style="color: #475569;">NIP. {{ $pembuatNipBersih }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- LAMPIRAN DOKUMENTASI KEGIATAN HARIAN (GRID 2 KOLOM) --}}
        @if(count($lampiranFotosHarian) > 0)
            <div class="mt-10 pt-8 border-t-2 border-dashed border-slate-300">
                <div class="text-center mb-5">
                    <h4 class="font-black text-xs md:text-sm uppercase tracking-wider text-slate-950 border-b border-slate-900 inline-block pb-0.5">
                        LAMPIRAN DOKUMENTASI KEGIATAN
                    </h4>
                    <p class="text-[11px] font-semibold text-slate-600 mt-1">{{ $namaBidangResmi }} • {{ $tglHeaderHarian }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($lampiranFotosHarian as $fIdx => $fotoItem)
                        <div class="border border-slate-300 rounded-lg overflow-hidden bg-slate-50 flex flex-col">
                            <div class="w-full h-44 bg-slate-200 overflow-hidden">
                                <img src="{{ $fotoItem['url'] }}" alt="Dokumentasi {{ $fIdx + 1 }}" class="w-full h-full object-cover" onerror="this.parentElement.parentElement.style.display='none'">
                            </div>
                            <div class="p-2.5 text-center border-t border-slate-200 bg-white flex-1 flex flex-col justify-center">
                                <p class="text-[11px] font-bold text-slate-900 leading-snug">Gambar {{ $fIdx + 1 }}. {{ $fotoItem['judul'] }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $fotoItem['tanggal'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    {{-- ========================================================================= --}}
    {{-- 3. PREVIEW: NOTULENSI RAPAT --}}
    {{-- ========================================================================= --}}
    @elseif($report->jenis_dokumen === 'Notulensi Rapat')
        @php
            $ketuaNama = trim($formData['notulen_ketua'] ?? '');
            $notulisNama = trim($formData['notulen_pencatat'] ?? '');
            if (empty($notulisNama)) {
                $notulisNama = $pembuat->name ?? '';
            }

            $ketuaNip = null;
            if (!empty($ketuaNama)) {
                $uKetua = \App\Models\User::where('name', $ketuaNama)->first();
                if ($uKetua && ($uKetua->status_pegawai ?? 'ASN') !== 'Tenaga Ahli') {
                    $nipTmp = trim($uKetua->nip ?? '');
                    if (!empty($nipTmp) && $nipTmp !== '-') {
                        $ketuaNip = $nipTmp;
                    }
                }
            }

            $notulisNip = null;
            if (!empty($notulisNama)) {
                $uNotulis = \App\Models\User::where('name', $notulisNama)->first();
                if ($uNotulis && ($uNotulis->status_pegawai ?? 'ASN') !== 'Tenaga Ahli') {
                    $nipTmp = trim($uNotulis->nip ?? '');
                    if (!empty($nipTmp) && $nipTmp !== '-') {
                        $notulisNip = $nipTmp;
                    }
                }
            }
        @endphp

        <div class="text-center mb-6">
            <h3 class="font-black text-base uppercase tracking-widest text-slate-950 border-b-2 border-slate-900 inline-block pb-1">NOTULA</h3>
        </div>

        <div class="space-y-5 text-xs">
            <table class="w-full">
                <tr>
                    <td class="font-bold w-36 py-1">Sidang / Rapat</td>
                    <td class="w-3">:</td>
                    <td class="font-semibold">{{ $formData['notulen_judul'] ?? $report->judul }}</td>
                </tr>
                <tr>
                    <td class="font-bold py-1">Hari / Tanggal</td>
                    <td>:</td>
                    <td class="font-semibold">{{ $formatTglIndo($formData['notulen_tanggal'] ?? $report->tanggal) }}</td>
                </tr>
                <tr>
                    <td class="font-bold py-1">Waktu</td>
                    <td>:</td>
                    <td class="font-semibold">{{ ($formData['notulen_waktu_mulai'] ?? '-') . (!empty($formData['notulen_waktu_selesai']) ? ' - ' . $formData['notulen_waktu_selesai'] . ' WIB' : ' WIB') }}</td>
                </tr>
            </table>

            <div class="pt-2">
                <p class="font-bold text-xs uppercase tracking-wide mb-1 text-slate-900">Pimpinan Sidang / Rapat:</p>
                <table class="w-full pl-2">
                    <tr>
                        <td class="font-semibold w-36 py-0.5">- Ketua</td>
                        <td class="w-3">:</td>
                        <td>{{ $formData['notulen_ketua'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold py-0.5">- Sekretaris</td>
                        <td>:</td>
                        <td>{{ $formData['notulen_sekretaris'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold py-0.5">- Pencatat (Notulis)</td>
                        <td>:</td>
                        <td>{{ $formData['notulen_pencatat'] ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            <div class="pt-2">
                <p class="font-bold text-xs uppercase tracking-wide mb-1 text-slate-900">Peserta Sidang / Rapat:</p>
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 whitespace-pre-line text-slate-700">
                    {{ $formData['notulen_peserta'] ?? '-' }}
                </div>
            </div>

            <div class="pt-2">
                <p class="font-bold text-xs uppercase tracking-wide mb-1 text-slate-900">Kegiatan Sidang / Rapat (Isi & Keputusan):</p>
                <div class="bg-white p-4 rounded-lg border border-slate-200 whitespace-pre-line text-slate-800 leading-relaxed text-justify">
                    {{ $formData['notulen_hasil_ai'] ?? $formData['notulen_hasil'] ?? '-' }}
                </div>
            </div>
        </div>

        {{-- Tanda Tangan Notula: 2 kolom sejajar jika TTD Mengetahui, 1 kolom di kanan jika tanpa TTD Mengetahui --}}
        @php
            $tglNotulenSaja = $tglSaja($formData['notulen_tanggal'] ?? $report->tanggal);
            $namaNotulisTtd = !empty($notulisNama) ? $notulisNama : ($pembuat->name ?? '-');
        @endphp
        @if($pakaiTtd === 'ya')
            <div style="margin-top: 40px; padding-top: 16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; width: 100%; font-size: 12px; line-height: 1.5; color: #0f172a;">
                {{-- Kiri: Mengetahui (Pimpinan) --}}
                <div style="width: 50%; text-align: center;">
                    <p style="visibility: hidden;">Kayuagung, {{ $tglNotulenSaja }}</p>
                    <p style="margin-top: 2px;">Mengetahui,</p>
                    <p style="margin-top: 2px; font-weight: 700;">{{ strtoupper($ttdJabatan) }}</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $ttdNama }}</p>
                    @if(!$isTtdTenagaAhli && !empty($ttdNipBersih) && $ttdNipBersih !== '-')
                        <p style="color: #475569;">NIP. {{ $ttdNipBersih }}</p>
                    @endif
                </div>

                {{-- Kanan: Yang Menyusun (Notulis) --}}
                <div style="width: 50%; text-align: center;">
                    <p>Kayuagung, {{ $tglNotulenSaja }}</p>
                    <p style="margin-top: 2px;">Yang Menyusun,</p>
                    <p style="margin-top: 2px; visibility: hidden;">Penyusun</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $namaNotulisTtd }}</p>
                    @if($notulisNip)
                        <p style="color: #475569;">NIP. {{ $notulisNip }}</p>
                    @endif
                </div>
            </div>
        @else
            <div style="margin-top: 40px; padding-top: 16px; display: flex; justify-content: flex-end; width: 100%;">
                <div style="width: 260px; text-align: center; font-size: 12px; line-height: 1.5; color: #0f172a;">
                    <p>Kayuagung, {{ $tglNotulenSaja }}</p>
                    <p style="margin-top: 2px;">Yang Menyusun,</p>
                    <div style="height: 64px;"></div>
                    <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $namaNotulisTtd }}</p>
                    @if($notulisNip)
                        <p style="color: #475569;">NIP. {{ $notulisNip }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Lampiran Dokumentasi Notulensi Rapat (Grid 2 Kolom) --}}
        @php
            $notulenFotos = [];
            if (!empty($formData['notulen_foto_1_path'])) {
                $notulenFotos[] = ['url' => $getImageUrl($formData['notulen_foto_1_path']), 'label' => 'Dokumentasi Rapat 1'];
            }
            if (!empty($formData['notulen_foto_2_path'])) {
                $notulenFotos[] = ['url' => $getImageUrl($formData['notulen_foto_2_path']), 'label' => 'Dokumentasi Rapat 2'];
            }
        @endphp
        @if(count($notulenFotos) > 0)
            <div class="mt-8 pt-6 border-t-2 border-dashed border-slate-300">
                <div class="text-center mb-4">
                    <h4 class="font-black text-xs md:text-sm uppercase tracking-wider text-slate-950 border-b border-slate-900 inline-block pb-0.5">
                        DOKUMENTASI NOTULENSI RAPAT
                    </h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($notulenFotos as $nIdx => $nFoto)
                        <div class="border border-slate-300 rounded-lg overflow-hidden bg-slate-50 flex flex-col">
                            <div class="w-full h-44 bg-slate-200 overflow-hidden">
                                <img src="{{ $nFoto['url'] }}" alt="{{ $nFoto['label'] }}" class="w-full h-full object-cover" onerror="this.parentElement.parentElement.style.display='none'">
                            </div>
                            <div class="p-2 text-center border-t border-slate-200 bg-white">
                                <p class="text-[11px] font-bold text-slate-900">{{ $nFoto['label'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    {{-- ========================================================================= --}}
    {{-- 4. PREVIEW: LAPORAN HASIL PERJALANAN DINAS (LHPD) --}}
    {{-- ========================================================================= --}}
    @elseif($report->jenis_dokumen === 'Laporan Perjalanan Dinas')
        @php
            $batasPetugas = (int) ($formData['sppd_jumlah_petugas'] ?? count($formData['sppd_petugas'] ?? []));
            $petugasSppd = array_filter(array_slice($formData['sppd_petugas'] ?? [], 0, $batasPetugas), function($p) {
                return !empty(trim($p['nama'] ?? ''));
            });
            $tglSppdSaja = $tglSaja($formData['sppd_tanggal'] ?? ($formData['sppd_berangkat_tgl'] ?? $report->tanggal));
        @endphp

        <div class="text-center mb-6">
            <h3 class="font-black text-base uppercase tracking-widest text-slate-950 border-b-2 border-slate-900 inline-block pb-1">LAPORAN HASIL PERJALANAN</h3>
        </div>

        <div class="space-y-4 text-xs">
            <div class="flex items-start gap-2">
                <span class="font-bold w-6">1.</span>
                <div class="flex-1">
                    <span class="font-bold">Nama Petugas Berpergian:</span>
                    <div class="mt-2 space-y-1 pl-2">
                        @foreach($petugasSppd as $p)
                            @php
                                $nipBersih = trim($p['nip'] ?? '');
                                $statusPeg = $p['status_pegawai'] ?? null;
                                if (empty($statusPeg) && !empty($p['nama'])) {
                                    $matchedUser = \App\Models\User::where('name', $p['nama'])->first();
                                    if ($matchedUser) {
                                        $statusPeg = $matchedUser->status_pegawai;
                                    }
                                }
                                $isTenagaAhli = ($statusPeg === 'Tenaga Ahli') || empty($nipBersih) || $nipBersih === '-';
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="font-semibold">- {{ $p['nama'] ?? '-' }}</span>
                                @if(!$isTenagaAhli && !empty($nipBersih) && $nipBersih !== '-')
                                    <span class="text-slate-500">/ NIP. {{ $nipBersih }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-2">
                <span class="font-bold w-6">2.</span>
                <div class="w-36 font-bold">Tujuan Perjalanan</div>
                <div class="w-3">:</div>
                <div class="flex-1 font-semibold">{{ $formData['sppd_tujuan'] ?? '-' }}</div>
            </div>

            <div class="flex items-start gap-2">
                <span class="font-bold w-6">3.</span>
                <div class="w-36 font-bold">Maksud Perjalanan</div>
                <div class="w-3">:</div>
                <div class="flex-1 font-semibold">{{ $formData['sppd_maksud'] ?? '-' }}</div>
            </div>

            <div class="flex items-start gap-2">
                <span class="font-bold w-6">4.</span>
                <div class="w-36 font-bold">Lama Perjalanan</div>
                <div class="w-3">:</div>
                <div class="flex-1 font-semibold">
                    {{ $formData['sppd_lama'] ?? ($formatTglIndo($formData['sppd_berangkat_tgl'] ?? $report->tanggal) . ' s/d ' . $formatTglIndo($formData['sppd_kembali_tgl'] ?? $report->tanggal)) }}
                </div>
            </div>

            <div class="flex items-start gap-2">
                <span class="font-bold w-6">5.</span>
                <div class="w-36 font-bold">Waktu Perjalanan</div>
                <div class="w-3">:</div>
                <div class="flex-1 font-semibold">Tanggal {{ $tglSppdSaja }}</div>
            </div>

            <div class="flex items-start gap-2 pt-2">
                <span class="font-bold w-6">6.</span>
                <div class="flex-1">
                    <p class="font-bold mb-1">Hasil Perjalanan:</p>
                    <div class="bg-white p-4 rounded-lg border border-slate-200 whitespace-pre-line text-slate-800 leading-relaxed text-justify">
                        {{ $formData['sppd_hasil_ai'] ?? $formData['sppd_hasil'] ?? '-' }}
                    </div>
                </div>
            </div>

            {{-- Tanda Tangan SPPD: 2 kolom sejajar jika TTD Mengetahui, 1 kolom di kanan jika tanpa TTD Mengetahui --}}
            @php
                $hasMultiplePetugas = count($petugasSppd) > 1;
            @endphp
            @if($pakaiTtd === 'ya')
                <div style="margin-top: 36px; padding-top: 16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; width: 100%; font-size: 12px; line-height: 1.5; color: #0f172a;">
                    {{-- Kiri: Mengetahui (Pimpinan) --}}
                    <div style="width: 50%; text-align: center;">
                        <p style="visibility: hidden;">Kayuagung, {{ $tglSppdSaja }}</p>
                        <p style="margin-top: 2px;">Mengetahui,</p>
                        <p style="margin-top: 2px; font-weight: 700;">{{ strtoupper($ttdJabatan) }}</p>
                        <div style="height: 64px;"></div>
                        <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $ttdNama }}</p>
                        @if(!$isTtdTenagaAhli && !empty($ttdNipBersih) && $ttdNipBersih !== '-')
                            <p style="color: #475569;">NIP. {{ $ttdNipBersih }}</p>
                        @endif
                    </div>

                    {{-- Kanan: Yang Menyusun --}}
                    <div style="width: 50%; text-align: center;">
                        <p>Kayuagung, {{ $tglSppdSaja }}</p>
                        <p style="margin-top: 2px;">Yang Menyusun,</p>
                        <p style="margin-top: 2px; visibility: hidden;">Penyusun</p>
                        @if($hasMultiplePetugas)
                            <div style="margin-top: 16px; text-align: center;">
                                @foreach($petugasSppd as $pIdx => $p)
                                    @php
                                        $pNip = trim($p['nip'] ?? '');
                                        $pStat = $p['status_pegawai'] ?? null;
                                        if (empty($pStat)) {
                                            $mU = \App\Models\User::where('name', $p['nama'] ?? '')->first();
                                            if ($mU) $pStat = $mU->status_pegawai;
                                        }
                                        $pIsTA = ($pStat === 'Tenaga Ahli') || empty($pNip) || $pNip === '-' || str_starts_with(strtoupper($pNip), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $pNip);
                                    @endphp
                                    <div style="margin-bottom: 16px;">
                                        <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ ($pIdx + 1) }}. {{ $p['nama'] ?? '-' }}</p>
                                        @if(!$pIsTA && !empty($pNip))
                                            <p style="color: #475569;">NIP. {{ $pNip }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @php
                                $singlePetugas = $petugasSppd[0] ?? [];
                                $singleNama = $singlePetugas['nama'] ?? ($pembuat->name ?? '-');
                                $singleNip = trim($singlePetugas['nip'] ?? ($pembuatNipBersih ?? ''));
                                $singleStat = $singlePetugas['status_pegawai'] ?? ($pembuat->status_pegawai ?? 'ASN');
                                $singleIsTA = ($singleStat === 'Tenaga Ahli') || empty($singleNip) || $singleNip === '-' || str_starts_with(strtoupper($singleNip), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $singleNip);
                            @endphp
                            <div style="height: 64px;"></div>
                            <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $singleNama }}</p>
                            @if(!$singleIsTA && !empty($singleNip))
                                <p style="color: #475569;">NIP. {{ $singleNip }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                <div style="margin-top: 36px; padding-top: 16px; display: flex; justify-content: flex-end; width: 100%;">
                    <div style="width: 280px; text-align: center; font-size: 12px; line-height: 1.5; color: #0f172a;">
                        <p>Kayuagung, {{ $tglSppdSaja }}</p>
                        <p style="margin-top: 2px;">Yang Menyusun,</p>
                        @if($hasMultiplePetugas)
                            <div style="margin-top: 16px; text-align: center;">
                                @foreach($petugasSppd as $pIdx => $p)
                                    @php
                                        $pNip = trim($p['nip'] ?? '');
                                        $pStat = $p['status_pegawai'] ?? null;
                                        if (empty($pStat)) {
                                            $mU = \App\Models\User::where('name', $p['nama'] ?? '')->first();
                                            if ($mU) $pStat = $mU->status_pegawai;
                                        }
                                        $pIsTA = ($pStat === 'Tenaga Ahli') || empty($pNip) || $pNip === '-' || str_starts_with(strtoupper($pNip), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $pNip);
                                    @endphp
                                    <div style="margin-bottom: 16px;">
                                        <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ ($pIdx + 1) }}. {{ $p['nama'] ?? '-' }}</p>
                                        @if(!$pIsTA && !empty($pNip))
                                            <p style="color: #475569;">NIP. {{ $pNip }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @php
                                $singlePetugas = $petugasSppd[0] ?? [];
                                $singleNama = $singlePetugas['nama'] ?? ($pembuat->name ?? '-');
                                $singleNip = trim($singlePetugas['nip'] ?? ($pembuatNipBersih ?? ''));
                                $singleStat = $singlePetugas['status_pegawai'] ?? ($pembuat->status_pegawai ?? 'ASN');
                                $singleIsTA = ($singleStat === 'Tenaga Ahli') || empty($singleNip) || $singleNip === '-' || str_starts_with(strtoupper($singleNip), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $singleNip);
                            @endphp
                            <div style="height: 64px;"></div>
                            <p style="font-weight: 700; text-decoration: underline; color: #020617;">{{ $singleNama }}</p>
                            @if(!$singleIsTA && !empty($singleNip))
                                <p style="color: #475569;">NIP. {{ $singleNip }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            {{-- Lampiran Dokumentasi SPPD --}}
            @php
                $sppdFotos = [];
                if (!empty($formData['sppd_foto_1_path'])) $sppdFotos[] = $getImageUrl($formData['sppd_foto_1_path']);
                if (!empty($formData['sppd_foto_2_path'])) $sppdFotos[] = $getImageUrl($formData['sppd_foto_2_path']);
            @endphp
            @if(count($sppdFotos) > 0)
                <div class="pt-6 border-t border-slate-200">
                    <p class="font-bold text-xs uppercase tracking-wide text-center mb-3">{{ $formData['sppd_judul_dokumentasi'] ?? 'Dokumentasi Hasil Perjalanan Dinas' }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($sppdFotos as $sIdx => $sUrl)
                            <div class="rounded-lg overflow-hidden border border-slate-200">
                                <img src="{{ $sUrl }}" alt="Dokumentasi {{ $sIdx + 1 }}" class="w-full h-44 object-cover">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>