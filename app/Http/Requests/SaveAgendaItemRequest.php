<?php

namespace App\Http\Requests;

use App\Enums\AgendaItemType;
use App\Enums\AssetsStatus;
use App\Http\Requests\Concerns\GuardsAgainstStaleEdits;
use App\Models\AgendaItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating (POST) and updating (PUT, needs expected_updated_at).
 * Statuses are not edited here; they have their own one-click endpoint.
 */
class SaveAgendaItemRequest extends FormRequest
{
    use GuardsAgainstStaleEdits;

    protected function prepareForValidation(): void
    {
        $emails = collect((array) $this->input('emails', []))
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->values()
            ->all();

        $this->merge(['emails' => $emails]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSegment = $this->input('type') === AgendaItemType::Segment->value;

        return [
            'type' => ['required', Rule::enum(AgendaItemType::class)],
            'label' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date_format:H:i', 'required_with:ends_at'],
            'ends_at' => ['nullable', 'date_format:H:i', 'required_with:starts_at', 'after:starts_at'],
            'speaker_name' => [Rule::requiredIf(! $isSegment), 'nullable', 'string', 'max:255'],
            'speaker_role' => ['nullable', 'string', 'max:255'],
            'affiliation' => ['nullable', 'string', 'max:1000'],
            'emails' => ['array', 'max:5'],
            'emails.*' => ['email', 'max:255'],
            'title' => [Rule::requiredIf($isSegment), 'nullable', 'string', 'max:2000'],
            'expected_updated_at' => [Rule::requiredIf($this->isMethod('put')), 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'speaker_name.required' => 'Add the speaker\'s name.',
            'title.required' => 'Describe this part of the programme.',
            'ends_at.after' => 'The end time must be after the start time.',
            'emails.*.email' => 'This is not a valid email address.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        $item = $this->route('item');

        return $item instanceof AgendaItem
            ? [$this->staleEditGuard($item, $this->route('conference'))]
            : [];
    }

    /**
     * Segments carry no speaker; speakers always track assets.
     *
     * @return array<string, mixed>
     */
    public function itemAttributes(?AgendaItem $existing = null): array
    {
        $data = $this->safe()->except(['expected_updated_at']);
        $type = AgendaItemType::from($data['type']);

        if (! $type->hasSpeaker()) {
            return [
                ...$data,
                'speaker_name' => null,
                'speaker_role' => null,
                'affiliation' => null,
                'emails' => null,
                'assets_status' => AssetsStatus::NotNeeded,
            ];
        }

        $assets = $existing?->assets_status;

        return [
            ...$data,
            'emails' => $data['emails'] ?: null,
            'assets_status' => $assets === null || $assets === AssetsStatus::NotNeeded ? AssetsStatus::Pending : $assets,
        ];
    }
}
