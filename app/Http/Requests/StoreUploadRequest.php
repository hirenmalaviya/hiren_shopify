<?php

namespace App\Http\Requests;

use App\Support\CsvProductMapper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use League\Csv\Reader;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('shopify.import.max_file_size_kb', 5120);

        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'extensions:csv,txt',
                "max:{$maxKb}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a CSV file to upload.',
            'file.mimes' => 'The file must be a CSV (.csv) file.',
            'file.extensions' => 'The file must have a .csv extension.',
            'file.max' => 'The file may not be larger than '.config('shopify.import.max_file_size_kb', 5120).' KB.',
        ];
    }

    /**
     * After the basic file rules pass, confirm the CSV parses and has the
     * required header columns.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasFile('file') || ! $this->file('file')->isValid()) {
                return;
            }

            try {
                $reader = Reader::createFromPath($this->file('file')->getRealPath(), 'r');
                $reader->setHeaderOffset(0);
                $header = $reader->getHeader();
            } catch (\Throwable $e) {
                $validator->errors()->add('file', 'The file could not be read as a CSV.');

                return;
            }

            if (count($header) === 0) {
                $validator->errors()->add('file', 'The CSV file appears to be empty.');

                return;
            }

            $missing = CsvProductMapper::missingColumns($header);
            if (! empty($missing)) {
                $validator->errors()->add(
                    'file',
                    'The CSV is missing required column(s): '.implode(', ', $missing).'.'
                );
            }
        });
    }
}
