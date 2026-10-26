<?php

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Cms\Models\Page;
use Amplify\System\Facades\AssetsFacade;
use Amplify\System\Sayt\Classes\ItemRow;
use Amplify\System\Support\AssetsLoader;
use Amplify\System\Support\Money;
use Amplify\System\Utility\Models\DataTransformation;
use Amplify\System\Utility\Models\ImportJobHistory;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */

defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR') || define('HOUR', 3600);
defined('DAY') || define('DAY', 86400);
defined('WEEK') || define('WEEK', 604800);
defined('MONTH') || define('MONTH', 2_592_000);
defined('YEAR') || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);
defined('PASSWORD_MIN_LEN') || define('PASSWORD_MIN_LEN', 6);

/*
 |--------------------------------------------------------------------------
 | Helper Functions
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 |
 */

if (! function_exists('external_asset')) {
    function external_asset($url): string
    {
        return str_replace('http://', '//', $url);
    }
}

if (! function_exists('array_unset_recursive')) {
    function array_unset_recursive(&$array, $remove)
    {
        $remove = (array) $remove;
        foreach ($array as $key => &$value) {
            if (in_array($key, $remove) && $value == []) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                array_unset_recursive($value, $remove);
            }
        }
    }
}

if (! function_exists('array_rename_recursive')) {
    function array_rename_recursive(&$array, $target, $rename)
    {
        foreach ($array as $key => &$value) {
            if ($key === $target) {
                $array[$rename] = $array[$key];
                unset($array[$key]);
            } elseif (is_array($value)) {
                array_rename_recursive($value, $target, $rename);
            }
        }
    }
}

if (! function_exists('is_super_admin')) {
    function is_super_admin($role = ['Super Admin']): bool
    {
        return backpack_auth()->check() && backpack_user()->hasRole($role);
    }
}

if (! function_exists('listOfCrudToHideBackToButton')) {
    function listOfCrudToHideBackToButton(): array
    {
        return [
            'searches',
            'site pricings',
            'core configurations',
        ];
    }
}

if (! function_exists('getColumnListing')) {
    function getColumnListing($table, $withoutTimestamps = false, $except = [], $ignoreId = false): array
    {
        $columns = DB::select("DESCRIBE `{$table}`;");
        $defaultExcepts = $withoutTimestamps
                ? ['user_id', 'has_sku', 'is_new', 'is_updated', 'created_at', 'updated_at', 'deleted_at', '`values`']
                : [];

        $fields = [];
        foreach ($columns as $column) {
            $fields[] = [
                'name' => $column->Field,
                'type' => $column->Type,
                'is_required' => strtolower($column->Null) == 'no' && $column->Default == null && ! ($column->Extra == 'auto_increment' && $ignoreId),
            ];
        }

        $except = $except
                ? array_unique(array_merge($defaultExcepts, $except))
                : $defaultExcepts;

        return collect($fields)
            ->map(function ($field) use ($except) {
                return in_array($field['name'], $except)
                        ? false
                        : $field;
            })
            ->reject(function ($rej) {
                return $rej == false;
            })
            ->toArray();
    }
}

if (! function_exists('getTotalPriceOfItem')) {
    function getTotalPriceOfItem($price, $qty)
    {
        $price = preg_replace('/[,$]/', '', $price);

        return $price * $qty;
    }
}

if (! function_exists('getPriceOfItem')) {
    function getPriceOfItem($price, $qty)
    {
        $price = preg_replace('/[,$]/', '', $price);

        return $price * $qty;
    }
}

if (! function_exists('getAllAttributes')) {
    function getAllAttributes(): array
    {
        $attributes = Attribute::query()->get(['name']);

        return collect($attributes)
            ->map(function ($attribute) {
                return $attribute->name;
            })
            ->toArray();
    }
}

if (! function_exists('getModelNames')) {
    function getModelNames($files = null)
    {
        $files = $files ?? app_path('Models').'/*.php';
        $models = collect(glob($files))->map(fn ($file) => basename($file, '.php'))->toArray();

        return array_combine($models, $models);
    }
}

