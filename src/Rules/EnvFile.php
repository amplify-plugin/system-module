<?php

namespace Amplify\System\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnvFile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $failed = false;

        $errors = [];

        $usedKeys = [];

        foreach (explode(PHP_EOL, $value) as $lineNumber => $line) {
            $trimmed = trim($line);

            // Skip comments and empty lines
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Check if line has "="
            if (!str_contains($trimmed, '=')) {
                $errors[] = "Line " . ($lineNumber+1) . ": Missing '=' → {$line}";
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);

            // Validate key
            if ($key === '') {
                $errors[] = "Line " . ($lineNumber+1) . ": Empty key → {$line}.";
            }

            if (!preg_match('/^[A-Z0-9_]+$/', $key)) {
                $errors[] = "Line " . ($lineNumber+1) . ": This ({$key} key may contains UPPERCASE letters, Numbers or Underscore(_) only.";
            }

            // Detect duplicate keys
            if (in_array($key, $usedKeys)) {
                $errors[] = "Line " . ($lineNumber+1) . ": This {$key} already defined previously.";
            } else {
                $usedKeys[] = $key;
            }

            // Check for unclosed quotes
            if (
                ((str_starts_with($value, '"') && !str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && !str_ends_with($value, "'")))
            ) {
                $errors[] = "Line " . ($lineNumber+1) . ": Unclosed quotes → {$line}";
            }
        }

        if (count($errors) > 0) {
            $fail(implode(PHP_EOL."→ ", $errors));
        }
    }
}
