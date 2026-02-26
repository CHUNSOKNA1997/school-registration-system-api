<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TeacherResource extends JsonResource
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
            'teacher_code' => $this->teacher_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'khmer_name' => $this->khmer_name,
            'date_of_birth' => $this->formatDate($this->date_of_birth),
            'date_of_birth_raw' => $this->formatRawDate($this->date_of_birth),
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'phone' => $this->phone,
            'email' => $this->email,
            'current_address' => $this->current_address,
            'permanent_address' => $this->permanent_address,
            'qualification' => $this->qualification,
            'documents' => $this->documents,
            'subjects_count' => $this->subjects_count,
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'education_level' => $this->education_level,
            'specialization' => $this->specialization,
            'employment_type' => $this->employment_type,
            'hire_date' => $this->formatDate($this->hire_date),
            'hire_date_raw' => $this->formatRawDate($this->hire_date),
            'contract_end_date' => $this->formatDate($this->contract_end_date),
            'contract_end_date_raw' => $this->formatRawDate($this->contract_end_date),
            'salary' => $this->salary,
            'bank_account' => $this->bank_account,
            'id_card_number' => $this->id_card_number,
            'photo' => $this->photo,
            'cv' => $this->cv,
            'certificates' => $this->certificates,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'subjects' => $this->whenLoaded('subjects'),
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
