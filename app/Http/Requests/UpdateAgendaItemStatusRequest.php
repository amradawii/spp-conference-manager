<?php

namespace App\Http\Requests;

use App\Enums\AssetsStatus;
use App\Enums\SpeakerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgendaItemStatusRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'speaker_status' => ['required_without:assets_status', Rule::enum(SpeakerStatus::class)],
            'assets_status' => ['required_without:speaker_status', Rule::enum(AssetsStatus::class)->only([AssetsStatus::Pending, AssetsStatus::Ready])],
        ];
    }
}
