<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        render($view, $data);
    }

    protected function redirect(string $routeName, array $params = []): array
    {
        return redirect_to($routeName, $params);
    }

    protected function validationRedirect(string $routeName, array $errors, array $input = [], array $params = []): array
    {
        set_validation_errors($errors);
        set_old_input($input);
        flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
        return $this->redirect($routeName, $params);
    }

    protected function validate(array $input, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $input[$field] ?? null;
            $ruleSet = is_array($ruleSet) ? $ruleSet : explode('|', (string) $ruleSet);

            foreach ($ruleSet as $rule) {
                $name = $rule;
                $parameter = null;

                if (str_contains((string) $rule, ':')) {
                    [$name, $parameter] = explode(':', (string) $rule, 2);
                }

                $stringValue = trim((string) $value);

                if ($name === 'required' && $stringValue === '') {
                    $errors[$field] = __('validation.required', 'This field is required.');
                    break;
                }

                if ($stringValue === '') {
                    continue;
                }

                if ($name === 'email' && filter_var($stringValue, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[$field] = __('validation.email', 'Enter a valid email address.');
                    break;
                }

                if ($name === 'numeric' && !preg_match('/^[0-9]+$/', $stringValue)) {
                    $errors[$field] = __('validation.numeric', 'Enter numbers only.');
                    break;
                }

                if ($name === 'min' && mb_strlen($stringValue) < (int) $parameter) {
                    $errors[$field] = __('validation.min', 'This field is too short.');
                    break;
                }

                if ($name === 'in' && $parameter !== null) {
                    $options = explode(',', $parameter);
                    if (!in_array($stringValue, $options, true)) {
                        $errors[$field] = __('validation.invalid_choice', 'Select a valid option.');
                        break;
                    }
                }
            }
        }

        return $errors;
    }
}
