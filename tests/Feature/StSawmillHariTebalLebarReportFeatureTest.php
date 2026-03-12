<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\StSawmillHariTebalLebarReportService;
use Mockery;
use Tests\TestCase;

class StSawmillHariTebalLebarReportFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.required_scope', null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_form_page_is_accessible(): void
    {
        $this->get('/reports/sawn-timber/st-sawmill-hari-tebal-lebar')
            ->assertOk()
            ->assertSee('Generate Laporan ST Sawmill / Hari / Tebal / Lebar');
    }

    public function test_preview_endpoint_returns_grouped_pivot_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StSawmillHariTebalLebarReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    [
                        'TglSawmill' => '2026-01-05',
                        'Group' => 'ST RACIP JABON',
                        'Tebal' => 10.0,
                        'Lebar' => 38.0,
                        'STton' => 0.0,
                        'IsGroup' => 1,
                    ],
                ],
                'date_keys' => ['2026-01-05'],
                'date_chunks' => [['2026-01-05']],
                'is_group_blocks' => [
                    [
                        'is_group' => 1,
                        'groups' => [
                            [
                                'name' => 'ST RACIP JABON',
                                'tebal_blocks' => [
                                    [
                                        'tebal' => 10.0,
                                        'lebar_rows' => [
                                            ['lebar' => 38.0, 'values' => ['2026-01-05' => 0.0]],
                                        ],
                                        'totals_by_date' => ['2026-01-05' => 0.0],
                                    ],
                                ],
                                'totals_by_date' => ['2026-01-05' => 0.0],
                            ],
                        ],
                        'totals_by_date' => ['2026-01-05' => 0.0],
                    ],
                ],
                'summary' => ['total_rows' => 1, 'total_dates' => 1, 'total_is_groups' => 1],
            ]);

        $this->app->instance(StSawmillHariTebalLebarReportService::class, $service);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ])->postJson('/api/reports/sawn-timber/st-sawmill-hari-tebal-lebar', [
            'TglAwal' => '2026-01-01',
            'TglAkhir' => '2026-01-31',
        ])->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('summary.total_is_groups', 1)
            ->assertJsonPath('data.date_keys.0', '2026-01-05')
            ->assertJsonPath('data.is_group_blocks.0.is_group', 1);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StSawmillHariTebalLebarReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'date_keys' => [],
                'date_chunks' => [],
                'is_group_blocks' => [],
                'summary' => ['total_rows' => 0, 'total_dates' => 0, 'total_is_groups' => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(StSawmillHariTebalLebarReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/st-sawmill-hari-tebal-lebar/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan ST Sawmill Hari Tebal Lebar');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StSawmillHariTebalLebarReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['TglSawmill', 'Group', 'Tebal', 'Lebar', 'STton', 'IsGroup'],
                'detected_columns' => ['TglSawmill', 'Group', 'Tebal', 'Lebar', 'STton', 'IsGroup'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(StSawmillHariTebalLebarReportService::class, $service);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ])->postJson('/api/reports/sawn-timber/st-sawmill-hari-tebal-lebar/health', [
            'TglAwal' => '2026-01-01',
            'TglAkhir' => '2026-01-31',
        ])->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31');
    }
}

