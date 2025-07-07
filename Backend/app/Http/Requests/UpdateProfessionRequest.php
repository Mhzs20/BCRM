<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateProfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        return $this->user()->can('update', $salon);
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
        ];
    }
}