if (! function_exists('getCategorySlug')) {
    function getCategorySlug($slug, $id = null)
    {
        $where = ! empty($id)
                ? [['category_slug', 'like', '%'.$slug.'%'], ['id', '!=', $id]]
                : [['category_slug', 'like', '%'.$slug.'%']];

        $count = Category::query()->where($where)->count();

        return $count > 0
                ? $slug.'-'.$count
                : $slug;
    }
}

if (! function_exists('getProductSlug')) {
    function getProductSlug($slug, $id = null)
    {
        $where = ! empty($id)
                ? [['product_slug', 'like', '%'.$slug.'%'], ['id', '!=', $id]]
                : [['product_slug', 'like', '%'.$slug.'%']];

        $count = Product::query()->where($where)->count();

        return $count > 0
                ? $slug.'-'.$count
                : $slug;
    }
}

if (! function_exists('getPageSlug')) {
    function getPageSlug($slug, $id = null)
    {
        $where = $id
                ? [['slug', 'LIKE', '%'.$slug.'%'], ['id', '!=', $id]]
                : ['slug' => $slug];

        $count = Page::query()->where($where)->count();

        return $count
                ? "$slug-$count"
                : $slug;
    }
}

if (! function_exists('manageImportJobHistory')) {
    function manageImportJobHistory(
        string $uuid,
        int $importJobId,
        bool $isFinalJob = false,
        string $action = 'create',
        string $status = 'failed'
    ) {
        $importJobHistory = ImportJobHistory::query();

        switch ($action) {
            case 'create':
            case 'update':
                return $importJobHistory->updateOrCreate([
                    'uuid' => $uuid,
                    'import_job_id' => $importJobId,
                ], [
                    'status' => $status,
                    'is_final_job' => $isFinalJob,
                ]);
            case 'delete':
                return $importJobHistory->where([
                    'uuid' => $uuid,
                    'import_job_id' => $importJobId,
                    'status' => $status,
                ])->delete();
            default:
                return null;
        }
    }
}

if (! function_exists('getDBTypeValues')) {
    function getDBTypeValues(string $table, string $column, string $columnType): array
    {
        $typeString = DB::select(DB::raw("SHOW COLUMNS FROM $table WHERE Field = '$column'"))[0]->Type ?? '';

        switch ($columnType) {
            case 'enum':
                $typeString = Str::replace('\'', '', $typeString);
                $length = Str::length($typeString);
                $str = Str::substr($typeString, 5, ($length - 6));
                $value = explode(',', $str);
                break;
            default:
                $value = [];
                break;
        }

        return $value;
    }
}

if (! function_exists('is_auto_publish')) {
    function is_auto_publish(): bool
    {
        return config('amplify.pim.auto_publish', false);
    }
}

if (! function_exists('getHierarchies')) {
    /**
     * @return array
     */
    function getHierarchies()
    {
        return config('amplify.basic.hierarchies', []);
    }
}

if (! function_exists('getDefaultDateTimeFormat')) {
    function getDefaultDateTimeFormat(): string
    {
        return config('amplify.basic.date_time_format');
    }
}

if (! function_exists('getDefault')) {
    function getDefault(string $case): ?string
    {
        switch ($case) {
            case 'DateTimeFormat':
            case 'dateTimeFormat':
            case 'date_time_format':
                $response = getDefaultDateTimeFormat();
                break;
            default:
                $response = null;
                break;
        }

        return $response;
    }
}

if (! function_exists('getFileData')) {
    function getFileData($toCollection, $filePath, $readerType, string $disc = 'local'): Collection
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '300');

        $fileData = \Maatwebsite\Excel\Facades\Excel::toCollection($toCollection, $filePath, $disc, $readerType);

        return collect($fileData->first());
    }
}

