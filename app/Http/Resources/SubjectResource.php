<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
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
            'uuid' => $this->uuid,
            'subject_code' => $this->subject_code,
            'name' => $this->name,
            'name_khmer' => $this->name_khmer,
            'description' => $this->description,
            'grade_level' => $this->grade_level,
            'subject_type' => $this->subject_type,
            'credits' => $this->credits,
            'hours_per_week' => $this->hours_per_week,
            'fee' => $this->fee,
            'monthly_fee' => $this->monthly_fee,
            'syllabus' => $this->syllabus,
            'prerequisites' => $this->prerequisites,
            'is_active' => $this->is_active,
            'students' => $this->whenLoaded('students'),
            'teachers' => $this->whenLoaded('teachers'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
