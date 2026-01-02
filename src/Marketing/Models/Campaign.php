<?php

namespace Amplify\System\Marketing\Models;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Cms\Models\Page;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Campaign extends Model implements Auditable
{
    use CrudTrait;
    use \OwenIt\Auditing\Auditable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'campaigns';

    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];

    // protected $fillable = [];
    // protected $hidden = [];
    protected $casts = [
        'status' => 'bool',
        'login_required' => 'bool',
    ];

    const DISCOUNT_TYPE = [
        'fixed_price' => 'Fixed price',
        'percentage_discount' => 'Percentage discount',
        'buy_n1_get_n2' => 'Buy n1 get n2',
    ];

    const AVAILABILITY = [
        'url' => 'URL',
        'code' => 'Code',
        'specific_customers' => 'Specific Customers',
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function fetchFromErp()
    {
        return "<a class='btn btn-info' href='".backpack_url('campaign/fetch-from-erp')."'>Fetch From ERP</a>";
    }

    public function syncProducts()
    {
        if ($this->source === 'ERP') {
            return "<a class='btn btn-sm btn-link' href='".backpack_url('campaign/sync-products', $this->code)."'>Sync Products</a>";
        }
    }

    public static function guessCurrentCampaign()
    {
        $campaign = request()->route('campaign_code');

        if (! $campaign) {
            abort(404, 'Campaign Title Parameter is missing');
        }

        return self::where('slug', $campaign)->first();
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, CampaignProduct::class)
            ->withPivot([
                'discount_type',
                'discount',
                'n1',
                'n2',
            ])
            ->withTimestamps();
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(\Amplify\System\Backend\Models\Customer::class, CampaignProduct::class)
            ->withTimestamps();
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function bannerZone(): BelongsTo
    {
        return $this->belongsTo(\Amplify\System\Cms\Models\BannerZone::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
