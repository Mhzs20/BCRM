<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RenewalReminderTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'template' => $this->template,
            'estimated_parts' => ceil(mb_strlen($this->template) / 70),
            'estimated_cost' => ceil(mb_strlen($this->template) / 70) * 100, // فرضی - 100 تومان هر قطعه
            'variables' => $this->extractVariables($this->template),
        ];
    }

    /**
     * استخراج متغیرهای موجود در قالب
     */
    private function extractVariables(string $template): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        return array_unique($matches[1] ?? []);
    }
}