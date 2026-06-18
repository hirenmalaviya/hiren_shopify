<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Upload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests cover upload + parsing only; don't run the import pipeline.
        Queue::fake();
    }

    private function sampleCsvUpload(): UploadedFile
    {
        $source = base_path('plan/shopify-products-csv (5) (1) (1).csv');
        $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        copy($source, $tmp);

        return new UploadedFile($tmp, 'products.csv', 'text/csv', null, true);
    }

    private function makeCsv(string $contents, string $name = 'products.csv'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        file_put_contents($tmp, $contents);

        return new UploadedFile($tmp, $name, 'text/csv', null, true);
    }

    public function test_valid_csv_creates_upload_and_product_rows(): void
    {
        Storage::fake('local');

        $response = $this->post('/uploads', ['file' => $this->sampleCsvUpload()]);

        $upload = Upload::firstOrFail();
        $response->assertRedirect("/uploads/{$upload->id}");
        $response->assertSessionHas('success');

        $this->assertSame(10, $upload->total_rows);
        $this->assertSame(10, $upload->products()->count());
        $this->assertSame(10, $upload->products()->where('status', ProductStatus::Pending->value)->count());
        $this->assertSame(0, $upload->failed_rows);

        $first = $upload->products()->orderBy('row_number')->first();
        $this->assertSame('modern-desk-lamp', $first->handle);
        $this->assertSame('MDL-001', $first->sku);
        $this->assertEquals(39.99, (float) $first->price);
        $this->assertTrue($first->published);
    }

    public function test_rejects_file_missing_required_columns(): void
    {
        Storage::fake('local');

        $csv = $this->makeCsv("Handle,Title\nx,Y\n");

        $response = $this->from('/uploads')->post('/uploads', ['file' => $csv]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, Upload::count());
    }

    public function test_rejects_non_csv_file(): void
    {
        Storage::fake('local');

        $response = $this->from('/uploads')->post('/uploads', [
            'file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, Upload::count());
    }

    public function test_invalid_rows_are_marked_failed(): void
    {
        Storage::fake('local');

        $header = 'Handle,Title,Variant SKU,Variant Price';
        $rows = [
            'good-1,Good One,SKU1,10.00',   // valid
            'bad-price,Bad Price,SKU2,abc', // invalid price
            ',No Handle,SKU3,5.00',         // missing handle
        ];
        $csv = $this->makeCsv($header."\n".implode("\n", $rows)."\n");

        $this->post('/uploads', ['file' => $csv]);

        $upload = Upload::firstOrFail();
        $this->assertSame(3, $upload->total_rows);
        $this->assertSame(2, $upload->failed_rows);
        $this->assertSame(1, $upload->products()->where('status', ProductStatus::Pending->value)->count());

        $bad = $upload->products()->where('row_number', 2)->first();
        $this->assertSame(ProductStatus::Failed, $bad->status);
        $this->assertStringContainsString('Variant Price', (string) $bad->error_message);
    }

    public function test_duplicate_handle_in_file_is_skipped(): void
    {
        Storage::fake('local');

        $header = 'Handle,Title,Variant SKU,Variant Price';
        $rows = [
            'dup,First,SKU1,10.00',
            'dup,Second,SKU2,12.00',
        ];
        $csv = $this->makeCsv($header."\n".implode("\n", $rows)."\n");

        $this->post('/uploads', ['file' => $csv]);

        $upload = Upload::firstOrFail();
        $this->assertSame(1, $upload->skipped_rows);
        $this->assertSame(ProductStatus::Skipped, $upload->products()->where('row_number', 2)->first()->status);
    }
}
