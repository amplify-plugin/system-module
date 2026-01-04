<?php

namespace Amplify\System\Marketing\Http\Request;

use Amplify\System\Marketing\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
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
        $campaign = request()->campaign;

        return [
            'name' => 'required|max:255',
            'slug' => 'required|max:255|unique:campaigns,slug,'.$campaign?->id,
            'code' => 'required|max:255|unique:campaigns,code,'.$campaign?->id,
            'banner_zone_id' => 'required',
            'page_id' => 'required',
            'description' => 'nullable',
            'start_date' => 'required|date',
            'end_date' => 'date|after_or_equal:start_date',
            'campaign_products' => 'nullable|array',
            'campaign_products.*.product_id' => 'required|integer',
            'campaign_products.*.discount_type' => 'required|in:'.implode(',', array_keys(Campaign::DISCOUNT_TYPE)),
            'campaign_products.*.discount' => 'required|numeric',
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
            //
        ];
    }
}
