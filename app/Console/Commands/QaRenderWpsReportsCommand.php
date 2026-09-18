<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use ReflectionMethod;
use Throwable;

class QaRenderWpsReportsCommand extends Command
{
    protected $signature = 'reports:qa-render-wps
        {--group= : mutasi|kayu-bulat|sawn-timber|standalone|all (default all)}
        {--start=2026-08-01 : start_date for date-range reports}
        {--end=2026-08-31 : end_date for date-range reports}
        {--only= : comma-separated URI fragments to include}
        {--skip= : comma-separated URI fragments to exclude}
        {--timeout-pages= : unused placeholder}';

    protected $description = 'Render all WPS report PDFs against the real SQL Server database and validate the output';

    private const GROUP_VARS = [
        'mutasi' => '$mutasiReportRouteDefinitions',
        'kayu-bulat' => '$kayuBulatReportRouteDefinitions',
        'sawn-timber' => '$sawnTimberReportRouteDefinitions',
        'standalone' => '$standaloneReportRouteDefinitions',
    ];

    /**
     * Real parameter values probed from the live WPS database (QA-only).
     *
     * @var array<string, array<string, string|int>>
     */
    private const REAL_PARAM_OVERRIDES = [
        'umur-barang-jadi-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-sawn-timber-detail-ton' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-finger-joint-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-laminating-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-moulding-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-reproses-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-cc-akhir-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-sanding-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'umur-s4s-detail' => ['Umur1' => 7, 'Umur2' => 14, 'Umur3' => 30, 'Umur4' => 60],
        'penerimaan-kayu-bulat-int-ton' => ['NoKayuBulat' => 'A.017210', 'no_kayu_bulat' => 'A.017210'],
        'penerimaan-kayu-bulat-ext-ton' => ['NoKayuBulat' => 'A.017210', 'no_kayu_bulat' => 'A.017210'],
        'penerimaan-kayu-bulat-kg' => ['NoKayuBulat' => 'A.017209', 'no_kayu_bulat' => 'A.017209'],
        'penerimaan-kayu-bulat-extkg' => ['NoKayuBulat' => 'A.017209', 'no_kayu_bulat' => 'A.017209'],
        'perbandingan-kb-masuk-periode' => ['period_1_start_date' => '2026-07-01', 'period_1_end_date' => '2026-07-31', 'period_2_start_date' => '2026-08-01', 'period_2_end_date' => '2026-08-31'],
        'kd-upah-per-no-proc-kd-per-customer-detail' => ['no_proc_kd' => 'H.000272', 'NoProcKD' => 'H.000272'],
        'serah-terima-st-kamar-kd' => ['no_proc_kd' => 'H.000275', 'NoProcKD' => 'H.000275'],
        'stok-opname-st-detail-kd' => ['no_proc_kd' => 'H.001275', 'NoProcKD' => 'H.001275'],
        'penerimaan-st-hasil-sawmill' => ['no_pen_st' => 'B.162219', 'NoPenST' => 'B.162219'],
        'detail-lembar-tally-hasil-sawmill' => [],
        'lembar-tally-hasil-sawmill' => ['no_produksi' => 'D.046026', 'NoProduksi' => 'D.046026'],
        'tracing-st' => ['no_produk' => 'E.519969', 'NoProduk' => 'E.519969'],
        'total-bagus-kulit-rambung' => ['report_date' => '2026-08-31', 'tanggal' => '2026-08-31', 'TglSawmill' => '2026-08-31'],
        'proses-produksi/produksi-per-nomor-produksi' => ['no_produksi' => 'VA.003044', 'NoProduksi' => 'VA.003044'],
        'produksi-fj-per-nomor-produksi' => ['no_produksi' => 'SA.004740', 'NoProduksi' => 'SA.004740'],
        'produksi-laminating-per-nomor-produksi' => ['no_produksi' => 'UA.006150', 'NoProduksi' => 'UA.006150'],
        'produksi-moulding-per-nomor-produksi' => ['no_produksi' => 'TA.003301', 'NoProduksi' => 'TA.003301'],
        'produksi-packing-per-nomor-produksi' => ['no_produksi' => 'X.001523', 'NoProduksi' => 'X.001523'],
        'produksi-s4s-per-nomor-produksi' => ['no_produksi' => 'RA.003589', 'NoProduksi' => 'RA.003589'],
        'produksi-sanding-per-nomor-produksi' => ['no_produksi' => 'WA.001522', 'NoProduksi' => 'WA.001522'],
        'rekap-rendemen-non-rambung' => ['year' => 2026, 'Tahun' => 2026, 'month' => 8, 'Bulan' => 8],
        'rekap-rendemen-rambung' => ['year' => 2026, 'Tahun' => 2026, 'month' => 8, 'Bulan' => 8],
        'penjualan-barang-jadi-m3' => ['no_jual' => 'J.001257', 'NoJual' => 'J.001257'],
        'koordinat-tanah' => ['no_spk' => '2026-97', 'NoSPK' => '2026-97'],
        'produksi-per-spk' => ['no_spk' => '2026-97', 'NoSPK' => '2026-97'],
        'penjualan/surat-jalan' => ['no_jual' => 'G.002609', 'NoJual' => 'G.002609', 'no_surat_jalan' => 'G.002609'],
    ];

