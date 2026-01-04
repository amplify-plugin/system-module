<?php

namespace Amplify\System\Marketing\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class MerchandisingZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3|max:255',
            'easyask_key' => 'required|min:3',
            'description' => 'required',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'The name field is required',
            'easyask_key.required' => 'The easyask key field is required',
            'description.required' => 'The description field is required',
            //            $rules['name.required'] = 'The product name field is required.'
        ];
    }
}
