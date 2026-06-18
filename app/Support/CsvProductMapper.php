<?php

namespace App\Support;

/**
 * Single source of truth for translating a Shopify-style product CSV into
 * `products` table attributes, and for validating each row.
 */
class CsvProductMapper
{
    /** Columns the CSV header MUST contain for the file to be importable. */
    public const REQUIRED_COLUMNS = ['Handle', 'Title', 'Variant SKU', 'Variant Price'];

    /** Allowed values for constrained fields. */
    public const WEIGHT_UNITS = ['kg', 'g', 'lb', 'oz'];

    public const INVENTORY_POLICIES = ['deny', 'continue'];

    /** CSV header => products column. */
    public const HEADER_MAP = [
        'Handle' => 'handle',
        'Title' => 'title',
        'Body HTML' => 'body_html',
        'Vendor' => 'vendor',
        'Product Type' => 'product_type',
        'Tags' => 'tags',
        'Published' => 'published',
        'Variant SKU' => 'sku',
        'Variant Price' => 'price',
        'Variant Compare At Price' => 'compare_at_price',
        'Variant Requires Shipping' => 'requires_shipping',
        'Variant Taxable' => 'taxable',
        'Variant Inventory Tracker' => 'inventory_tracker',
        'Variant Inventory Qty' => 'inventory_qty',
        'Variant Inventory Policy' => 'inventory_policy',
        'Variant Fulfillment Service' => 'fulfillment_service',
        'Variant Weight' => 'weight',
        'Variant Weight Unit' => 'weight_unit',
        'Image Src' => 'image_src',
        'Image Position' => 'image_position',
        'Image Alt Text' => 'image_alt_text',
    ];

    /**
     * Return the list of required columns missing from the given header row.
     *
     * @param  array<int, string>  $header
     * @return array<int, string>
     */
    public static function missingColumns(array $header): array
    {
        $normalized = array_map(fn ($h) => trim((string) $h), $header);

        return array_values(array_diff(self::REQUIRED_COLUMNS, $normalized));
    }

    /**
     * Map a CSV record (header => value) to typed `products` attributes.
     *
     * @param  array<string, string|null>  $record
     * @return array<string, mixed>
     */
    public function map(array $record): array
    {
        $get = fn (string $col) => isset($record[$col]) ? trim((string) $record[$col]) : null;

        return [
            'handle' => $get('Handle') ?: null,
            'title' => $get('Title') ?: null,
            'body_html' => $get('Body HTML') ?: null,
            'vendor' => $get('Vendor') ?: null,
            'product_type' => $get('Product Type') ?: null,
            'tags' => $get('Tags') ?: null,
            'published' => $this->toBool($get('Published'), true),
            'sku' => $get('Variant SKU') ?: null,
            'price' => $this->toDecimal($get('Variant Price')),
            'compare_at_price' => $this->toDecimal($get('Variant Compare At Price')),
            'requires_shipping' => $this->toBool($get('Variant Requires Shipping'), true),
            'taxable' => $this->toBool($get('Variant Taxable'), true),
            'inventory_tracker' => $get('Variant Inventory Tracker') ?: null,
            'inventory_qty' => $this->toInt($get('Variant Inventory Qty')),
            'inventory_policy' => $this->normalizeLower($get('Variant Inventory Policy')),
            'fulfillment_service' => $get('Variant Fulfillment Service') ?: null,
            'weight' => $this->toDecimal($get('Variant Weight')),
            'weight_unit' => $this->normalizeLower($get('Variant Weight Unit')),
            'image_src' => $get('Image Src') ?: null,
            'image_position' => $this->toInt($get('Image Position')),
            'image_alt_text' => $get('Image Alt Text') ?: null,
        ];
    }

    /**
     * Validate a CSV record. Returns a list of human-readable error messages
     * (empty array means the row is valid).
     *
     * @param  array<string, string|null>  $record
     * @return array<int, string>
     */
    public function validate(array $record): array
    {
        $errors = [];
        $get = fn (string $col) => isset($record[$col]) ? trim((string) $record[$col]) : '';

        if ($get('Handle') === '') {
            $errors[] = 'Handle is required.';
        }
        if ($get('Title') === '') {
            $errors[] = 'Title is required.';
        }
        if ($get('Variant SKU') === '') {
            $errors[] = 'Variant SKU is required.';
        }

        $price = $get('Variant Price');
        if ($price === '') {
            $errors[] = 'Variant Price is required.';
        } elseif (! is_numeric($price) || (float) $price < 0) {
            $errors[] = "Variant Price must be a number ≥ 0 (got \"{$price}\").";
        }

        $compare = $get('Variant Compare At Price');
        if ($compare !== '' && (! is_numeric($compare) || (float) $compare < 0)) {
            $errors[] = "Variant Compare At Price must be a number ≥ 0 (got \"{$compare}\").";
        }

        $qty = $get('Variant Inventory Qty');
        if ($qty !== '' && (! ctype_digit(ltrim($qty, '-')) || (int) $qty < 0)) {
            $errors[] = "Variant Inventory Qty must be a whole number ≥ 0 (got \"{$qty}\").";
        }

        $weight = $get('Variant Weight');
        if ($weight !== '' && (! is_numeric($weight) || (float) $weight < 0)) {
            $errors[] = "Variant Weight must be a number ≥ 0 (got \"{$weight}\").";
        }

        $unit = strtolower($get('Variant Weight Unit'));
        if ($unit !== '' && ! in_array($unit, self::WEIGHT_UNITS, true)) {
            $errors[] = 'Variant Weight Unit must be one of: '.implode(', ', self::WEIGHT_UNITS).".";
        }

        $policy = strtolower($get('Variant Inventory Policy'));
        if ($policy !== '' && ! in_array($policy, self::INVENTORY_POLICIES, true)) {
            $errors[] = 'Variant Inventory Policy must be one of: '.implode(', ', self::INVENTORY_POLICIES).".";
        }

        $image = $get('Image Src');
        if ($image !== '' && ! filter_var($image, FILTER_VALIDATE_URL)) {
            $errors[] = 'Image Src must be a valid URL.';
        }

        return $errors;
    }

    private function toBool(?string $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['true', '1', 'yes', 'y'], true);
    }

    private function toDecimal(?string $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function toInt(?string $value): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return 0;
        }

        return (int) $value;
    }

    private function normalizeLower(?string $value): ?string
    {
        return ($value === null || $value === '') ? null : strtolower($value);
    }
}
