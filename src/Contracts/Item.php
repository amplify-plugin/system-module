<?php

namespace Amplify\System\Contracts;

use Amplify\Frontend\Store\StoreDataBus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Item
{
    public static function loadSingle() {}

    private ?Model $model;

    private $easyAskResult;

    private $ERP;

    private $attributes = [
        'id' => null,
        'seopath' => null,
        'link' => null,
        'image' => null,
        'thumbnails' => [],
        'name' => null,
        'price' => null,
        'discount_price' => null,
        'code' => null,
        'description' => null,
        'short_description' => null,
        'exists_on_campaign' => null,
        'exists_on_favourite' => null,
        'favourite' => null,
        'campaign' => null,
        'uom' => null,
        'has_sku' => null,
        'skus' => [],
        'allow_backorder' => null,
        'inventory' => [],
        'manufacturer' => null,
    ];

    public function __construct()
    {
        $store = StoreDataBus::init();

        $this->easyAskResult = $store->eaProductsData;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function getEasyAskResponse(): mixed
    {
        return $this->easyAskResult;
    }

    public function id()
    {
        return $this->attributes['id'] ?? null;
    }

    public function seoPath()
    {
        return $this->attributes['seopath'] ?? null;
    }

    public function link()
    {
        return $this->attributes['link'];
    }

    public function image()
    {
        return $this->attributes['image'];
    }

    public function thumbnails(): Collection
    {
        return collect($this->attributes['thumbnails']);
    }

    public function price($formatted = false)
    {
        return ($formatted)
            ? price_format($this->attributes['price'])
            : filter_var($this->attributes['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public function name()
    {
        return $this->attributes['name'];
    }

    public function discountPrice($formatted = false)
    {
        return ($formatted)
            ? price_format($this->attributes['discount_price'])
            : filter_var($this->attributes['discount_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public function code()
    {
        return $this->attributes['code'];
    }

    public function shortDescription()
    {
        return $this->attributes['short_description'];
    }

    public function description()
    {
        return $this->attributes['description'];
    }

    public function existsOnFavourite(): bool
    {
        return (bool) $this->attributes['exists_on_favourite'];
    }

    public function favouriteList()
    {
        return $this->attributes['favourite'];
    }

    public function existsOnCampaign(): bool
    {
        return (bool) $this->attributes['exists_on_campaign'];

    }

    public function campaign()
    {
        return $this->attributes['campaign'];
    }

    public function uom()
    {
        return $this->attributes['uom'];

    }

    public function hasSku(): bool
    {
        return (bool) $this->attributes['has_sku'];
    }

    public function skus()
    {
        return collect($this->attributes['skus']);
    }

    public function allowBackOrder(): bool
    {
        return (bool) $this->attributes['allow_backorder'];
    }

    public function inventory()
    {
        return $this->attributes['inventory'];
    }

    public function manufacturer()
    {
        return $this->attributes['manufacturer'];
    }
}