if (! function_exists('getFileDataCount')) {
    function getFileDataCount($toCollection, $filePath, $readerType, $hasColumnHeading, string $disc = 'local'): int
    {
        $fileData = getFileData($toCollection, $filePath, $readerType, $disc);

        if ($hasColumnHeading) {
            $totalRow = $fileData->count() - 1;
        } else {
            $totalRow = $fileData->count();
        }

        return $totalRow;
    }
}

if (! function_exists('readFileFromLocal')) {
    function readFileFromLocal(
        $toCollection,
        $filePath,
        $readerType,
        bool $hasColumnHeading = false,
        string $disc = 'local',
        $take = null,
        array $compact = ['csvArray', 'headerRows', 'totalRow']
    ): array {
        $fileData = getFileData($toCollection, $filePath, $readerType, $disc);

        if ($hasColumnHeading) {
            $totalRow = $fileData->count() - 1;

            // Get file data in array
            $fileData = ($take
                    ? $fileData->take(11)
                    : $fileData)->toArray();
            $headerRows = $fileData[0];
            unset($fileData[0]);
        } else {
            $totalRow = $fileData->count();

            // Get file data in array
            $fileData = ($take
                    ? $fileData->take(10)
                    : $fileData)->toArray();
            $number_of_header_rows = count($fileData[0]);
            $headerData = [];
            for ($x = 1; $x <= $number_of_header_rows; $x++) {
                $headerData[] = 'Column '.$x;
            }
            $headerRows = $headerData;
        }
        $csvArray = array_values($fileData);

        return compact(...$compact);
    }
}

if (! function_exists('getFileNameFromPath')) {
    /**
     * @return false|mixed|string
     */
    function getFileNameFromPath($filePath)
    {
        $fileName = explode('/', $filePath);

        return end($fileName);
    }
}

if (! function_exists('removeExtension')) {
    function removeExtension(string $fileName): string
    {
        $fileName = explode('.', $fileName);

        return $fileName[0];
    }
}

if (! function_exists('getFileExtension')) {
    function getFileExtension(string $fileName): string
    {
        $fileName = explode('.', $fileName);

        return end($fileName);
    }
}

if (! function_exists('getFileName')) {
    function getFileName($filePath): string
    {
        $fileName = getFileNameFromPath($filePath);

        return removeExtension($fileName);
    }
}

if (! function_exists('removeFileNameFromPath')) {
    function removeFileNameFromPath($filePath): string
    {
        $fileName = getFileNameFromPath($filePath);

        return Str::replace($fileName, '', $filePath);
    }
}

// if required length is less than the actual length then repeat the string and return the required length
if (! function_exists('repeatString')) {
    function repeatString(string $string, bool $toLower = false, int $length = 6): string
    {
        if ($toLower) {
            $string = Str::lower($string);
        }

        $string = Str::repeat($string, ceil($length / strlen($string)));

        return Str::substr($string, 0, $length);
    }
}

// get files from Storage public disc directory with or without extension string
if (! function_exists('getFilesFromStorage')) {
    function getFilesFromStorage(string $directory, string $extension = ''): array
    {
        $files = Storage::disk('public')->files($directory);
        if ($extension) {
            $files = array_filter($files, function ($file) use ($extension) {
                return Str::endsWith($file, $extension);
            });
        }

        return $files;
    }
}

