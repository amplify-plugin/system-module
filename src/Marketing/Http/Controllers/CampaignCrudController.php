<?php

namespace Amplify\System\Marketing\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Abstracts\BackpackCustomCrudController;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Cms\Models\BannerZone;
use Amplify\System\Cms\Models\Page;
use Amplify\System\Marketing\Http\Request\CampaignRequest;
use Amplify\System\Marketing\Models\Campaign;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Prologue\Alerts\Facades\Alert;

/**
 * Class CampaignCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CampaignCrudController extends BackpackCustomCrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Campaign::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/campaign');
        CRUD::setEntityNameStrings('campaign', 'campaigns');
    }

    protected function setupCustomRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/fetch-from-erp', [
            'as' => $routeName.'.fetch-from-erp',
            'uses' => $controller.'@fetchCampaigns',
            'operation' => 'fetch-from-erp',
        ]);
        Route::get($segment.'/sync-products/{campaign_code}', [
            'as' => $routeName.'.sync-products',
            'uses' => $controller.'@syncProducts',
            'operation' => 'sync-products',
        ]);
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addButtonFromModelFunction('top', 'fetch-from-erp', 'fetchFromErp', 'end');
        CRUD::addButtonFromModelFunction('line', 'sync-products', 'syncProducts', 'start');

        CRUD::addColumn([
            'name' => 'code',
            'label' => 'Campaign Code',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => 'Campaign Name',
        ]);

        CRUD::addColumn([
            'name' => 'products',
            'type' => 'relationship_count',
            'label' => 'Total Product',
            'suffix' => ' Products',
        ]);

        CRUD::addColumn([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'datetime',
            'format' => 'l',
        ]);

        CRUD::addColumn([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'datetime',
            'format' => 'l',
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->status == 1 ? 'Enable' : 'Disable';
            },

        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        $this->data['pages'] = Page::where('page_type', 'campaign_details')->get();
        $this->data['banner_zones'] = BannerZone::all();
        $this->crud->setCreateContentClass('col-md-12 bold-labels');
        $this->crud->setCreateView('backend::pages.campaign.create');
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        $this->crud->setUpdateContentClass('col-md-12 bold-labels');
        $this->crud->setUpdateView('backend::pages.campaign.create');
    }

    /**
     *  Reorder the items in the database using the Nested Set pattern.
     *
     *  Database columns needed: id, parent_id, lft, rgt, depth, name/title
     *
     * @return Application|Factory|View
     */
    protected function setupShowOperation()
    {
        $this->crud->set('show.setFromDb', false);

        CRUD::addColumn([
            'name' => 'code',
            'label' => 'Campaign Code',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => 'Campaign Name',
        ]);

        CRUD::addColumn([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'datetime',
            'format' => 'l',
        ]);

        CRUD::addColumn([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'datetime',
            'format' => 'l',
        ]);
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->status == 1 ? 'Enable' : 'Disable';
            },

        ]);
        CRUD::addColumn([
            'name' => 'products',
            'label' => 'Products',
            'type' => 'table-related',
            'columns' => [
                [
                    'name' => 'product_id',
                    'label' => 'Product ID',
                    'type' => 'text',
                ],
                [
                    'name' => 'product_name',
                    'label' => 'Product Name',
                    'type' => 'text',
                ],
                [
                    'name' => 'discount',
                    'label' => 'Campaign Price',
                    'type' => 'closure',
                    'function' => function ($entry) {
                        $price = $entry->pivot?->discount;

                        return '$ '.number_format((float) $price, 2);
                    },
                ],
            ],
        ]);
    }

    public function store(CampaignRequest $request)
    {
        $this->crud->hasAccessOrFail('create');

        $products = $this->campaignProduct($request->input('campaign_products', []));

        $defaultCampaignPage = Page::wherePageType('campaign_details')->first();

        $campaign = new Campaign([
            'name' => $request->input('name', Str::random(20)),
            'slug' => $request->input('slug', Str::slug(Str::random(20))),
            'page_id' => $request->input('page_id', $defaultCampaignPage->getKey() ?? null),
            'banner_zone_id' => $request->input('banner_zone_id'),
            'code' => $request->input('code', Str::upper(Str::random(8))),
            'description' => $request->input('description'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->boolean('status', false),
            'login_required' => $request->boolean('login_required', false),
        ]);

        if ($campaign->save()) {
            if (! empty($products)) {
                $campaign->products()->attach($products);
            }
            Alert::success(trans('backpack::crud.insert_success'))->flash();

            return true;
        }

        Alert::error('Something went wrong!')->flash();

        return false;
    }

    public function update(CampaignRequest $request, Campaign $campaign): bool
    {
        $this->crud->hasAccessOrFail('update');

        $products = $this->campaignProduct($request->input('campaign_products', []));
        $inputs = [
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'page_id' => $request->input('page_id'),
            'banner_zone_id' => $request->input('banner_zone_id'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status'),
            'login_required' => $request->boolean('login_required', false),
        ];

        if ($campaign->update($inputs)) {
            if (! empty($products)) {
                $campaign->products()->sync($products);
            }
            Alert::success(trans('backpack::crud.update_success'))->flash();

            return true;
        }

        Alert::error('Something went wrong!')->flash();

        return false;
    }

    private function campaignProduct($campaign_products = [])
    {
        $products = [];
        foreach (($campaign_products ?? []) as $key => $product) {
            $products[$key] = [
                'product_id' => $product['product_id'],
                'discount_type' => $product['discount_type'],
                'discount' => $product['discount'],
            ];

            if ($product['discount_type'] == 'buy_n1_get_n2') {
                $products[$key]['n1'] = $product['n1'];
                $products[$key]['n2'] = $product['n2'];
            }
        }

        return $products;
    }

    public function fetchCampaigns()
    {
        $campaigns = ErpApi::getCampaignList(['override_date' => '10/23/2017']);

        foreach ($campaigns as $campaign) {
            Campaign::updateOrCreate([
                'code' => $campaign->Promoid,
            ], [
                'name' => $campaign->ShortDesc,
                'slug' => Str::slug($campaign->ShortDesc),
                'description' => $campaign->LongDesc,
                'start_date' => carbon_date($campaign->BegDate, 'Y-m-d'),
                'end_date' => carbon_date($campaign->EndDate, 'Y-m-d'),
                'source' => 'ERP',
                'status' => true,
            ]);
        }

        Alert::success('Successfully fetched campaigns from ERP.');

        return back();
    }

    public function syncProducts($campaign_code)
    {
        $products = [];
        $campaign = ErpApi::getCampaignDetail(['promo' => $campaign_code, 'override_date' => '10/23/2017']);
        $productCodes = $campaign->CampaignDetail->map(fn ($campaign) => $campaign->Item)->toArray();
        $dbProductDetails = Product::select(['product_code', 'id'])->whereIn('product_code', $productCodes)->get();

        foreach ($campaign->CampaignDetail as $campaignItem) {
            $product = $dbProductDetails->first(function ($item) use ($campaignItem) {
                return trim($item->product_code) == trim($campaignItem->Item);
            });

            if ($product) {
                $products[] = [
                    'product_id' => $product->id,
                    'discount_type' => 'fixed_price',
                    'discount' => $campaignItem->Price,
                ];
            }
        }

        Campaign::updateOrCreate([
            'code' => $campaign->Promoid,
        ], [
            'name' => $campaign->ShortDesc,
            'slug' => Str::slug($campaign->ShortDesc),
            'description' => $campaign->LongDesc,
            'start_date' => carbon_date($campaign->BegDate, 'Y-m-d'),
            'end_date' => carbon_date($campaign->EndDate, 'Y-m-d'),
            'source' => 'ERP',
            'status' => true,
        ])->products()->sync($products);

        Alert::success('Successfully fetched campaigns from ERP.');

        return back();
    }
}
