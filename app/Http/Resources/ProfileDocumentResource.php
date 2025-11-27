<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_name' => $this->document_name,
            'verification_status' => $this->verification_status,
            'rejection_reason' => $this->rejection_reason,
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'verified_by' => $this->when($this->verifier, function () {
                return [
                    'id' => $this->verifier->id,
                    'name' => $this->verifier->full_name,
                ];
            }),
            'uploaded_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}