if (! function_exists('deleteFileFromPublicFolder')) {
    function deleteFileFromPublicFolder(string $filePath): void
    {
        $path = public_path($filePath);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

// Get default limit for reorder
if (! function_exists('getDefaultReorderLimit')) {
    /**
     * @return int|mixed
     */
    function getDefaultReorderLimit()
    {
        return config('amplify.basic.default_reorder_limit', 100);
    }
}

// get full locale name
if (! function_exists('getFullLocaleName')) {
    function getFullLocaleName(string $locale): string
    {
        return config('backpack.crud.available_locales')[$locale] ?? $locale;
    }
}

// get download anchor tag
if (! function_exists('getDownloadAnchorTag')) {
    function getDownloadAnchorTag(
        string $filePath,
        ?string $fileName = null,
        string $title = 'Click To Download'
    ): string {
        $fileName = $fileName ?? getFileNameFromPath($filePath);
        $fileExists = File::exists($filePath);

        return '<a '.($fileExists
                        ? 'class=\'text-success\' title=\''.$title.'\' href=\''
                        .asset($filePath).'\'> <i class="la la-download mr-1"></i>'
                        : 'class=\'text-danger\' title=\'File Does Not Exists\' href=\'#\'>')
                ."$fileName</a>";
    }
}

if (! function_exists('getFileDetails')) {
    function getFileDetails($type, $product_code = null, $key = null, $fileExt = null)
    {
        $folderPath = config('filesystems.disks.uploads.folder_name') ? config(
            'filesystems.disks.uploads.folder_name'
        ).'/' : '';

        $pathUrl = config('filesystems.disks.uploads.url');
        if (substr($pathUrl, -1) != '/') {
            $pathUrl = $pathUrl.'/';
        }

        if (! $fileExt) {
            $fileExt = ! empty($key) ? "-{$key}.jpg" : '.jpg';
        }

        switch ($type) {
            case 'product_image':
                $file_path = $folderPath.'images/products/'.strtolower($product_code[0]).'/';
                $file_url = $pathUrl.$file_path.$product_code.$fileExt;

                return ['file_path' => $file_path, 'file_url' => $file_url];

            case 'brand_image':
                $file_path = $folderPath.'images/brands/';
                $file_url = $pathUrl.$file_path.$product_code.$fileExt;

                return ['file_path' => $file_path, 'file_url' => $file_url];

            case 'product_document':
                $file_path = $folderPath.'icecat_docs/'.strtolower($product_code[0]).'/';
                $file_url = config(
                    'filesystems.disks.uploads.url'
                ).$file_path.$product_code.'-'.$key.'.'.$fileExt;

                return ['file_path' => $file_path, 'file_url' => $file_url];
            default:
                return ['file_path' => '', 'file_url' => ''];
        }
    }
}

if (! function_exists('getDataTransformations')) {
    /**
     * @return bool|\Illuminate\Database\Eloquent\Builder[]|Illuminate\Database\Eloquent\Collection
     */
    function getDataTransformations(
        string $appliesTo = 'Products',
        array $inCategories = [],
        ?string $runWhen = null,
        string $responseType = 'array'
    ) {
        /* Getting applies to */
        $appliesTo = DataTransformation::APPLIES_TO[$appliesTo];

        /* Getting all data transformations */
        $dataTransformations = DataTransformation::query()
            ->where('run_when', 'like', '%'.ucwords(strtolower($runWhen)).'%')
            ->where('applies_to', 'like', $appliesTo)
            ->orderBy('execution_sequence', 'asc')
            ->get()
            ->filter(function ($dataTransformation) use ($inCategories) {
                return count(
                    array_intersect(
                        json_decode($dataTransformation->in_category, false, 512, JSON_THROW_ON_ERROR),
                        $inCategories
                    )
                ) > 0;
            });

        return $responseType === 'boolean'
                ? $dataTransformations->count() > 0
                : $dataTransformations;
    }
}

if (! function_exists('getCategoryWithSubCategories')) {
    function getCategoryWithSubCategories($category_seopath)
    {
        $subCategories = Sayt::getSubCategoriesByCategory($category_seopath);

        return ! empty($subCategories) ? $subCategories : new stdClass;
    }
}

if (! function_exists('error_layout')) {
    /**
     * return the layout to extend on error display page
     *
     * @return string
     */
    function error_layout()
    {
        return (request()->is(config('backpack.base.route_prefix').'/*') || request()->is('sales/*'))
                ? 'errors.layout'
                : theme_view('error');
    }
}

if (! function_exists('returnProductSlug')) {
    function returnProductSlug($product): string
    {
        if (gettype($product) == 'string') {
            return $product;
        }

        $column = config('amplify.frontend.easyask_single_product_index');

        if ($product instanceof ItemRow) {
            return match ($column) {
                'product_code' => $product->Product_Code,
                'product_slug' => $product->Product_Slug,
                default => $product->Amplify_Id ?? $product->Product_Id,
            };
        }

        if (! empty($column) && ! empty($product->{$column})) {
            return $product->{$column};
        }

        return '';
    }
}

if (!function_exists('product_code_url_map')) {
    function product_code_url_map(string $product_code, bool $decode = false): string
    {
        $replacements = [
                '/' => '~',
                ' ' => '%20'
        ];

        if ($decode) {
            $replacements = array_flip($replacements);
        }

        return strtr($product_code, $replacements);
    }
}

if (! function_exists('frontendSingleProductURL')) {
    function frontendSingleProductURL($product, $seo_path = null): string
    {
        $skuProductCode = false;

        if ($product instanceof Product && config('amplify.frontend.show_parent_product_for_sku', true) && $product->parent_id) {
            $skuProductCode = $product->product_code;
            $product = Product::find($product->parent_id) ?? $product;
        }

        $identifier = returnProductSlug($product);

        if (config('amplify.frontend.easyask_single_product_index') == 'product_code') {
            $identifier = product_code_url_map($identifier);
        }

        $params['identifier'] = $identifier;

        if (config('amplify.frontend.easyask_single_product_index') != 'product_slug') {
            $params['slug'] = $product instanceof ItemRow ? $product->Product_Slug : $product->product_slug;
        }

        if (! empty($seo_path)) {
            $params['ref'] = $seo_path;
        }else if(!empty($skuProductCode)){
            $params['ref'] = '-'.$skuProductCode;
        }

        return (! empty($params['identifier']))
                ? \route('frontend.shop.show', $params)
                : '#';
    }
}

if (! function_exists('frontendShopURL')) {
    function frontendShopURL($params = null): string
    {
        $shopRoutePrefix = config('amplify.frontend.shop_page_prefix');

        $params = \Illuminate\Support\Arr::wrap($params);

        return route('frontend.shop.index', $params);
    }
}

if (! function_exists('frontendHomeURL')) {
    function frontendHomeURL(): string
    {
        return route('frontend.index');
    }
}

if (! function_exists('getProductsByIds')) {
    function getProductsByIds($productIDs)
    {
        $EASPConnection = Sayt::getEASetup();
        $config = eaShopConfig();
        $searchString = $config->ProductDetailSearch->Fieldname.' = '.$productIDs;
        $EASPresults = $EASPConnection->userSearch('', $searchString);

        if ($EASPresults->getTotalItems() > 0) {
            $products = $EASPresults->getProducts();

            $products = collect($products->items)->map(function ($product) {
                $product->isSkuProduct = isset($product->Sku_Id) ? true : false;
                $product->seopath = '-'.trim(
                    config('amplify.sayt.product_search_by_id_prefix')
                ).'-=-'.$product->Product_Id;

                return $product;
            });

            return $products;
        }

        return null;
    }
}

if (! function_exists('getFormattedIdsForEasyAskProducts')) {
    function getFormattedIdsForEasyAskProducts($products)
    {
        $ids = '';

        $length = count($products);
        foreach ($products as $key => $product) {
            if ($length > 1) {
                $ids .= $key == $length - 1 ?
                        preg_replace('/\D+/', '', optional($product)->product_id)
                        : preg_replace('/\D+/', '', optional($product)->product_id).' or ';
            } else {
                $ids = preg_replace('/\D+/', '', optional($product)->product_id);
            }
        }

        return $ids;
    }
}

if (! function_exists('getPaginationLengths')) {
    function getPaginationLengths(): array
    {
        $options = config('amplify.basic.length_options', '10,25,50,100,500');

        $options = trim(trim($options), ',');

        $lengthOptions = explode(',', $options);

        return array_map(function ($item) {
            return (int) $item;
        }, $lengthOptions);
    }
}

if (! function_exists('customerCartUrl')) {
    function customerCartUrl(): string
    {
        // if found in cache return it
        $cartSlug = Cache::get('customerCartUrl');
        if (! empty($cartSlug)) {
            return route('dynamic-page', $cartSlug);
        }

        $loginPage = Page::where('page_type', 'cart_page')->first();
        if (! empty($loginPage)) {
            Cache::add('customerCartUrl', $loginPage->slug, now()->addMonths(1));

            return route('dynamic-page', $loginPage->slug);
        } else {
            return '#';
        }
    }
}

if (! function_exists('getCart')) {
    function getCart(): ?Cart
    {
        if (customer_check()) {
            return Cart::firstOrCreate(['contact_id' => customer(true)->id, 'status' => true]);
        }

        if (config('amplify.frontend.guest_add_to_cart')) {
            return Cart::firstOrCreate(['session_id' => session()->token(), 'status' => true]);
        }

        return null;
    }
}

if (! function_exists('fileUploads')) {
    function fileUploads(?UploadedFile $file = null, $dir = '/')
    {
        if (empty($file)) {
            return '/';
        }
        // 1. Generate a filename.
        $filename = $file->getClientOriginalName().'.'.$file->getClientOriginalExtension();
        $path = Storage::disk('uploads')->put($dir, $file);
        $path = Storage::disk('uploads')->url($path);

        return $path;
    }
}

if (! function_exists('get_orders')) {
    function get_orders($type)
    {
        $now = Carbon::now();

        if ($type === 'today') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($type === 'this_week') {
            $weekStartsAt = (int) config('amplify.basic.first_day_of_week', Carbon::SUNDAY);
            $weekEndsAt = ($weekStartsAt + 6) % 7;
            $start = $now->copy()->startOfWeek($weekStartsAt);
            $end = $now->copy()->endOfWeek($weekEndsAt);
        } elseif ($type === 'this_month') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
        } else {
            return [
                'count' => 0,
                'totalAmount' => 0,
            ];
        }

        $query = \Amplify\System\Backend\Models\CustomerOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('order_status', '!=', 'Rejected');

        return [
            'count' => (clone $query)->count(),
            'totalAmount' => (clone $query)->sum('total_amount'),
        ];
    }
}

if (! function_exists('customer')) {
    /**
     * get current logged in contact customer model instance
     */
    function customer(bool $onlyContact = false): Contact|Customer|Authenticatable|null
    {
        $contact = auth('customer')->user();

        /**
         * if true return Contact Model
         */
        if ($onlyContact) {
            return $contact;
        }

        return Cache::remember(\session()->token().'-customer-model',
            (app()->environment('production')) ? HOUR : 0,
            fn () => ($contact instanceof Contact) ? $contact->customer : null);
    }
}

if (! function_exists('customer_check')) {
    /**
     * get current logged in contact customer model instance
     */
    function customer_check(): bool
    {
        return auth('customer')->check();
    }
}

if (! function_exists('has_erp_customer')) {
    /**
     * get current logged in contact customer model instance
     */
    function has_erp_customer(): bool
    {
        try {
            $cust = \ErpApi::getCustomerDetail();

            if (! empty($cust->CustomerNumber)) {
                return true;
            }

            return false;
        } catch (Throwable $th) {
            return false;
        }
    }
}

if (! function_exists('customer_guard')) {
    /**
     * get current logged in contact customer model instance
     */
    function customer_guard(): string
    {
        return Contact::AUTH_GUARD;
    }
}

if (! function_exists('customer_permissions')) {
    /**
     * get current logged in contact customer permission name as array
     */
    function customer_permissions(): array
    {
        $key = \session()->token().'_permissions';

        if (! session()->has($key)) {

            $permissions = [];

            if (customer_check()) {

                $customer = customer();

                setPermissionsTeamId($customer->getKey());

                $permissions = customer(true)?->getAllPermissions()?->pluck('name')?->toArray() ?? [];
            }

            session()->put($key, $permissions);
        }

        return session($key, []);
    }
}

if (! function_exists('carbon_date')) {
    /**
     * Convert date to desired format
     */
    function carbon_date($date = null, $format = null): string
    {
        $format = $format ?? config('amplify.basic.date_format');

        return ($date)
                ? CarbonImmutable::parse($date)->format($format)
                : 'N/A';
    }
}

if (! function_exists('carbon_datetime')) {
    /**
     * Convert date time to desired format
     */
    function carbon_datetime($date = null, $format = null): string
    {
        $format = $format ?? config('amplify.basic.date_time_format');

        return ($date)
                ? CarbonImmutable::parse($date)->format($format)
                : 'N/A';
    }
}

if (! function_exists('carbon2moment_format')) {
    /**
     * Convert carbon date time to desired moment format
     */
    function carbon2moment_format($format = null): string
    {
        $format = $format ?? config('amplify.basic.date_time_format');

        return config("amplify.constant.moment_date_formats.{$format}", 'YYYY-MM-DD');
    }
}

if (! function_exists('sku_attribute_filter')) {
    function sku_attribute_filter($defaultAttributeId, $attributes = [])
    {
        $filtered = array_filter($attributes, function ($attribute) use ($defaultAttributeId) {
            return $defaultAttributeId == $attribute['pivot']['attribute_id'];
        });

        return (count($filtered) > 0) ? array_shift($filtered) : null;
    }
}

if (! function_exists('active_shop_view')) {
    function active_shop_view()
    {
        return request()->filled('view')
                ? request('view', config('amplify.frontend.shop_page_default_view'))
                : request()->cookie('showView', config('amplify.frontend.shop_page_default_view'));
    }
}

if (! function_exists('set_customer_team_id')) {
    function set_customer_team_id($team_id)
    {
        $GLOBALS['customer_team_id'] = $team_id;
    }
}

if (! function_exists('get_customer_team_id')) {
    function get_customer_team_id()
    {
        return $GLOBALS['customer_team_id'] ?? null;
    }
}

if (! function_exists('generate_ordinal')) {
    function generate_ordinal($number): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) {
            return $number.'th';
        }

        switch ($lastDigit) {
            case 1:
                return $number.'st';
            case 2:
                return $number.'nd';
            case 3:
                return $number.'rd';
            default:
                return $number.'th';
        }
    }
}