    public function handle(): int
    {
        $group = (string) $this->option('group') ?: 'all';
        $groups = $group === 'all' ? array_keys(self::GROUP_VARS) : [$group];
        $only = array_filter(explode(',', (string) $this->option('only')));
        $skip = array_filter(explode(',', (string) $this->option('skip')));
        $start = (string) $this->option('start');
        $end = (string) $this->option('end');

        $outDir = storage_path('app/wps-qa');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        $entries = [];
        $routesFile = file_get_contents(base_path('routes/api.php'));
        foreach ($groups as $g) {
            $entries = array_merge($entries, $this->parseGroup($routesFile, self::GROUP_VARS[$g]));
        }

        if ($only !== []) {
            $entries = array_values(array_filter($entries, fn (array $e) => (bool) array_filter($only, fn (string $f) => str_contains($e['uri'], $f))));
        }
        if ($skip !== []) {
            $entries = array_values(array_filter($entries, fn (array $e) => ! (bool) array_filter($skip, fn (string $f) => str_contains($e['uri'], $f))));
        }

        $this->info(sprintf('QA render: %d WPS reports, range %s .. %s, output: %s', count($entries), $start, $end, $outDir));

        $token = $this->issueJwt();
        $kernel = $this->laravel->make(HttpKernel::class);
        $rows = [];

        foreach ($entries as $i => $entry) {
            $params = $this->buildParams($entry['name'], $start, $end);
            if ($params === null) {
                $rows[] = [$entry['uri'], 'SKIP', 'no FormRequest / preview signature', '', ''];
                $this->line(sprintf('[%d/%d] SKIP  %s (no FormRequest)', $i + 1, count($entries), $entry['uri']));

                continue;
            }

            [$status, $contentType, $body, $note] = $this->callPdf($kernel, $entry['uri'], $params, $token);
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(preg_replace('#^/reports/#', '', $entry['uri'])));

            if ($status === 200 && str_starts_with($contentType, 'application/pdf')) {
                $pages = $this->countPages($body);
                file_put_contents($outDir.'/'.$slug.'.pdf', $body);
                $rows[] = [$entry['uri'], 'OK', sprintf('%s KB, %s page(s)', number_format(strlen($body) / 1024, 1), $pages ?: '?'), '', ''];
                $this->line(sprintf('[%d/%d] OK    %s (%s KB, %s pages)', $i + 1, count($entries), $entry['uri'], number_format(strlen($body) / 1024, 1), $pages ?: '?'));
            } elseif ($status === 422) {
                $rows[] = [$entry['uri'], 'PARAM', $note, '', ''];
                $this->line(sprintf('[%d/%d] PARAM %s -> %s', $i + 1, count($entries), $entry['uri'], $note));
            } else {
                $rows[] = [$entry['uri'], $status === 0 ? 'ERROR' : (string) $status, $note, '', ''];
                $this->line(sprintf('[%d/%d] FAIL  %s [%s] %s', $i + 1, count($entries), $entry['uri'], $status, $note));
            }
        }

        $this->table(['URI', 'STATUS', 'NOTE'], $rows);
        $ok = count(array_filter($rows, fn (array $r) => $r[1] === 'OK'));
        $this->info(sprintf('SUMMARY: %d/%d OK, %d param-needed, %d failed', $ok, count($rows), count(array_filter($rows, fn (array $r) => $r[1] === 'PARAM')), count($rows) - $ok));

