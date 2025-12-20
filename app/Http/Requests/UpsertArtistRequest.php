<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertArtistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prn_artist_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'spotify' => ['nullable', 'string', 'max:64'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'twitter' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'homepage' => ['nullable', 'string', 'max:255'],
            'apple' => ['nullable', 'string', 'max:128'],
            'youtube' => ['nullable', 'string', 'max:128'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prn_artist_id.required' => 'PRN artist ID is required.',
            'prn_artist_id.integer' => 'PRN artist ID must be an integer.',
            'name.required' => 'Artist name is required.',
            'spotify.max' => 'Spotify artist ID must be 64 characters or less.',
        ];
    }
}
