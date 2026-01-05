<?php

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\CustomerAddress;
use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Cms\Models\Banner;
use Amplify\System\Cms\Models\Navigation;
use Amplify\System\Cms\Models\Page;
use Amplify\System\Cms\Models\Template;
use Amplify\System\Facades\AssetsFacade;
use Amplify\System\Support\AssetsLoader;
use Amplify\System\Utility\Models\DataTransformation;
use Amplify\System\Utility\Models\ImportJobHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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

if (!function_exists('external_asset')) {
    function external_asset($url): string
    {
        return str_replace('http://', '//', $url);
    }
}

if (!function_exists('array_unset_recursive')) {
    function array_unset_recursive(&$array, $remove)
    {
        $remove = (array)$remove;
        foreach ($array as $key => &$value) {
            if (in_array($key, $remove) && $value == []) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                array_unset_recursive($value, $remove);
            }
        }
    }
}

if (!function_exists('array_rename_recursive')) {
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

if (!function_exists('is_super')) {
    function is_super($role = ['Super Admin']): bool
    {
        return is_super_admin($role);
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin($role = ['Super Admin']): bool
    {
        return backpack_auth()->check() && backpack_user()->hasRole($role);
    }
}

if (!function_exists('public_routes')) {
    function public_routes($route = null)
    {
        $routes = [
                'backpack',
                'backpack.dashboard',
                'product.status-update',
        ];

        return $route
                ? in_array($route, $routes)
                : $routes;
    }
}

if (!function_exists('get_all_routes_name')) {
    function get_all_routes_name(...$search)
    {
        $routesName = backpack_user()
                ->getAllPermissions()
                ->sortBy('name')
                ->pluck('name');

        return ($search
                ? $routesName->map(function ($name) use ($search) {
                    foreach ($search as $src) {
                        if (if_found_pos($name, $src)) {
                            return $name;
                        }
                    }

                    return false;
                })->reject(function ($val) {
                    return $val == false;
                })
                : $routesName)->values()->toArray();
    }
}

if (!function_exists('if_found_pos')) {
    function if_found_pos($name, $search): bool
    {
        return strpos($name, $search) !== false;
    }
}

if (!function_exists('get_fetch_routes')) {
    function get_fetch_routes()
    {
        return get_all_routes_name('.fetch');
    }
}

if (!function_exists('get_inline_routes')) {
    function get_inline_routes()
    {
        return get_all_routes_name('-inline-');
    }
}

if (!function_exists('get_action_button_permission')) {
    function get_action_button_permission()
    {
        //    $route = Route::currentRouteName();
        //    $routeArr                 = explode('.', $route);
        //    $post                     = end($routeArr);
        //    $route                    = implode('.', $routeArr);
        //    dd($route);
        /*return backpack_user()->can($route)
            ? true
            : false;*/
    }
}

if (!function_exists('user_has_permission')) {
    function user_has_permission($route = null): bool
    {
        // $freePass = [...get_inline_routes(), ...get_fetch_routes()];
        $freePass = get_all_routes_name('.fetch', '-inline-');

        if (public_routes($route = $route ?? Route::currentRouteName()) || in_array($route, $freePass)) {
            return true;
        }

        $routePost = [
                'create' => 'create',
                'store' => 'create',
                'destroy' => 'destroy',
                'edit' => 'edit',
                'update' => 'edit',
                'index' => 'index',
                'search' => 'index',
                'show' => 'index',
                'showDetailsRow' => 'index',
                'reorder' => 'reorder',
                'message' => 'message',
                'newMessage' => 'message',
                'reset' => 'reset',
        ];

        $routeArr = explode('.', $route);
        $post = end($routeArr);
        $routeArr[key($routeArr)] = $routePost[$post];
        $route = implode('.', $routeArr);

        return backpack_user()->can($route);
    }
}

if (!function_exists('url_query')) {
    function url_query($to, array $params = [], array $additional = []): string
    {
        $query = Arr::query($params);
        $url = Str::finish(
                url($to, $additional),
                $query
                        ? '?'
                        : ''
        );

        return $query
                ? $url . $query
                : $url;
    }
}

if (!function_exists('listOfCrudToHideBackToButton')) {
    function listOfCrudToHideBackToButton(): array
    {
        return [
                'searches',
                'site pricings',
                'core configurations',
        ];
    }
}

if (!function_exists('checkIfQuantityisValid')) {
    function checkIfQuantityisValid($_products)
    {
        $products = $_products->map(function ($product) {
            return ['item' => $product->product_code];
        });

        $warehouseString = \ErpApi::getWarehouses()->reduce(function ($previous, $current) {
            return $previous . $current->WarehouseNumber;
        }, '');

        $erp = \ErpApi::getProductPriceAvailability(['items' => $products, 'warehouse' => $warehouseString]) ?? [];
        foreach ($_products as $product) {
            foreach ($erp as $item) {
                if ($item['ItemNumber'] === $product->product_code && $item->WarehouseID == $product->product_warehouse_code) {
                    $warehouseProduct = $item;
                }
            }

            if ($warehouseProduct->QuantityAvailable < $product->quantity) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('sumOfProductPrices')) {
    function sumOfProductPrices($_products)
    {
        $price = 0;
        $products = array_map(function ($product) {
            return ['item' => $product['product_code']];
        }, $_products ?? []);

        $warehouseString = \ErpApi::getWarehouses()->reduce(function ($previous, $current) {
            return $previous . $current->WarehouseNumber;
        }, '');

        $erp = \ErpApi::getProductPriceAvailability(['items' => $products, 'warehouse' => $warehouseString]) ?? [];
        $products = array_map(function ($product) {
            return $product['product_code'];
        }, $_products ?? []);

        foreach ($_products as $product) {
            foreach ($erp as $item) {
                if ($item['ItemNumber'] === $product['product_code'] && $item['WarehouseID'] == $product['product_warehouse_code']) {
                    $price += $item['Price'] * $product['qty'];
                    break;
                }
            }
        }

        return $price;
    }
}

if (!function_exists('array_flatten')) {
    function array_flatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, array_flatten($value));
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('checkIfProductFieldIsRequired')) {
    function checkIfProductFieldIsRequired($productFieldName): bool
    {
        if (\Amplify\System\Helpers\ProductHelper::isRequiredFields()) {
            if (in_array($productFieldName, \Amplify\System\Helpers\ProductHelper::getProductMandatoryFields(), true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('getColumnListing')) {
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
                    'is_required' => strtolower($column->Null) == 'no' && $column->Default == null && !($column->Extra == 'auto_increment' && $ignoreId),
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

if (!function_exists('getTotalPriceOfItem')) {
    function getTotalPriceOfItem($price, $qty)
    {
        $price = preg_replace('/[,$]/', '', $price);

        return $price * $qty;
    }
}

if (!function_exists('getPriceOfItem')) {
    function getPriceOfItem($price, $qty)
    {
        $price = preg_replace('/[,$]/', '', $price);

        return $price * $qty;
    }
}

if (!function_exists('getAllAttributes')) {
    function getAllAttributes(): array
    {
        $attributes = \Amplify\System\Backend\Models\Attribute::query()->get(['name']);

        return collect($attributes)
                ->map(function ($attribute) {
                    return $attribute->name;
                })
                ->toArray();
    }
}

if (!function_exists('getModelNames')) {
    function getModelNames($files = null)
    {
        $files = $files ?? app_path('Models') . '/*.php';
        $models = collect(glob($files))->map(fn($file) => basename($file, '.php'))->toArray();

        return array_combine($models, $models);
    }
}

if (!function_exists('getCategorySlug')) {
    function getCategorySlug($slug, $id = null)
    {
        $where = !empty($id)
                ? [['category_slug', 'like', '%' . $slug . '%'], ['id', '!=', $id]]
                : [['category_slug', 'like', '%' . $slug . '%']];

        $count = Category::query()->where($where)->count();

        return $count > 0
                ? $slug . '-' . $count
                : $slug;
    }
}

if (!function_exists('getProductSlug')) {
    function getProductSlug($slug, $id = null)
    {
        $where = !empty($id)
                ? [['product_slug', 'like', '%' . $slug . '%'], ['id', '!=', $id]]
                : [['product_slug', 'like', '%' . $slug . '%']];

        $count = Product::query()->where($where)->count();

        return $count > 0
                ? $slug . '-' . $count
                : $slug;
    }
}

/**
 * @params - productIds - Array which contains the ids of products
 *
 * @return Collection of products that matches the productIds array
 */
if (!function_exists('getProductsFromProductIds')) {
    function getProductsFromProductIds($productIds)
    {
        return Product::whereIn('id', $productIds);
    }
}

if (!function_exists('getPageSlug')) {
    function getPageSlug($slug, $id = null)
    {
        $where = $id
                ? [['slug', 'LIKE', '%' . $slug . '%'], ['id', '!=', $id]]
                : ['slug' => $slug];

        $count = Page::query()->where($where)->count();

        return $count
                ? "$slug-$count"
                : $slug;
    }
}

if (!function_exists('getNavShortCode')) {
    function getNavShortCode($short_code, $id = null)
    {
        $where = $id
                ? [['short_code', 'LIKE', '%' . $short_code . '%'], ['id', '!=', $id]]
                : ['short_code' => $short_code];

        $count = Page::query()->where($where)->count();

        return $count
                ? "$short_code-$count"
                : $short_code;
    }
}

if (!function_exists('getTemplateSlug')) {
    function getTemplateSlug($slug, $id = null)
    {
        $where = $id
                ? [['slug', 'LIKE', '%' . $slug . '%'], ['id', '!=', $id]]
                : ['slug' => $slug];

        $count = Template::query()->where($where)->count();

        return $count
                ? "$slug-$count"
                : $slug;
    }
}

if (!function_exists('manageImportJobHistory')) {
    /**
     * @return Builder|Model|mixed|null
     */
    function manageImportJobHistory(
            string $uuid,
            int    $importJobId,
            bool   $isFinalJob = false,
            string $action = 'create',
            string $status = 'failed'
    )
    {
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

if (!function_exists('getDBTypeValues')) {
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

if (!function_exists('is_auto_publish')) {
    function is_auto_publish(): bool
    {
        return config('amplify.pim.auto_publish', false);
    }
}

if (!function_exists('getHierarchies')) {
    /**
     * @return array
     */
    function getHierarchies()
    {
        return config('amplify.basic.hierarchies', []);
    }
}

if (!function_exists('getDefaultDateTimeFormat')) {
    function getDefaultDateTimeFormat(): string
    {
        return config('amplify.basic.date_time_format');
    }
}

if (!function_exists('getDefault')) {
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

if (!function_exists('getFileData')) {
    function getFileData($toCollection, $filePath, $readerType, string $disc = 'local'): Collection
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '300');

        $fileData = Excel::toCollection($toCollection, $filePath, $disc, $readerType);

        return collect($fileData->first());
    }
}

if (!function_exists('getFileDataCount')) {
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

if (!function_exists('readFileFromLocal')) {
    function readFileFromLocal(
            $toCollection,
            $filePath,
            $readerType,
            bool $hasColumnHeading = false,
            string $disc = 'local',
            $take = null,
            array $compact = ['csvArray', 'headerRows', 'totalRow']
    ): array
    {
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
                $headerData[] = 'Column ' . $x;
            }
            $headerRows = $headerData;
        }
        $csvArray = array_values($fileData);

        return compact(...$compact);
    }
}

if (!function_exists('getFileNameFromPath')) {
    /**
     * @return false|mixed|string
     */
    function getFileNameFromPath($filePath)
    {
        $fileName = explode('/', $filePath);

        return end($fileName);
    }
}

if (!function_exists('removeExtension')) {
    function removeExtension(string $fileName): string
    {
        $fileName = explode('.', $fileName);

        return $fileName[0];
    }
}

if (!function_exists('getFileExtension')) {
    function getFileExtension(string $fileName): string
    {
        $fileName = explode('.', $fileName);

        return end($fileName);
    }
}

if (!function_exists('getFileName')) {
    function getFileName($filePath): string
    {
        $fileName = getFileNameFromPath($filePath);

        return removeExtension($fileName);
    }
}

if (!function_exists('removeFileNameFromPath')) {
    function removeFileNameFromPath($filePath): string
    {
        $fileName = getFileNameFromPath($filePath);

        return Str::replace($fileName, '', $filePath);
    }
}

// if required length is less than the actual length then repeat the string and return the required length
if (!function_exists('repeatString')) {
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
if (!function_exists('getFilesFromStorage')) {
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

if (!function_exists('deleteFileFromPublicFolder')) {
    function deleteFileFromPublicFolder(string $filePath): void
    {
        $path = public_path($filePath);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

// Get default limit for reorder
if (!function_exists('getDefaultReorderLimit')) {
    /**
     * @return HigherOrderBuilderProxy|int|mixed
     */
    function getDefaultReorderLimit()
    {
        return config('amplify.basic.default_reorder_limit', 100);
    }
}

// get full locale name
if (!function_exists('getFullLocaleName')) {
    function getFullLocaleName(string $locale): string
    {
        return config('backpack.crud.available_locales')[$locale] ?? $locale;
    }
}

// get download anchor tag
if (!function_exists('getDownloadAnchorTag')) {
    function getDownloadAnchorTag(
            string  $filePath,
            ?string $fileName = null,
            string  $title = 'Click To Download'
    ): string
    {
        $fileName = $fileName ?? getFileNameFromPath($filePath);
        $fileExists = File::exists($filePath);

        return '<a ' . ($fileExists
                        ? 'class=\'text-success\' title=\'' . $title . '\' href=\''
                        . asset($filePath) . '\'> <i class="la la-download mr-1"></i>'
                        : 'class=\'text-danger\' title=\'File Does Not Exists\' href=\'#\'>')
                . "$fileName</a>";
    }
}

// get Current DB name
if (!function_exists('getCurrentDatabaseName')) {
    function getCurrentDatabaseName(): string
    {
        return config('database.connections.' . config('database.default') . '.database');
    }
}

if (!function_exists('getFileDetails')) {
    function getFileDetails($type, $product_code = null, $key = null, $fileExt = null)
    {
        $folderPath = config('filesystems.disks.uploads.folder_name') ? config(
                        'filesystems.disks.uploads.folder_name'
                ) . '/' : '';

        $pathUrl = config('filesystems.disks.uploads.url');
        if (substr($pathUrl, -1) != '/') {
            $pathUrl = $pathUrl . '/';
        }

        if (!$fileExt) {
            $fileExt = !empty($key) ? "-{$key}.jpg" : '.jpg';
        }

        switch ($type) {
            case 'product_image':
                $file_path = $folderPath . 'images/products/' . strtolower($product_code[0]) . '/';
                $file_url = $pathUrl . $file_path . $product_code . $fileExt;

                return ['file_path' => $file_path, 'file_url' => $file_url];

            case 'brand_image':
                $file_path = $folderPath . 'images/brands/';
                $file_url = $pathUrl . $file_path . $product_code . $fileExt;

                return ['file_path' => $file_path, 'file_url' => $file_url];

            case 'product_document':
                $file_path = $folderPath . 'icecat_docs/' . strtolower($product_code[0]) . '/';
                $file_url = config(
                                'filesystems.disks.uploads.url'
                        ) . $file_path . $product_code . '-' . $key . '.' . $fileExt;

                return ['file_path' => $file_path, 'file_url' => $file_url];
            default:
                return ['file_path' => '', 'file_url' => ''];
        }
    }
}

if (!function_exists('getDataTransformations')) {
    /**
     * @return bool|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    function getDataTransformations(
            string  $appliesTo = 'Products',
            array   $inCategories = [],
            ?string $runWhen = null,
            string  $responseType = 'array'
    )
    {
        /* Getting applies to */
        $appliesTo = DataTransformation::APPLIES_TO[$appliesTo];

        /* Getting all data transformations */
        $dataTransformations = DataTransformation::query()
                ->where('run_when', 'like', '%' . ucwords(strtolower($runWhen)) . '%')
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

if (!function_exists('generateEACategoryList')) {
    function generateEACategoryList($eacategories, $sub_cat = null, $levelDepth = 0, $oldParentNodeString = ''): string
    {
        $categoryList = '<ul>';
        $levelDepth++;
        foreach ($eacategories as $eacategory) {
            if (!empty($eacategory->subCategories) && $sub_cat !== null) {
                $categoryList .= ($levelDepth === 1)
                        ? "<li class='has-children'><a href='javascript:void(0)'>{$eacategory->name}</a>"
                        : "<li><a href='" . url()->current()
                        . "?ea_server_products={$eacategory->nodeString}'>{$eacategory->name}</a>";
                if (!empty($sub_cat['@attributes']['level'])) { // if level attribute found manage level
                    if ($levelDepth <= $sub_cat['@attributes']['level']) {
                        $categoryList .= "<span>({$eacategory->productCount})</span>";
                        $categoryList .= generateEACategoryList(
                                $eacategory->subCategories,
                                $sub_cat,
                                $levelDepth,
                                $eacategory->nodeString
                                ??
                                ''
                        );
                    }
                } else { // if there is no level attributes found show all sub-categories with their sub-categories
                    $categoryList .= generateEACategoryList(
                            $eacategory->subCategories,
                            $sub_cat,
                            $levelDepth,
                            $eacategory->nodeString
                            ?? ''
                    );
                }
            } else {
                $categoryList .= '<li>';
                $categoryList .= "<a href='" . url()->current()
                        . "?ea_server_products={$eacategory->nodeString}'>{$eacategory->name}</a>";
                $categoryList .= ($levelDepth === 1)
                        ? "<span>({$eacategory->productCount})</span>"
                        : '';
            }
            $categoryList .= '</li>';
        }

        return $categoryList . '</ul>';
    }
}

if (!function_exists('getLayoutList')) {
    function getLayoutList($location): array
    {
        $templateComponentDir = theme()->component_folder ?? theme('fallback')->component_folder;
        $navLayouts = [];
        $rootFolder = base_path();
        $directoryFolder =
                $rootFolder . "/themes/{$templateComponentDir}/components/{$location}";

        if (file_exists($directoryFolder) && $handle = opendir($directoryFolder)) {
            $blacklist = ['.', '..'];
            while (($folder = readdir($handle)) !== false) {
                if (!in_array($folder, $blacklist)) {
                    $fileLocation = $directoryFolder . '/' . "$folder/config.json";
                    if (file_exists($fileLocation) && $layout = json_decode(file_get_contents($fileLocation), true)) {
                        $layout['blade_folder'] =
                                "theme::{$templateComponentDir}.components." . str_replace('/', '.', $location)
                                . ".{$folder}.index";
                        !empty($layout['unique_identifier'])
                                ? $navLayouts[$layout['unique_identifier']] = $layout
                                : '';
                    }
                }
            }
            closedir($handle);
        }

        return $navLayouts;
    }
}

if (!function_exists('getNavigationLayoutList')) {
    function getNavigationLayoutList(): array
    {
        return getLayoutList('header/navigation-layouts');
    }
}

if (!function_exists('getCategoryWithSubCategories')) {
    function getCategoryWithSubCategories($category_seopath)
    {
        $subCategories = \Sayt::getSubCategoriesByCategory($category_seopath);

        return !empty($subCategories) ? $subCategories : new stdClass;
    }
}

if (!function_exists('getActiveNavigationLayout')) {
    function getActiveNavigationLayout()
    {
        $navigationLayouts = getNavigationLayoutList();
        $activeNavigation = Navigation::query()
                ->where(['template_id' => (theme()->id ?? theme('fallback')->id), 'is_enabled' => 1])
                ->with(['menu_group', 'mobile_menu', 'account_menu'])
                ->first();

        if (!empty($activeNavigation)) {
            $layout = $navigationLayouts[$activeNavigation->layout];
            $layout['menu_short_code'] = $activeNavigation->menu_group?->short_code;
            $layout['mobile_menu_short_code'] = $activeNavigation->mobile_menu?->short_code;
            $layout['account_menu_short_code'] = $activeNavigation->account_menu?->short_code;
            $layout['menu_logo_key'] = $activeNavigation->cms_config_logo;
            $layout['top_bar'] = $activeNavigation->top_bar;
            $layout['db_contents'] = json_decode($activeNavigation->content, true);
        } else {
            $layout = [];
        }

        return $layout;
    }
}

if (!function_exists('getFooterLayoutList')) {
    function getFooterLayoutList(): array
    {
        return getLayoutList('footer');
    }
}

if (!function_exists('str_slug')) {
    function str_slug($title, $separator): string
    {
        return Str::slug($title, $separator);
    }
}

if (!function_exists('is_icecat_username_set')) {
    function is_icecat_username_set()
    {
        return strlen(config('amplify.icecat.icecat_username', '')) > 0;
    }
}

if (!function_exists('getEasyaskSkuProductImage')) {
    function getEasyaskSkuProductImage($Product): array
    {
        $product['image'] = $Product->Product_Image;
        $product['name'] = $Product->Product_Name;
        if (!empty($Product->Sku_List_Details) && count(json_decode($Product->Sku_List_Details)) == 1) {
            $product['image'] = $Product->Sku_ProductImage;
            $product['name'] = $Product->Sku_Name;
        }

        return $product;
    }
}

if (!function_exists('error_layout')) {
    /**
     * return the layout to extend on error display page
     *
     * @return string
     */
    function error_layout()
    {
        return (request()->is(config('backpack.base.route_prefix') . '/*') || request()->is('sales/*'))
                ? 'errors.layout'
                : theme_view('error');
    }
}

if (!function_exists('menuLink')) {
    function menuLink($menu)
    {
        if ($menu->url_type == 'page') {
            if (empty($menu->page_slug)) {
                return '#';
            }

            return route('dynamic-page', $menu->page_slug);
        }

        return $menu->url;
    }
}

if (!function_exists('menuContent')) {
    function menuContent($menu_group)
    {
        if (!empty($menu_group)) {
            if ($menu_group->blade_location) {
                return view(component_view($menu_group->blade_location), compact('menu_group'))->render();
            }

            return view('components.menu')
                    ->with('menu_group', $menu_group)
                    ->render();
        } else {
            return '';
        }
    }
}

if (!function_exists('get_banner_from_zone')) {
    function get_banner_from_zone($banner): array
    {
        if (count($banner) > 0) {
            $bannerDetail = Banner::whereCode($banner[0]->getid())->first();

            return !empty($bannerDetail) ? $bannerDetail->toArray() : [];
        }

        return [];
    }
}

if (!function_exists('setSiteSearchConfigToCache')) {
    function setSiteSearchConfigToCache($searchConfig, $forgetOnly = false)
    {
        if (Cache::has('siteSearchConfig')) {
            Cache::forget('siteSearchConfig');
        }

        if (!$forgetOnly) {
            Cache::forever('siteSearchConfig', $searchConfig);
        }
    }
}

if (!function_exists('getIsDynamicSiteFromCache')) {
    function getIsDynamicSiteFromCache(): int
    {
        //        return (int) Cache::get('isDynamicSite');
        return 0;
    }
}

if (!function_exists('getSiteSearchConfigFromCache')) {
    function getSiteSearchConfigFromCache()
    {
        return Cache::get('siteSearchConfig');
    }
}

if (!function_exists('setDynamicSiteSlugToCache')) {
    function setDynamicSiteSlugToCache($slug)
    {
        if (Cache::has('dynamicSiteSlug')) {
            Cache::forget('dynamicSiteSlug');
        }

        Cache::forever('dynamicSiteSlug', $slug);
    }
}

if (!function_exists('getDynamicSiteSlugFromCache')) {
    function getDynamicSiteSlugFromCache()
    {
        return Cache::get('dynamicSiteSlug');
    }
}

if (!function_exists('returnProductSlug')) {
    function returnProductSlug($product): string
    {
        $column = config('amplify.frontend.easyask_single_product_index');

        if (!empty($column) && !empty($product->{$column})) {
            return $product->{$column};
        }

        if ($column == 'product_slug' && !empty($product->Product_Slug)) {
            return $product->Product_Slug;
        }

        if ($column == 'id' && !empty($product->Product_Id)) {
            return $product->Product_Id;
        }

        if (!empty($product->id)) {
            return $product->id;
        }

        if (gettype($product) == 'string') {
            return $product;
        }

        return '';
    }
}

if (!function_exists('frontendSingleProductURL')) {
    function frontendSingleProductURL($product, $seo_path = null): string
    {
        if ($product instanceof Product && config('amplify.frontend.show_parent_product_for_sku', true) && $product->parent_id) {
            $product = Product::find($product->parent_id) ?? $product;
        }

        $productSlug = returnProductSlug($product);
        if (empty($seo_path)) {
            $seo_path = \Sayt::getDefaultCatPath();
        }
        return getIsDynamicSiteFromCache()
                ? \request()->getSchemeAndHttpHost() . '/' . getDynamicSiteSlugFromCache() . "/product/{$productSlug}?seo_path={$seo_path}"
                : route('frontend.shop.show', ['identifier' => $productSlug, 'seo_path' => $seo_path]);
    }
}

if (!function_exists('frontendShopURL')) {
    function frontendShopURL($params = null): string
    {
        $shopRoutePrefix = config('amplify.frontend.shop_page_prefix');

        $params = Arr::wrap($params);

        return getIsDynamicSiteFromCache()
                ? \request()->getSchemeAndHttpHost() . '/' . getDynamicSiteSlugFromCache() . "/{$shopRoutePrefix}/"
                : route('frontend.shop.index', $params);
    }
}

if (!function_exists('frontendHomeURL')) {
    function frontendHomeURL(): string
    {
        return getIsDynamicSiteFromCache()
                ? \request()->getSchemeAndHttpHost() . '/' . getDynamicSiteSlugFromCache()
                : route('frontend.index');
    }
}

if (!function_exists('frontend_page_url')) {
    /**
     * This function will return valid full url
     * if a page type is available and given else return home url
     * passing query parameters as array will convert them to
     * http query string
     *
     * @deprecated
     */
    function frontend_page_url(?string $pageType = null, array $data = []): string
    {
        $query = '';
        if (!empty($data)) {
            $query = '?' . http_build_query($data);
        }

        if ($pageType == null) {
            return route('frontend.index') . $query;
        }

        /**
         * Not Working
         */
        if (config('amplify.cms.page_types')) {
            throw new InvalidArgumentException("Invalid Page Type ({$pageType}) given, check `\Amplify\System\Cms\Models\Page::PAGE_TYPES`.");
        }

        $page_id = config("amplify.frontend.{$pageType}_id");

        $page = Page::findOrFail($page_id);

        return \route('frontend.index') . $page->slug . $query;
    }
}

if (!function_exists('getProductsByIds')) {
    function getProductsByIds($productIDs)
    {
        $EASPConnection = \Sayt::getEASetup();
        $config = eaShopConfig();
        $searchString = $config->ProductDetailSearch->Fieldname . ' = ' . $productIDs;
        $EASPresults = $EASPConnection->userSearch('', $searchString);

        if ($EASPresults->getTotalItems() > 0) {
            $products = $EASPresults->getProducts();

            $products = collect($products->items)->map(function ($product) {
                $product->isSkuProduct = isset($product->Sku_Id) ? true : false;
                $product->seopath = '-' . trim(
                                config('amplify.sayt.product_search_by_id_prefix')
                        ) . '-=-' . $product->Product_Id;

                return $product;
            });

            return $products;
        }

        return null;
    }
}

if (!function_exists('getFormattedIdsForEasyAskProducts')) {
    function getFormattedIdsForEasyAskProducts($products)
    {
        $ids = '';

        $length = count($products);
        foreach ($products as $key => $product) {
            if ($length > 1) {
                $ids .= $key == $length - 1 ?
                        preg_replace('/\D+/', '', optional($product)->product_id)
                        : preg_replace('/\D+/', '', optional($product)->product_id) . ' or ';
            } else {
                $ids = preg_replace('/\D+/', '', optional($product)->product_id);
            }
        }

        return $ids;
    }
}

if (!function_exists('getPaginationLengths')) {
    function getPaginationLengths(): array
    {
        $options = config('amplify.basic.length_options', '10,25,50,100,500');

        $options = trim(trim($options), ',');

        $lengthOptions = explode(',', $options);

        return array_map(function ($item) {
            return (int)$item;
        }, $lengthOptions);
    }
}

if (!function_exists('customerCartUrl')) {
    function customerCartUrl(): string
    {
        // if found in cache return it
        $cartSlug = Cache::get('customerCartUrl');
        if (!empty($cartSlug)) {
            return route('dynamic-page', $cartSlug);
        }

        $loginPage = Page::where('page_type', 'cart_page')->first();
        if (!empty($loginPage)) {
            Cache::add('customerCartUrl', $loginPage->slug, now()->addMonths(1));

            return route('dynamic-page', $loginPage->slug);
        } else {
            return '#';
        }
    }
}

if (!function_exists('getCart')) {
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

if (!function_exists('getOrCreateCart')) {
    function getOrCreateCart()
    {
        $contact_id = customer_check() ? customer(true)->id : null;
        $session_id = session()->token();

        if ($cart = getCart()) {
            return $cart;
        }

        return Cart::create([
                'contact_id' => $contact_id,
                'session_id' => $session_id,
                'status' => true,
        ]);
    }
}

if (!function_exists('getAllAddress')) {
    function getAllAddress()
    {
        if (customer_check()) {
            $customer = Contact::with('customer')->whereId(customer(true)->id)->first();

            $addresses = CustomerAddress::where('customer_id', $customer->customer->id)->get(['id', 'address_name']);

            return $addresses;
        }

        return [];
    }
}

if (!function_exists('getImageFromPath')) {
    function getImageFromPath($path)
    {
        $base64Path = base64_encode(file_get_contents($path));

        return $base64Path;
    }
}

if (!function_exists('getFileFromS3')) {
    function getFileFromS3(string $attachment): string
    {
        if ($attachment == null) {
            return $attachment;
        }

        if ($attachment[0] != '/') {
            return config('filesystems.disks.uploads.url') . '/' . $attachment;
        }

        return config('filesystems.disks.uploads.url') . $attachment;
    }
}
if (!function_exists('fileUploads')) {
    function fileUploads(?UploadedFile $file = null, $dir = '/')
    {
        if (empty($file)) {
            return '/';
        }
        // 1. Generate a filename.
        $filename = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
        $path = Storage::disk('uploads')->put($dir, $file);
        $path = Storage::disk('uploads')->url($path);

        return $path;
    }
}

if (!function_exists('get_orders')) {
    function get_orders($type)
    {
        $ordersCount = 0;
        $totalAmount = 0;

        if ($type === 'today') {
            $orders = CustomerOrder::whereDate('created_at', Carbon::today())->get();
            $ordersCount = $orders->count();
            $totalAmount = $orders->sum('total_amount');
        } elseif ($type == 'this_month') {
            $orders = CustomerOrder::whereMonth('created_at', Carbon::now()->month)->get();
            $ordersCount = $orders->count();
            $totalAmount = $orders->sum('total_amount');
        } elseif ($type == 'this_week') {
            $orders = CustomerOrder::whereBetween(
                    'created_at',
                    [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]
            )->get();
            $ordersCount = $orders->count();
            $totalAmount = $orders->sum('total_amount');
        }

        return [
                'count' => $ordersCount,
                'totalAmount' => $totalAmount,
        ];
    }
}

if (!function_exists('mega_menu_columns')) {
    /**
     * Dynamically calculate the mega menu column length
     *
     * @return int
     */
    function mega_menu_columns($parentColumn)
    {
        $parentColumn = (int)(strpos($parentColumn, 'col-md-') !== false)
                ? str_replace('col-md-', '', $parentColumn)
                : $parentColumn;

        if ($parentColumn != null && is_numeric($parentColumn)) {
            if ($parentColumn > 6) {
                return 4;
            } elseif ($parentColumn > 3) {
                return 2;
            }
        }

        return 1;
    }
}

if (!function_exists('mega_menu_max_height')) {
    function mega_menu_max_height()
    {
        $height = config('amplify.frontend.mega_menu_max_height', '310px');
        if (is_numeric($height)) {
            return intval($height) . 'px';
        }

        return $height;
    }
}

if (!function_exists('customer')) {
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

        return Cache::remember(\Illuminate\Support\Facades\Session::token() . '-customer-model',
                (\Illuminate\Support\Facades\App::environment('production')) ? HOUR : 0,
                fn() => ($contact instanceof Contact) ? $contact->customer : null);
    }
}

if (!function_exists('customer_check')) {
    /**
     * get current logged in contact customer model instance
     */
    function customer_check(): bool
    {
        return auth('customer')->check();
    }
}

if (!function_exists('has_erp_customer')) {
    /**
     * get current logged in contact customer model instance
     */
    function has_erp_customer(): bool
    {
        try {
            $cust = ErpApi::getCustomerDetail();

            if (!empty($cust->CustomerNumber)) {
                return true;
            }

            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if (!function_exists('customer_guard')) {
    /**
     * get current logged in contact customer model instance
     */
    function customer_guard(): string
    {
        return 'customer';
    }
}

if (!function_exists('customer_permissions')) {
    /**
     * get current logged in contact customer permission name as array
     */
    function customer_permissions(): array
    {
        $key = \Illuminate\Support\Facades\Session::token() . '_permissions';

        if (!session()->has($key)) {

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

if (!function_exists('carbon_date')) {
    /**
     * Convert date to desired format
     */
    function carbon_date($date = null, $format = null): string
    {
        $format = $format ?? config('amplify.basic.date_format');

        return ($date)
                ? \Carbon\CarbonImmutable::parse($date)->format($format)
                : 'N/A';
    }
}

if (!function_exists('carbon_datetime')) {
    /**
     * Convert date time to desired format
     */
    function carbon_datetime($date = null, $format = null): string
    {
        $format = $format ?? config('amplify.basic.date_time_format');

        return ($date)
                ? \Carbon\CarbonImmutable::parse($date)->format($format)
                : 'N/A';
    }
}

if (!function_exists('carbon2moment_format')) {
    /**
     * Convert carbon date time to desired moment format
     */
    function carbon2moment_format($format = null): string
    {
        $format = $format ?? config('amplify.basic.date_time_format');

        return config("amplify.constant.moment_date_formats.{$format}", 'YYYY-MM-DD');
    }
}

if (!function_exists('suppress_exception')) {
    function suppress_exception(): bool
    {
        return config('amplify.suppress_exception', true);
    }
}

if (!function_exists('sku_attribute_filter')) {
    function sku_attribute_filter($defaultAttributeId, $attributes = [])
    {
        $filtered = array_filter($attributes, function ($attribute) use ($defaultAttributeId) {
            return $defaultAttributeId == $attribute['pivot']['attribute_id'];
        });

        return (count($filtered) > 0) ? array_shift($filtered) : null;
    }
}

if (!function_exists('active_shop_view')) {
    function active_shop_view()
    {
        return request()->filled('view')
                ? request('view', config('amplify.frontend.shop_page_default_view'))
                : request()->cookie('showView', config('amplify.frontend.shop_page_default_view'));
    }
}

if (!function_exists('sort_link')) {
    function sort_link(string $column, ?string $label = null)
    {

        $inputs = request()->query();

        $label = $label ?? ucfirst($column);

        $inputs['dir'] = (
                (isset($inputs['sort']) && $inputs['sort'] == $column)
                && (isset($inputs['dir']) && $inputs['dir'] == 'asc')
        ) ? 'desc' : 'asc';
        $inputs['sort'] = $column;

        $newUrl = request()->fullUrlWithQuery($inputs);

        return new HtmlString("<a class='d-flex justify-content-between text-dark' style='text-decoration: none;' href='{$newUrl}'>{$label}</a>");
    }
}

if (!function_exists('set_customer_team_id')) {
    function set_customer_team_id($team_id)
    {
        $GLOBALS['customer_team_id'] = $team_id;
    }
}

if (!function_exists('get_customer_team_id')) {
    function get_customer_team_id()
    {
        return $GLOBALS['customer_team_id'] ?? null;
    }
}

if (!function_exists('generate_ordinal')) {
    function generate_ordinal($number): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) {
            return $number . 'th';
        }

        switch ($lastDigit) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
        }
    }
}

if (!function_exists('validate_number')) {
    function validate_number($num)
    {
        return (float)filter_var($num, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}

if (!function_exists('price_format')) {
    function price_format($num, $decimals = 2)
    {
        return (string)\Amplify\System\Support\Money::parse($num);
    }
}

if (!function_exists('discount_badge_label')) {
    function discount_badge_label($item_discount_price, $item_current_price, bool $ignore_equal_value = true)
    {
        $min_limit = config('amplify.marketing.discount_percent_to_flat_min_limit');

        if ($item_discount_price < $item_current_price) {
            $discounted_price = $item_current_price - $item_discount_price;
            $discount_perc = ($discounted_price / $item_current_price) * 100;

            if ($discount_perc >= $min_limit) {
                return round($discount_perc) . '% Off';
            }

            return price_format($discounted_price) . ' Off';

        }

        return ($ignore_equal_value) ? '' : 'Special Price';
    }
}

// blade rendering related functions
/**
 * @removed  generateEACategoryPageList()
 *
 * @see <x-ea-category-page-list :categories="$eacategories" />
 */
if (!function_exists('generateInternalCategoryList')) {
    function generateInternalCategoryList($categories, $sub_cat = null, $levelDepth = 0): string
    {
        $categoryList = '<ul>';
        $levelDepth++;
        foreach ($categories as $category) {
            if (isset($category['children']) && !empty($sub_cat)) {
                $categoryList .= ($levelDepth === 1)
                        ? "<li class='has-children'><a href='javascript:void(0)'>{$category['label']}</a>"
                        : "<li><a href='"
                        . request()->fullUrlWithQuery(['internal_category_id' => $category['id'], 'page' => 1])
                        . "'>{$category['label']}</a>";
                if (!empty($sub_cat['@attributes']['level'])) { // if level attribute found manage level
                    if ($levelDepth <= $sub_cat['@attributes']['level']) {
                        $categoryList .= isset($category['children'])
                                ? count($category['children']) > 0
                                        ? '<span>(' . count($category['children']) . ')</span>'
                                        : ''
                                : '';
                        $categoryList .= generateInternalCategoryList($category['children'] ?? [], $sub_cat, $levelDepth);
                    }
                } else { // if there is no level attributes found show all sub-categories with their sub-categories
                    $categoryList .= generateInternalCategoryList($category['children'] ?? [], $sub_cat, $levelDepth);
                }
            } else {
                $categoryList .= '<li>';
                $categoryList .= "<a href='javascript:void(0)'>{$category['label']}</a>";
                $categoryList .= ($levelDepth === 1)
                        ? isset($category['children'])
                                ? '<span>(' . count($category['children']) . ')</span>'
                                : ''
                        : '';
            }
            $categoryList .= '</li>';
        }

        return $categoryList . '</ul>';
    }
}

if (!function_exists('easyAskProductPagination')) {
    /**
     * @return array|false|Application|Factory|View|mixed
     */
    function easyAskProductPagination($cur_page, $number_of_pages, $prev_next = false)
    {
        $ends_count = 1;    // how many items at the ends (before and after [...])
        $middle_count = 2;    // how many items before and after current page
        $dots = false;

        return view(component_view('product.product-pagination.custom-ea-pagination'), [
                'cur_page' => $cur_page,
                'number_of_pages' => $number_of_pages,
                'middle_count' => $middle_count,
                'ends_count' => $ends_count,
                'dots' => $dots,
        ]);
    }
}

if (!function_exists('t2EasyAskProductPagination')) {
    function t2EasyAskProductPagination($cur_page, $number_of_pages, $prev_next = false)
    {
        $ends_count = 1;  // how many items at the ends (before and after [...])
        $middle_count = 2;  // how many items before and after current page
        $dots = false;
        ?>

        <ul class="pagination">
            <?php
            if ($prev_next && $cur_page && $cur_page > 1) {  // print previous button?
                ?>
                <li><a class="btn btn-primary"
                       href="<?php echo request()->fullUrlWithQuery(['currentPage' => $cur_page - 1]); ?>">Prev</a></li>
                <?php
            } else { ?>
                <li>
                    <button disabled class="btn btn-primary">Prev</button>
                </li>

            <?php }
            ?>

            <?php
            for ($i = 1; $i <= $number_of_pages; $i++) {
                if ($i == $cur_page) {
                    ?>
                    <li><a class="btn btn-primary text-white text-dark"><?php
                        echo $i; ?></a></li><?php
                    $dots = true;
                } else {
                    if ($i <= $ends_count
                            || ($cur_page && $i >= $cur_page - $middle_count
                                    && $i <= $cur_page + $middle_count)
                            || $i > $number_of_pages - $ends_count) {
                        ?>
                        <li><a class="active btn btn-light text-dark" href="<?php
                        echo request()->fullUrlWithQuery(['currentPage' => $i]); ?>"><?php
                            echo $i; ?></a></li><?php
                        $dots = true;
                    } elseif ($dots) {
                        ?>
                        <li><a>&hellip;</a></li><?php
                        $dots = false;
                    }
                }
            }
            ?>

            <?php
            if ($prev_next && $cur_page
                    && ($cur_page < $number_of_pages
                            || $number_of_pages == -1)) { // print next button?
                ?>
                <li><a class="btn btn-primary"
                       href="<?php echo request()->fullUrlWithQuery(['currentPage' => $cur_page + 1]); ?>">Next</a></li>
                <?php
            } else { ?>
                <li>
                    <button disabled class="btn btn-primary">Next</button>
                </li>

            <?php }
            ?>


        </ul>


        <?php
    }
}

/**
 * @removed  generateEACategoryPageList()
 *
 * @see <x-ea-category-page-list :categories="$eacategories" />
 */
if (!function_exists('generateInternalCategoryList')) {
    function generateInternalCategoryList($categories, $sub_cat = null, $levelDepth = 0): string
    {
        $categoryList = '<ul>';
        $levelDepth++;
        foreach ($categories as $category) {
            if (isset($category['children']) && !empty($sub_cat)) {
                $categoryList .= ($levelDepth === 1)
                        ? "<li class='has-children'><a href='javascript:void(0)'>{$category['label']}</a>"
                        : "<li><a href='"
                        . request()->fullUrlWithQuery(['internal_category_id' => $category['id'], 'page' => 1])
                        . "'>{$category['label']}</a>";
                if (!empty($sub_cat['@attributes']['level'])) { // if level attribute found manage level
                    if ($levelDepth <= $sub_cat['@attributes']['level']) {
                        $categoryList .= isset($category['children'])
                                ? count($category['children']) > 0
                                        ? '<span>(' . count($category['children']) . ')</span>'
                                        : ''
                                : '';
                        $categoryList .= generateInternalCategoryList($category['children'] ?? [], $sub_cat, $levelDepth);
                    }
                } else { // if there is no level attributes found show all sub-categories with their sub-categories
                    $categoryList .= generateInternalCategoryList($category['children'] ?? [], $sub_cat, $levelDepth);
                }
            } else {
                $categoryList .= '<li>';
                $categoryList .= "<a href='javascript:void(0)'>{$category['label']}</a>";
                $categoryList .= ($levelDepth === 1)
                        ? isset($category['children'])
                                ? '<span>(' . count($category['children']) . ')</span>'
                                : ''
                        : '';
            }
            $categoryList .= '</li>';
        }

        return $categoryList . '</ul>';
    }
}

if (!function_exists('easyAskProductPagination')) {
    /**
     * @return array|false|Application|Factory|View|mixed
     */
    function easyAskProductPagination($cur_page, $number_of_pages, $prev_next = false)
    {
        $ends_count = 1;    // how many items at the ends (before and after [...])
        $middle_count = 2;    // how many items before and after current page
        $dots = false;

        return view(component_view('product.product-pagination.custom-ea-pagination'), [
                'cur_page' => $cur_page,
                'number_of_pages' => $number_of_pages,
                'middle_count' => $middle_count,
                'ends_count' => $ends_count,
                'dots' => $dots,
        ]);
    }
}
if (!function_exists('checkPermissionLength')) {
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

if (!function_exists('havePermissions')) {
    function havePermissions(array $permissions): bool
    {
        $havePermissions = true;

        if (auth('customer')->check()) {
            $havePermissions = auth('customer')->user()->can($permissions);
        }

        return $havePermissions;
    }
}

if (!function_exists('haveAnyPermissions')) {
    function haveAnyPermissions(array $permissions): bool
    {
        $havePermissions = true;

        if (auth('customer')->check()) {
            $havePermissions = auth('customer')->user()->canany($permissions);
        }

        return $havePermissions;
    }
}

if (!function_exists('generateUserAvatar')) {
    function generateUserAvatar($name, $withBackground = true): string
    {
        $imageUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name);

        if (empty($name)) {
            return '';
        }

        if ($withBackground) {
            $imageUrl .= '&background=202549&color=fff';
        }

        return $imageUrl;
    }
}

if (!function_exists('cacheAll')) {
    function cacheAll($clearFirst = false): void
    {
        if ($clearFirst) {
            Artisan::call('optimize:clear');
            Log::debug(Artisan::output());
        }

        Artisan::call('optimize');
        Log::debug(Artisan::output());

        Artisan::call('view:cache');
        Log::debug(Artisan::output());

        Artisan::call('event:cache');
        Log::debug(Artisan::output());
    }
}

if (!function_exists('assets_add')) {
    /**
     * if you file path doesn't have an extension (js, css)
     * then please define type for the asset
     *
     * @param string $type [js, css]
     */
    function assets_add($asset, string $type, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, $type, $group, $attributes);
    }
}

if (!function_exists('push_js')) {
    /**
     * if you file path doesn't have an extension (js, css)
     * then please define type for the asset
     */
    function push_js($asset, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, AssetsLoader::TYPE_JS, $group, $attributes);
    }
}

if (!function_exists('push_css')) {
    /**
     * if you file path doesn't have an extension (js, css)
     * then please define type for the asset
     */
    function push_css($asset, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, AssetsLoader::TYPE_CSS, $group, $attributes);
    }
}

if (!function_exists('push_html')) {
    /**
     * if you file path doesn't have an extension html
     * then please define type for the asset
     */
    function push_html($asset, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = []): void
    {
        AssetsFacade::add($asset, AssetsLoader::TYPE_HTML, $group, $attributes);
    }
}

if (!function_exists('assets_image')) {

    function assets_image(?string $path = null): string
    {
        return AssetsFacade::image($path);
    }
}

if (!function_exists('js_stack')) {

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param string|array $group
     */
    function js_stack($group = AssetsLoader::DEFAULT_GROUP): string
    {
        $js = AssetsFacade::js($group);

        if (strlen($js) > 0) {
            return '<!-- JS-' . \Illuminate\Support\Str::title($group) . ' -->' . PHP_EOL . $js;
        }

        return '';
    }
}

if (!function_exists('html_stack')) {

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param string|array $group
     */
    function html_stack($group = AssetsLoader::DEFAULT_GROUP): HtmlString
    {
        return AssetsFacade::html($group);
    }
}

if (!function_exists('css_stack')) {

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param string|array $group
     */
    function css_stack($group = AssetsLoader::DEFAULT_GROUP): string
    {
        $css = trim(AssetsFacade::css($group));

        if (strlen($css) > 0) {
            return '<!-- CSS-' . \Illuminate\Support\Str::title($group) . ' -->' . PHP_EOL . $css;
        }

        return '';
    }
}

if (!function_exists('unit_of_measurement')) {
    function unit_of_measurement(string $code = null, string $default = 'Each')
    {
        $options = collect(config('amplify.pim.unit_of_measurements'));
        if (!empty($code)) {
            if ($option = $options->firstWhere('code', $code)) {
                return $option['label'];
            }

            return $default;
        }
        return $options;
    }
}
