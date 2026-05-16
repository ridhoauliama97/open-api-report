<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\StHidupKeringReportService;
use Mockery;
use Tests\TestCase;

class StHidupKeringReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_preview_uses_hari_include_and_exclude_parameters(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StHidupKeringReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(120, ['INCLUDE', 'EXCLUDE'])
            ->andReturn($this->reportData());

        $this->app->instance(StHidupKeringReportService::class, $service);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/reports/sawn-timber/st-hidup-kering', [
                'hari' => 120,
                'include' => true,
                'exclude' => true,
            ])
            ->assertOk()
            ->assertJsonPath('meta.hari', 120)
            ->assertJsonPath('meta.include', true)
            ->assertJsonPath('meta.exclude', true)
            ->assertJsonPath('meta.modes.0', 'INCLUDE')
            ->assertJsonPath('meta.modes.1', 'EXCLUDE')
            ->assertJsonPath('meta.total_rows', 1);
    }

    public function test_pdf_uses_exclude_parameter(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StHidupKeringReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(90, ['EXCLUDE'])
            ->andReturn($this->reportData(false, true));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('reports.sawn-timber.st-hidup-kering-pdf', Mockery::on(
                static fn (array $data): bool => ($data['hari'] ?? null) === 90
                    && ($data['include'] ?? null) === false
                    && ($data['exclude'] ?? null) === true
                    && ($data['modes'] ?? null) === ['EXCLUDE']
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(StHidupKeringReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->withHeaders($this->authHeaders($user, 'application/pdf'))
            ->get('/api/reports/sawn-timber/st-hidup-kering/pdf?hari=90&include=0&exclude=1')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan ST Hidup Kering');
    }

    public function test_preview_allows_include_and_exclude_to_be_disabled(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StHidupKeringReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(30, [])
            ->andReturn([
                'rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'hari' => 30,
                    'include' => false,
                    'exclude' => false,
                    'modes' => [],
                ],
            ]);

        $this->app->instance(StHidupKeringReportService::class, $service);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/reports/sawn-timber/st-hidup-kering', [
                'hari' => 30,
                'include' => false,
                'exclude' => false,
            ])
            ->assertOk()
            ->assertJsonPath('meta.include', false)
            ->assertJsonPath('meta.exclude', false)
            ->assertJsonPath('meta.modes', [])
            ->assertJsonPath('meta.total_rows', 0);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user, string $accept = 'application/json'): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueJwtForUser($user),
            'Accept' => $accept,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(bool $include = true, bool $exclude = true): array
    {
        $modes = array_values(array_filter([
            $include ? 'INCLUDE' : null,
            $exclude ? 'EXCLUDE' : null,
        ]));

        return [
            'rows' => [
                [
                    'NoST' => 'ST001',
                    'Tebal' => 20,
                    'Lebar' => 90,
                    'JmlhBatang' => 10,
                    'IdLokasi' => 'A1',
                    'UsiaHari' => 120,
                    'Jenis' => 'JABON',
                    'BB' => 'BB001',
                ],
            ],
            'summary' => [
                'total_rows' => 1,
                'hari' => 120,
                'include' => $include,
                'exclude' => $exclude,
                'modes' => $modes,
            ],
        ];
    }
}
