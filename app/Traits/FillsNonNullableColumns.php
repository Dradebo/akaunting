<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait FillsNonNullableColumns
{
    /**
     * Fill non-nullable columns on a model instance with safe defaults based on PRAGMA table_info.
     * This is intended for test DBs (sqlite in-memory) where schema may have NOT NULL constraints
     * that are not relevant to minimal mobile flows.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $table
     * @param  mixed|null $company
     * @param  mixed|null $user
     * @return void
     */
    protected function fillNonNullableDefaults($model, string $table, $company = null, $user = null): void
    {
        try {
            $info = DB::select("PRAGMA table_info('" . $table . "')");
        } catch (\Throwable $e) {
            return;
        }

        foreach ($info as $col) {
            if (isset($col->notnull) && $col->notnull == 1) {
                $name = $col->name;

                // only fill if not already set (null). If attribute is missing or null, fill it.
                if (array_key_exists($name, $model->getAttributes()) && $model->{$name} !== null) {
                    continue;
                }

                $type = strtoupper($col->type ?? '');

                // common semantic fallbacks
                if (str_ends_with($name, '_id')) {
                    if ($name === 'company_id' && $company && isset($company->id)) {
                        $model->{$name} = $company->id;
                        continue;
                    }
                    if ($name === 'user_id' && $user && isset($user->id)) {
                        $model->{$name} = $user->id;
                        continue;
                    }
                    $model->{$name} = 1;
                    continue;
                }

                if ($name === 'currency_code') {
                    $model->{$name} = config('app.currency', 'UGX');
                    continue;
                }

                if (in_array($name, ['currency_rate', 'exchange_rate']) || str_contains($type, 'REAL') || str_contains($type, 'DECIMAL') || str_contains($type, 'NUM')) {
                    $model->{$name} = 1;
                    continue;
                }

                if (str_contains($type, 'INT')) {
                    $model->{$name} = 1;
                    continue;
                }

                if (str_contains($type, 'CHAR') || str_contains($type, 'CLOB') || str_contains($type, 'TEXT') || str_contains($type, 'VARCHAR')) {
                    // some text-like columns represent enums; use empty string as safe default
                    $model->{$name} = '';
                    continue;
                }

                if (str_contains($type, 'DATE') || str_contains($type, 'TIME')) {
                    $model->{$name} = now();
                    continue;
                }

                // fallback: set empty string to avoid NOT NULL
                $model->{$name} = '';
            }
        }
    }
}
