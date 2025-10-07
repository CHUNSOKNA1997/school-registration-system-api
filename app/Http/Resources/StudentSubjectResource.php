<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentSubjectResource extends JsonResource
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
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'uuid' => $this->student->uuid,
                    'student_code' => $this->student->student_code,
                    'full_name' => $this->student->full_name,
                ];
            }),
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject', function () {
                return [
                    'id' => $this->subject->id,
                    'uuid' => $this->subject->uuid,
                    'subject_code' => $this->subject->subject_code,
                    'name' => $this->subject->name,
                    'name_khmer' => $this->subject->name_khmer,
                    'credits' => $this->subject->credits,
                ];
            }),
            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'uuid' => $this->teacher->uuid,
                    'teacher_code' => $this->teacher->teacher_code,
                    'full_name' => $this->teacher->first_name . ' ' . $this->teacher->last_name,
                ];
            }),
            'academic_year' => $this->academic_year,
            'enrolled_date' => $this->enrolled_date?->format('Y-m-d'),
            'completion_date' => $this->completion_date?->format('Y-m-d'),
            'score' => $this->score,
            'grade' => $this->grade,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
