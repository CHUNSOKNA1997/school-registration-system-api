<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
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
            'class_code' => $this->class_code,
            'name' => $this->name,
            'name_khmer' => $this->name_khmer,
            'full_name' => $this->full_name,
            'grade_level' => $this->grade_level,
            'section' => $this->section,
            'academic_year' => $this->academic_year,
            'shift' => $this->shift,
            'room_number' => $this->room_number,
            'capacity' => $this->capacity,
            'current_students_count' => $this->current_students_count,
            'available_slots' => $this->available_slots,
            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher'),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'students' => $this->whenLoaded('students'),
            'subjects' => $this->whenLoaded('subjects'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
