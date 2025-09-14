<?php

namespace Amplify\System\Helpers;

use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\CategoryProduct;
use Amplify\System\Backend\Models\Product;

class UtilityHelper
{
    private static array $xmlArray = [];

    /**
     * Convert a value to given data type
     *
     * @return mixed
     */
    public static function typeCast(mixed $value, string $type = 'string')
    {
        if (gettype($value) === $type) {
            return $value;
        }

        if ($value == null) {
            return null;
        }
        switch ($type) {
            case 'boolean':
            case 'bool' :
                if (is_string($value)) {
                    return ! in_array($value, ['false', 'FALSE', '0', '']);
                }

                return (bool) $value;

            case 'float' :
            case 'double' :
                return floatval($value);

            case 'int' :
            case 'integer' :
                return intval($value);

            case 'json' :
                return json_decode($value, true);

            case 'string' :
            default:
                return (string) $value;
        }
    }

    /**
     * Convert All Datatype to string equivalent value
     *
     * @param  mixed  $value
     * @return false|string
     */
    public static function stringify(string $type, $value = null)
    {
        if ($value === null) {
            return '';
        }

        switch ($type) {
            case 'boolean':
            case 'bool':
                return (in_array($value, ['false', '0', 0, false], true)) ? 'false' : 'true';

            case 'json':
            case 'array':
                return (is_string($value)) ? $value : json_encode($value);

            case 'integer':
                return (is_numeric($value)) ? (string) filter_var($value, FILTER_SANITIZE_NUMBER_INT) : '';

            case 'float':
                return (is_numeric($value)) ? (string) filter_var(
                    $value,
                    FILTER_SANITIZE_NUMBER_FLOAT,
                    FILTER_FLAG_ALLOW_FRACTION
                ) : '';

            default:
                return (string) $value;
        }
    }

    /**
     * return all currency available for backend use
     */
    public static function currencyDropdown(): array
    {
        return array_map(function ($item) {
            return $item['code'].' - '.$item['name'];
        }, config('amplify.constant.currency'));

    }

    public static function honeypot(): array
    {
        return \Illuminate\Support\Facades\App::make(\Spatie\Honeypot\Honeypot::class)->toArray();
    }

    /**
     * Parse and convert a valid xml string into array
     *
     * @throws \Exception
     */
    public static function parseXml(string $content, bool $preserveNS = false): array
    {
        $xmlArray = [];

        $xmlObject = new \DOMDocument;
        $xmlObject->preserveWhiteSpace = false;
        $xmlObject->formatOutput = true;
        $xmlObject->loadXML($content);

        foreach ($xmlObject->getElementsByTagName('Catalog') as $catalog) {
            foreach ($catalog->childNodes as $index => $node) {
                $xmlArray[$index] = [];
                self::domToArray($node, $node->nodeName, $xmlArray[$index], $node->prefix, $preserveNS);
            }
        }

        // $node = $xmlObject->getElementsByTagName('Catalog');

        // self::domToArray($node, $node->tagName, $xmlArray, $node->prefix, $preserveNS);

        return $xmlArray;
    }

    /**
     * Iterator for the parseXML function
     *
     * @param  \DOMNode|\DOMElement|null  $node
     */
    private static function domToArray($node, string $nodeName, array &$constructArray, string $namespacePrefix = '', bool $preserveNS = false): void
    {
        $nodeName = ($preserveNS) ? $nodeName : str_replace("{$namespacePrefix}:", '', $nodeName);

        if ($node->nodeType == XML_TEXT_NODE) {
            $constructArray[$nodeName] = ['@content' => self::typeCast($node->nodeValue, gettype($node->nodeValue))];

            $constructArray[$nodeName]['@attributes'] = [];

            foreach ($node->attributes as $attribute) {
                $attributeName = ($preserveNS) ? $attribute->nodeName : str_replace("{$namespacePrefix}:", '', $attribute->nodeName);
                $constructArray[$nodeName]['@attributes'][$attributeName] = self::typeCast($attribute->nodeValue, gettype($attribute->nodeValue));
            }

            return;
        }

        if ($node->childNodes->length === 1 && $node->firstChild->nodeType == XML_TEXT_NODE) {
            $constructArray[$nodeName] = self::typeCast($node->firstChild->textContent, gettype($node->firstChild->textContent));

            return;
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child->nodeName === 'xs:schema') {
                    continue;
                }
                if (! isset($constructArray[$nodeName])) {
                    $constructArray[$nodeName] = [];
                }
                self::domToArray($child, $child->nodeName, $constructArray[$nodeName], $child->prefix, $preserveNS);
            }

