<?php

namespace Amplify\System\Media\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait CustomErrorMessage
{
    /**
     * Validation error response
     */
    protected function failedValidation(Validator $validator)
    {
        $message = (method_exists($this, 'message'))
            ? $this->container->call([$this, 'message'])
            : 'The given data was invalid.';

        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
            'message' => $message,
        ], 422));
    }
}
