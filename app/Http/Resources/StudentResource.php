<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class StudentResource extends JsonResource
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
            'student_code' => $this->student_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'khmer_name' => $this->khmer_name,
            'date_of_birth' => $this->formatDate($this->date_of_birth),
            'date_of_birth_raw' => $this->formatRawDate($this->date_of_birth),
            'age' => $this->age,
            'place_of_birth' => $this->place_of_birth,
            'gender' => $this->gender,
            'student_type' => $this->student_type,
            'nationality' => $this->nationality,
            'phone' => $this->phone,
            'email' => $this->email,
            'current_address' => $this->current_address,
            'permanent_address' => $this->permanent_address,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'parent_occupation' => $this->parent_occupation,
            'emergency_contact' => $this->emergency_contact,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('class'),
            'shift' => $this->shift,
            'registration_date' => $this->formatDate($this->registration_date),
            'registration_date_raw' => $this->formatRawDate($this->registration_date),
            'academic_year' => $this->academic_year,
            'previous_school' => $this->previous_school,
            'photo' => $this->photo,
            'documents' => $this->documents,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'subjects' => $this->whenLoaded('subjects'),
            'enrollments' => $this->whenLoaded('enrollments'),
            'payments' => $this->whenLoaded('payments'),
            'created_at' => $this->formatDateTime($this->created_at),
            'created_at_raw' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->formatDateTime($this->updated_at),
            'updated_at_raw' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('M d, Y');
    }

    private function formatRawDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function formatDateTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('M d, Y h:i A');
    }
}