if (! function_exists('price_format')) {
    function price_format($num, $decimals = 2)
    {
        return (string) Money::parse($num);
    }
}

if (! function_exists('discount_badge_label')) {
    function discount_badge_label($item_discount_price, $item_current_price, bool $ignore_equal_value = true)
    {
        $min_limit = config('amplify.marketing.discount_percent_to_flat_min_limit');

        if ($item_discount_price < $item_current_price) {
            $discounted_price = $item_current_price - $item_discount_price;
            $discount_perc = ($discounted_price / $item_current_price) * 100;

            if ($discount_perc >= $min_limit) {
                return round($discount_perc).'% Off';
            }

            return price_format($discounted_price).' Off';

        }

        return ($ignore_equal_value) ? '' : 'Special Price';
    }
}

if (! function_exists('checkPermissionLength')) {
    function checkPermissionLength($permissions): int
    {
        $i = 0;
        foreach ($permissions as $permission) {
            if (customer(true)->can($permission)) {
                $i++;
            }
        }

        return $i;
    }
}

if (! function_exists('havePermissions')) {
    function havePermissions(array $permissions): bool
    {
        $havePermissions = true;

        if (auth('customer')->check()) {
            $havePermissions = auth('customer')->user()->can($permissions);
        }

        return $havePermissions;
    }
}

