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
            'estimated_parts' => $this->resource->calculateEstimatedParts(),
            'estimated_cost' => (int)$this->resource->calculateEstimatedCost(),
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