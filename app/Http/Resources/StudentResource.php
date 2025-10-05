<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
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
            'registration_date' => $this->registration_date?->format('Y-m-d'),
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
            'payments' => $this->whenLoaded('payments'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