if (! function_exists('haveAnyPermissions')) {
    function haveAnyPermissions(array $permissions): bool
    {
        $havePermissions = true;

        if (auth('customer')->check()) {
            $havePermissions = auth('customer')->user()->canany($permissions);
        }

        return $havePermissions;
    }
}

if (! function_exists('generateUserAvatar')) {
    function generateUserAvatar($name, $withBackground = true): string
    {
        $imageUrl = 'https://ui-avatars.com/api/?name='.urlencode($name);

        if (empty($name)) {
            return '';
        }

        if ($withBackground) {
            $imageUrl .= '&background=202549&color=fff';
        }

        return $imageUrl;
    }
}

if (! function_exists('cacheAll')) {
    function cacheAll($clearFirst = false): void
    {
        if ($clearFirst) {
            Artisan::call('optimize:clear');
            Log::debug(Artisan::output());
        }

        Artisan::call('optimize');
        Log::debug(Artisan::output());

        Artisan::call('event:cache');
        Log::debug(Artisan::output());
    }
}

if (! function_exists('assets_add')) {
    /**
     * if you file path doesn't have an extension (js, css)
     * then please define type for the asset
     *
     * @param  string  $type  [js, css]
     */
    function assets_add($asset, string $type, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, $type, $group, $attributes);
    }
}

