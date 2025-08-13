<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'name' => __('users.labels.name'),
            'email' => __('users.labels.email'),
            'password' => __('users.labels.password'),
            'password_confirmation' => __('users.labels.password_confirmation'),
            'role' => __('users.labels.role'),
        ];
    }

    protected function passedValidation(): void {
        if (empty($this->input('password'))) {
            $this->request->remove('password');
        }
    }
}