            return;
        }

        $constructArray[$nodeName] = ['@content' => self::typeCast($node->nodeValue, gettype($node->nodeValue))];

        $constructArray[$nodeName]['@attributes'] = [];

        foreach ($node->attributes as $attribute) {
            $attributeName = ($preserveNS) ? $attribute->nodeName : str_replace("{$namespacePrefix}:", '', $attribute->nodeName);
            $constructArray[$nodeName]['@attributes'][$attributeName] = self::typeCast($attribute->nodeValue, gettype($attribute->nodeValue));
        }

        //        , '@attributes' => $node->attributes];
    }

    /**
     * Validate given content is a valid JSON
     */
    public static function isJson(mixed $content = null): bool
    {
        if (is_string($content)) {
            json_decode($content);

            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }

    /**
     *  NOTE: This function is only for DK-Lok XML data
     * This is a utility function to extract structured attributes from XML content
     */
    public static function extractStructuredAttributes(string $content): array
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($content);

        $xpath = new \DOMXPath($dom);
        $attributeNodes = $xpath->query('//Attribute');

        $attributeMap = [];

        foreach ($attributeNodes as $attribute) {
            /** @var \DOMElement $attribute */
            $id = $attribute->getAttribute('ID');
            $name = $attribute->getAttribute('Name');
            $dataType = $attribute->getAttribute('DataType');
            $group = $attribute->getAttribute('Group');

            // Measure extraction (may have multiple, take the first meaningful one)
            $measures = $attribute->getElementsByTagName('Measure');
            $measureName = null;
            $measureId = null;
            foreach ($measures as $measure) {
                if ($measure instanceof \DOMElement && strtolower($group) === 'dimensions') {
                    $measureName = $measure->getAttribute('Name');
                    $measureId = $measure->getAttribute('ID');
                    break; // only take one for now
                }
            }

            if (! isset($attributeMap[$id])) {
                $attributeMap[$id] = [
                    'id' => $id,
                    'name' => $name,
                    //                    'type' => self::mapDataType($dataType),
                    'type' => 'text',
                    'unit' => $measureName ?? null,
                ];
            }
        }

        return array_values($attributeMap);
    }

    /**
     * NOTE: This function is only for DK-Lok XML data
     * Map XML data types to internal data types
     */
    private static function mapDataType(string $xmlType): string
    {
        return match (strtolower($xmlType)) {
            'character' => 'text',
            'numeric' => 'decimal',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'enum' => 'enum',
            'date' => 'date',
            default => 'text',
        };
    }

    public static function extractStructuredCategories(string $content): array
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);

        $xpath = new \DOMXPath($dom);

        // Collect all assets into a map [id => url]
        $assets = [];
        foreach ($xpath->query('//Asset') as $asset) {
            $id = $asset->getAttribute('ID');
            $url = $asset->getAttribute('URL');
            $assets[$id] = 'https://catalog.dklokusa.com'.$url;
        }

        $categories = [];

        $catalogNodes = $xpath->query('//Catalog');
        foreach ($catalogNodes as $catalog) {
            foreach ($catalog->childNodes as $node) {
                if ($node->nodeName === 'Category') {
                    self::processCategoryNode($node, null, $assets, $categories);
                }
            }
        }

        return $categories;
    }

    private static function processCategoryNode(\DOMNode $node, $parentId, array $assets, array &$categories)
    {
        if ($node->nodeType !== XML_ELEMENT_NODE || $node->nodeName !== 'Category') {
            return;
        }

        $categoryId = $node->attributes->getNamedItem('ID')?->nodeValue;
        $categoryName = $node->attributes->getNamedItem('Name')?->nodeValue;
        $categorySlug = trim(parse_url($node->attributes->getNamedItem('URL')?->nodeValue ?? '', PHP_URL_PATH), '/');
        $categorySlug = str_replace('category/', '', $categorySlug);

        // Find image ID from children
        $imageUrl = null;
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'AssetID' && $child->attributes->getNamedItem('Context')?->nodeValue === 'Primary Image') {
                $assetId = $child->textContent;
                $imageUrl = $assets[$assetId] ?? null;
            }
        }

        $categories[] = [
            'category_id' => (int) $categoryId,
            'category_code' => str_replace(' ', '-', $categoryName),
            'category_name' => $categoryName,
            'category_slug' => $categorySlug,
            'parent_id' => $parentId ? (int) $parentId : null,
            'image' => $imageUrl,
        ];

        // Recurse into children
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'Category') {
                self::processCategoryNode($child, $categoryId, $assets, $categories);
            }
        }
    }

    public static function extractStructuredMasterProducts(string $content): array
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);

        $xpath = new \DOMXPath($dom);

        // Asset mapping: [id => full image URL]
        $assets = [];
        foreach ($xpath->query('//Asset') as $asset) {
            $id = $asset->getAttribute('ID');
            $url = $asset->getAttribute('URL');
            $assets[$id] = 'https://catalog.dklokusa.com'.$url;
        }

        $products = [];

        foreach ($xpath->query('//Product') as $productNode) {
            $id = $productNode->getAttribute('ID');
            $name = $productNode->getAttribute('Name');
            $url = $productNode->getAttribute('URL');
            $productCode = collect(explode('/', $url))->last();

            // Get image from AssetID Context="Primary Image"
            $imageId = null;
            foreach ($productNode->getElementsByTagName('AssetID') as $assetIdNode) {
                if ($assetIdNode->getAttribute('Context') === 'Primary Image') {
                    $imageId = $assetIdNode->nodeValue;
                    break;
                }
            }
            $imageUrl = $imageId && isset($assets[$imageId]) ? $assets[$imageId] : null;

            // Get first <Item> and collect AttributeIDs
            $attributeIds = [];
            $skuProductCodes = [];
            foreach ($productNode->getElementsByTagName('Item') as $index => $itemNode) {
                if ($index === 0) {
                    foreach ($itemNode->getElementsByTagName('Data') as $dataNode) {
                        $attrId = $dataNode->getAttribute('AttributeID');
                        if ($attrId && is_numeric($attrId)) {
                            $attributeIds[] = (int) $attrId;
                        }
                    }
                } //only for first time

                $skuUrl = $itemNode->getAttribute('URL');
                $skuProductCodes[] = collect(explode('/', $skuUrl))->last();
            }

            $products[] = [
                'id' => (int) $id,
                'product_name' => $name,
                'product_code' => $productCode,
                'image' => $imageUrl,
                'sku_default_attributes' => array_values(array_unique($attributeIds)),
                'sku_product_codes' => array_values(array_unique($skuProductCodes)),
            ];
        }

        return $products;
    }

    public static function attachProductsToCategories(string $content): array
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);

        $xpath = new \DOMXPath($dom);

        $attached = 0;
        $skipped = 0;

        // Only process categories that do NOT have nested Category nodes (leaf categories)
        foreach ($xpath->query('//Category[not(Category)]') as $categoryNode) {
            $tracepartsCategoryId = (int) $categoryNode->getAttribute('ID');
            $categoryId = Category::where('traceparts_category_id', $tracepartsCategoryId)->value('id');

            foreach ($categoryNode->getElementsByTagName('ProductID') as $productNode) {
                $tracepartsProductId = (int) $productNode->nodeValue;
                $productId = Product::where('traceparts_product_id', $tracepartsProductId)->value('id');
                $productExists = Product::where('id', $productId)->exists();
                $categoryExists = CategoryProduct::where('product_id', $productId)->where('category_id', $categoryId)->exists();

                if ($productExists && ! $categoryExists) {
                    CategoryProduct::create([
                        'product_id' => $productId,
                        'category_id' => $categoryId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $attached++;
                } else {
                    $skipped++;
                }
            }
        }

        return ['attached' => $attached, 'skipped' => $skipped];
    }

    public static function extractStructuredSkuProductsWithAllData(string $content): array
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);

        $xpath = new \DOMXPath($dom);

        // Build asset ID => URL map
        $assets = [];
        foreach ($xpath->query('//Asset') as $asset) {
            $id = $asset->getAttribute('ID');
            $url = $asset->getAttribute('URL');
            $name = $asset->getAttribute('Name');
            $assets[$id]['url'] = str_starts_with($url, 'http') ? $url : 'https://catalog.dklokusa.com'.$url;
            $assets[$id]['name'] = $name;
        }

        // Build attribute metadata map
        $attributeMeta = [];
        foreach ($xpath->query('//Product/Attribute') as $attrNode) {
            $attrId = $attrNode->getAttribute('ID');
            $group = $attrNode->getAttribute('Group');

            $measures = [];
            foreach ($attrNode->getElementsByTagName('Measure') as $measureNode) {
                $measures[$measureNode->getAttribute('ID')] = $measureNode->getAttribute('Name');
            }

            $attributeMeta[$attrId] = [
                'group' => $group,
                'measures' => $measures,
            ];
        }

        $skus = [];
        foreach ($xpath->query('//Product') as $productNode) {
            $parentId = (int) $productNode->getAttribute('ID');

            // Map ItemIDs to <Item> blocks under same Product
            foreach ($productNode->getElementsByTagName('Item') as $itemNode) {
                $skuId = (int) $itemNode->getAttribute('ID');
                $skuUrl = $itemNode->getAttribute('URL');
                $skuCode = collect(explode('/', $skuUrl))->last();
                $productName = 'Unnamed';
                $attributes = [];

                foreach ($itemNode->getElementsByTagName('Data') as $dataNode) {
                    $attrId = $dataNode->getAttribute('AttributeID');
                    $measureId = $dataNode->getAttribute('MeasureID');
                    $val = null;

                    if ($node = $dataNode->getElementsByTagName('ValueCharacter')->item(0)) {
                        $val = $node->nodeValue;
                    } elseif ($node = $dataNode->getElementsByTagName('ValueLongText')->item(0)) {
                        $val = $node->nodeValue;
                    } elseif ($node = $dataNode->getElementsByTagName('ValueNumeric')->item(0)) {
                        $val = $node->nodeValue;
                    }

                    if ($attrId && $val !== null) {
                        $measureName = $attributeMeta[$attrId]['measures'][$measureId] ?? null;
                        $groupName = $attributeMeta[$attrId]['group'] ?? null;

                        $valueWithUnit = ($measureId !== '0' && $measureName)
                            ? "{$val} {$measureName}"
                            : $val;

                        $attributes[] = [
                            'attribute_id' => (int) $attrId,
                            'attribute_value' => $valueWithUnit,
                            'group' => $groupName,
                        ];

                        if ((int) $attrId === 15) {
                            $productName = $val;
                        }
                    }
                }

                // Assets for this SKU
                $mainImage = null;
                $additionalImages = [];
                $documents = [];

                foreach ($itemNode->getElementsByTagName('AssetID') as $assetIdNode) {
                    $context = $assetIdNode->getAttribute('Context');
                    $assetId = trim($assetIdNode->nodeValue);
                    $url = $assets[$assetId]['url'] ?? null;
                    if (! $url) {
                        continue;
                    }

                    if ($context === 'Primary Image') {
                        $mainImage = $url;
                    } elseif ($context === 'Secondary Image') {
                        $additionalImages = [$url];
                    } elseif ($context === 'Downloads') {
                        $documents[] = [
                            'url' => $url,
                            'name' => $assets[$assetId]['name'] ?? null,
                        ];
                    }
                }

                $skus[] = [
                    'id' => $skuId,
                    'parent_id' => $parentId,
                    'product_code' => $skuCode,
                    'product_name' => $productName,
                    'attributes' => $attributes,
                    'main_image' => $mainImage,
                    'additional_images' => $additionalImages,
                    'documents' => $documents,
                ];
            }
        }

        return $skus;
    }

    public static function cleanCategoryCode($string): string
    {
        // Replace slashes with hyphens
        $string = str_replace('/', '-', $string);

        // Remove all characters except letters, numbers, spaces, and hyphens
        $string = preg_replace('/[^A-Za-z0-9\- ]/', '', $string);

        // Replace multiple hyphens or spaces with a single hyphen
        $string = preg_replace('/[\s\-]+/', '-', $string);

        // Trim hyphens from beginning and end
        return trim($string, '-');
    }

    public static function generateUniqueCategoryCodeOrSlug($baseData, $type = 'code')
    {
        $data = $baseData;
        $counter = 2;
        $column = $type === 'code' ? 'category_code' : 'category_slug';

        while (Category::where($column, $data)->exists()) {
            $data = $baseData.'-'.$counter;
            $counter++;
        }

        return $data;
    }

    /**
     * Stream SKU items from the big XML, yielding one-at-a-time
     */
    public static function streamSkuItems(string $filePath): \Generator
    {
        $reader = new \XMLReader;
        $reader->open($filePath);

        // Build asset ID => URL map
        $assets = [];
        // We need to grab all <Asset> first
        $domAssets = new \DOMDocument;
        $domAssets->preserveWhiteSpace = false;
        $domAssets->load($filePath);
        $xpathAssets = new \DOMXPath($domAssets);
        foreach ($xpathAssets->query('//Asset') as $asset) {
            $id = $asset->getAttribute('ID');
            $url = $asset->getAttribute('URL');
            $name = $asset->getAttribute('Name');
            $assets[$id]['url'] = str_starts_with($url, 'http') ? $url : 'https://catalog.dklokusa.com'.$url;
            $assets[$id]['name'] = $name;
        }

        // Build attribute metadata map
        $attributeMeta = [];
        foreach ($xpathAssets->query('//Product/Attribute') as $attrNode) {
            $attrId = $attrNode->getAttribute('ID');
            $group = $attrNode->getAttribute('Group');
            $measures = [];
            foreach ($attrNode->getElementsByTagName('Measure') as $measureNode) {
                $measures[$measureNode->getAttribute('ID')] = $measureNode->getAttribute('Name');
            }
            $attributeMeta[$attrId] = [
                'group' => $group,
                'measures' => $measures,
            ];
        }

        // Now stream through each <Product> and its <Item> children
        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Product') {
                $productNode = $reader->expand();
                $domProd = new \DOMDocument;
                $domProd->preserveWhiteSpace = false;
                $domProd->appendChild($domProd->importNode($productNode, true));
                $xpath = new \DOMXPath($domProd);

                $parentId = (int) $productNode->getAttribute('ID');
                $parentUrl = $productNode->getAttribute('URL');
                $parentCode = collect(explode('/', $parentUrl))->last();

                foreach ($xpath->query('//Item') as $itemNode) {
                    $skuId = (int) $itemNode->getAttribute('ID');
                    $skuUrl = $itemNode->getAttribute('URL');
                    $skuCode = collect(explode('/', $skuUrl))->last();
                    $productName = 'Unnamed';
                    $attributes = [];

                    // Data → attributes
                    foreach ($itemNode->getElementsByTagName('Data') as $dataNode) {
                        $attrId = $dataNode->getAttribute('AttributeID');
                        $measureId = $dataNode->getAttribute('MeasureID');
                        $val = null;

                        if ($node = $dataNode->getElementsByTagName('ValueCharacter')->item(0)) {
                            $val = $node->nodeValue;
                        } elseif ($node = $dataNode->getElementsByTagName('ValueLongText')->item(0)) {
                            $val = $node->nodeValue;
                        } elseif ($node = $dataNode->getElementsByTagName('ValueNumeric')->item(0)) {
                            $val = $node->nodeValue;
                        }

                        if ($attrId && $val !== null) {
                            $measureName = $attributeMeta[$attrId]['measures'][$measureId] ?? null;
                            $groupName = $attributeMeta[$attrId]['group'] ?? null;

                            $valueWithUnit = ($measureId !== '0' && $measureName)
                                ? "{$val} {$measureName}"
                                : $val;

                            $attributes[] = [
                                'attribute_id' => (int) $attrId,
                                'attribute_value' => $valueWithUnit,
                                'group' => $groupName,
                            ];

                            if ((int) $attrId === 15) {
                                $productName = $val;
                            }
                        }
                    }

                    // Assets for this SKU
                    $mainImage = null;
                    $additionalImages = [];
                    $documents = [];

                    foreach ($itemNode->getElementsByTagName('AssetID') as $assetIdNode) {
                        $context = $assetIdNode->getAttribute('Context');
                        $assetId = trim($assetIdNode->nodeValue);
                        $url = $assets[$assetId]['url'] ?? null;
                        if (! $url) {
                            continue;
                        }

                        if ($context === 'Primary Image') {
                            $mainImage = $url;
                        } elseif ($context === 'Secondary Image') {
                            $additionalImages = [$url];
                        } elseif ($context === 'Downloads') {
                            $documents[] = [
                                'url' => $url,
                                'name' => $assets[$assetId]['name'] ?? null,
                            ];
                        }
                    }

                    yield [
                        'id' => $skuId,
                        'parent_id' => $parentId,
                        'parent_product_code' => $parentCode,
                        'product_code' => $skuCode,
                        'product_name' => $productName,
                        'attributes' => $attributes,
                        'main_image' => $mainImage,
                        'additional_images' => $additionalImages,
                        'documents' => $documents,
                    ];
                }
            }
        }

        $reader->close();
    }
}