if (! function_exists('push_js')) {
    /**
     * if you file path doesn't have an extension (js, css)
     * then please define type for the asset
     */
    function push_js($asset, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, AssetsLoader::TYPE_JS, $group, $attributes);
    }
}

if (! function_exists('push_css')) {
    /**
     * if you file path doesn't have an extension (js, css)
     * then please define type for the asset
     */
    function push_css($asset, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, AssetsLoader::TYPE_CSS, $group, $attributes);
    }
}

if (! function_exists('push_html')) {
    /**
     * if you file path doesn't have an extension html
     * then please define type for the asset
     */
    function push_html($asset, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, AssetsLoader::TYPE_HTML, $group, $attributes);
    }
}

if (! function_exists('assets_image')) {

    function assets_image(?string $path = null): string
    {
        return AssetsFacade::image($path);
    }
}

if (! function_exists('js_stack')) {

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param  string|array  $group
     */
    function js_stack($group = AssetsLoader::DEFAULT_GROUP): string
    {
        $js = AssetsFacade::js($group);

        if (strlen($js) > 0) {
            return '<!-- JS-'.Str::title($group).' -->'.PHP_EOL.$js;
        }

        return '';
    }
}

if (! function_exists('html_stack')) {

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param  string|array  $group
     */
    function html_stack($group = AssetsLoader::DEFAULT_GROUP): \Illuminate\Support\HtmlString
    {
        return AssetsFacade::html($group);
    }
}