        file_put_contents($outDir.'/results.json', json_encode(array_map(fn (array $r) => ['uri' => $r[0], 'status' => $r[1], 'note' => $r[2]], $rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function parseGroup(string $routesFile, string $varName): array
    {
        $pos = strpos($routesFile, $varName);
        if ($pos === false) {
            return [];
        }
        $end = strpos($routesFile, '];', $pos);
        $chunk = substr($routesFile, $pos, $end - $pos);
        preg_match_all("/\['(\/reports\/[^']+)',\s*'([^']+)',\s*([A-Za-z0-9_\\\\:]+)::class\]/", $chunk, $m, PREG_SET_ORDER);

        return array_map(fn (array $s) => ['uri' => $s[1], 'name' => $s[2], 'controller' => $s[3]], $m);
    }

    private function buildParams(string $routeName, string $start, string $end): ?array
    {
        $route = app('router')->getRoutes()->getByName($routeName.'.preview')
            ?? app('router')->getRoutes()->getByName($routeName);
        $controllerClass = $route?->getControllerClass();
        if ($controllerClass === null || ! class_exists($controllerClass)) {
            $this->line("        [debug] route={$routeName} class=".(($route === null) ? 'ROUTE-NULL' : ($controllerClass ?? 'CLASS-NULL')));

            return null;
        }
        try {
            $ref = new ReflectionMethod($controllerClass, 'preview');
        } catch (Throwable) {
            return null;
        }
        $requestClass = null;
        foreach ($ref->getParameters() as $p) {
            $type = $p->getType();
            if ($type !== null && ! $type->isBuiltin() && is_subclass_of((string) $type, FormRequest::class)) {
                $requestClass = (string) $type;
                break;
            }
        }
        if ($requestClass === null) {
            // Plain Request controller (no validation rules) - just send the base date params.
            return [
                'start_date' => $start,
                'end_date' => $end,
                'TglAwal' => $start,
                'TglAkhir' => $end,
            ];
        }
        if (! class_exists($requestClass)) {
            return null;
        }
        try {
            $rules = (new $requestClass)->rules();
        } catch (Throwable) {
            return null;
        }

        $params = [
            'start_date' => $start,
            'end_date' => $end,
            'TglAwal' => $start,
            'TglAkhir' => $end,
        ];
        $missing = [];
        foreach ($rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            foreach ($rules as $rule) {
                if (is_string($rule) && str_starts_with($rule, 'required') && ! isset($params[$field]) && ! str_contains($rule, 'without')) {
                    $missing[] = $field;
                }
            }
        }
        if ($missing !== []) {
            $this->line(sprintf('        (unresolved required params: %s)', implode(', ', $missing)));
        }

        foreach (self::REAL_PARAM_OVERRIDES as $fragment => $overrides) {
            if (str_contains($routeName, $fragment) || str_contains($routeName, str_replace('/', '.', $fragment))) {
                $params = array_merge($params, $overrides);
                break;
            }
        }

        return $params;
    }

    private function callPdf(HttpKernel $kernel, string $uri, array $params, string $token): array
    {
        $request = Request::create('/api'.$uri.'/pdf', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($params));

        try {
            $response = $kernel->handle($request);
        } catch (Throwable $e) {
            return [0, '', '', 'exception: '.substr($e->getMessage(), 0, 200)];
        }

        $content = $response->getContent();
        $type = (string) $response->headers->get('Content-Type');
        $status = $response->getStatusCode();

        if ($status === 200 && str_starts_with($type, 'application/pdf')) {
            return [$status, $type, $content, ''];
        }

        $note = '';
        if (str_contains($type, 'json')) {
            $decoded = json_decode($content, true);
            $note = $decoded['message'] ?? $decoded['error'] ?? substr($content, 0, 200);
            if ($status === 422 && isset($decoded['errors'])) {
                $flat = [];
                foreach ($decoded['errors'] as $field => $msgs) {
                    $flat[] = $field.': '.(is_array($msgs) ? $msgs[0] : $msgs);
                }
                $note = implode('; ', $flat);
            }
        }

        return [$status, $type, '', substr((string) $note, 0, 240)];
    }

    private function countPages(string $pdf): int
    {
        $count = preg_match_all('/\/Type\s*\/Page[^s]/', $pdf);

        return max($count, 1);
    }

    private function issueJwt(): string
    {
        $secret = (string) config('reports.report_auth.jwt_secret', '');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7)) ?: substr($secret, 7);
        }
        $now = time();
        $payload = [
            'sub' => 'qa-render-agent',
            'username' => 'qa-render-agent',
            'name' => 'QA Render Agent',
            'scope' => config('reports.report_auth.required_scope'),
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + 7200,
            'jti' => bin2hex(random_bytes(8)),
        ];
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $b64(json_encode($payload));
        $sig = $b64(hash_hmac('sha256', $header.'.'.$body, $secret, true));

        return $header.'.'.$body.'.'.$sig;
    }
}
