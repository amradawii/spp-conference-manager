<?php

namespace App\Http\Requests;

use App\Enums\SessionKind;
use App\Http\Requests\Concerns\GuardsAgainstStaleEdits;
use App\Models\Conference;
use App\Models\ConferenceSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating (POST, needs day_id) and updating (PUT, needs expected_updated_at).
 */
class SaveConferenceSessionRequest extends FormRequest
{
    use GuardsAgainstStaleEdits;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'day_id' => [
                Rule::requiredIf($this->isCreating()),
                Rule::exists('days', 'id')->where('conference_id', $this->conference()->id),
            ],
            'kind' => ['required', Rule::enum(SessionKind::class)],
            'number' => ['nullable', 'integer', 'between:1,99', 'required_if:kind,session'],
            'journal' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255', 'required_without:journal'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'chair' => ['nullable', 'string', 'max:255'],
            'co_chair' => ['nullable', 'string', 'max:255'],
            'expected_updated_at' => [Rule::requiredIf(! $this->isCreating()), 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.required_if' => 'A numbered session needs its number.',
            'title.required_without' => 'Give it a journal or a title.',
            'ends_at.after' => 'The end time must be after the start time.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        $session = $this->route('session');

        return $session instanceof ConferenceSession
            ? [$this->staleEditGuard($session, $this->conference())]
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionAttributes(): array
    {
        $data = $this->safe()->except(['day_id', 'expected_updated_at']);

        if ($this->enum('kind', SessionKind::class) === SessionKind::Ceremony) {
            $data['number'] = null;
        }

        return $data;
    }

    private function isCreating(): bool
    {
        return $this->isMethod('post');
    }

    private function conference(): Conference
    {
        return $this->route('conference');
    }
}