if (! function_exists('css_stack')) {

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param  string|array  $group
     */
    function css_stack($group = AssetsLoader::DEFAULT_GROUP): string
    {
        $css = trim(AssetsFacade::css($group));

        if (strlen($css) > 0) {
            return '<!-- CSS-'.Str::title($group).' -->'.PHP_EOL.$css;
        }

        return '';
    }
}

if (! function_exists('unit_of_measurement')) {
    function unit_of_measurement(?string $code = null, string $default = 'Each')
    {
        $options = collect(config('amplify.pim.unit_of_measurements'));
        if (! empty($code)) {
            if ($option = $options->firstWhere('code', $code)) {
                return $option['label'];
            }

            return $default;
        }

        return $options;
    }
}

if (! function_exists('generate_product_slug')) {
    function generate_product_slug(string $value): string
    {
        $slug = strip_tags($value);
        $slug = Str::lower($slug);
        $slug = Str::replaceMatches('/[^A-Za-z0-9\-\s]/', '', $slug);
        $slug = Str::replaceMatches('/\s+/', '-', $slug);
        $slug = Str::replaceMatches('/-+/', '-', $slug);
        $slug = Str::replace('/', '~', $slug);
        $slug = Str::slug($slug);
        $slug = Str::limit($slug, 65, '');
        $slug = trim($slug, '-');

        return trim($slug);
    }
}

if (! function_exists('route_uri')) {
    /**
     * Get URI from route name.
     *
     */
    function route_uri(string $name): string
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name);

        if (! $route) {
            throw new \Illuminate\Routing\Exceptions\UrlGenerationException("Route with name '{$name}' does not exist.");
        }

        return $route->uri();
    }
}
