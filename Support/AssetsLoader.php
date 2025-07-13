<?php

namespace Amplify\System\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AssetsLoader
{
    const TYPE_JS = 'js';

    const TYPE_CSS = 'css';

    const TYPE_HTML = 'html';

    const TYPE_AUTO = 'auto';

    const DEFAULT_GROUP = 'default';

    const REGEX_CSS = '/\.css/i';

    const REGEX_JS = '/\.js/i';

    private array $attributes = [];

    private array $collection;

    private array $presets;

    private array $config;

    public function __construct()
    {
        $this->config = Config::get('assets');

        $this->collection = [
            self::TYPE_CSS => [
                self::DEFAULT_GROUP => [],
                'plugin-style' => [],
                'template-style' => [],
                'custom-style' => [],
                'internal-style' => [],
            ],
            self::TYPE_JS => [
                self::DEFAULT_GROUP => [],
                'plugin-script' => [],
                'head-script' => [],
                'template-script' => [],
                'custom-script' => [],
                'internal-script' => [],
                'footer-script' => [],
            ],
            self::TYPE_HTML => [
                self::DEFAULT_GROUP => [],
            ],
        ];

        $this->attributes = [
            self::TYPE_CSS => [],
            self::TYPE_JS => [],
            self::TYPE_HTML => [],
        ];

        $this->presets = $this->config['presets'];

    }

    public function collection(): array
    {
        return $this->collection;
    }

    /**
     * Add asset resources to asset stack to template program
     *
     * closure will add the current request an input parameter
     *
     * @reserved CSS plugin-style, template-style, custom-style, internal-style
     * @reserved JS head-script, footer-script, plugin-script, template-script, custom-script, internal-script
     * @reserved HTML footer-content
     *
     * @param  string|array|\Closure  $asset
     */
    public function add($asset, string $type, string $group = self::DEFAULT_GROUP, array $attributes = []): void
    {
        if (is_array($asset)) {
            $this->resolveArray($asset, $type, $group, $attributes);
        } elseif (is_callable($asset)) {
            $this->resolveCallback($asset, $type, $group, $attributes);
        } elseif ($type === self::TYPE_CSS) {
            $this->resolveCSSAsset($asset, $group, $attributes);
        } elseif ($type === self::TYPE_JS) {
            $this->resolveJSAsset($asset, $group, $attributes);
        } elseif ($type === self::TYPE_HTML) {
            $this->resolveHTMLAsset($asset, $group, $attributes);
        } else {
            throw new InvalidArgumentException('Unknown asset type: '.$asset);
        }
    }

    /**
     * @param  null|string  $path
     */
    public function image($path = null): string
    {
        if (! empty($path)) {
            return ((strpos($path, 'http') !== false) || (strpos($path, 'data') !== false))
                ? $path
                : asset($path);
        }

        return asset(config('amplify.frontend.fallback_image_path'));
    }

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param  string|array  $group
     */
    public function js($group = self::DEFAULT_GROUP): HtmlString
    {
        $group = is_array($group) ? $group : [$group];

        $assets = $this->collectAssets(self::TYPE_JS, $group);

        $html = implode("\n", $assets);

        return $this->toHtmlString($html);

    }

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param  string|array  $group
     */
    public function css($group = self::DEFAULT_GROUP): HtmlString
    {
        $group = is_array($group) ? $group : [$group];

        $assets = $this->collectAssets(self::TYPE_CSS, $group);

        $html = implode("\n", $assets);

        return $this->toHtmlString($html);
    }

    /**
     * this function load all the resources added from assets
     * function with given group to filtered
     * if no group is passed will return the default group css
     *
     * @param  string|array  $group
     */
    public function html($group = self::DEFAULT_GROUP): HtmlString
    {
        $group = is_array($group) ? $group : [$group];

        $assets = $this->collectAssets(self::TYPE_HTML, $group);

        $html = implode("\n", $assets);

        return $this->toHtmlString($html);
    }

    /**
     * @return void
     */
    private function resolveCallback(\Closure $asset, string $type = self::TYPE_AUTO, string $group = self::DEFAULT_GROUP, array $attributes = [])
    {
        $assets_func = call_user_func($asset, request());

        if (is_array($assets_func)) {
            foreach ($assets_func as $a) {
                $this->add($a, $type, $group, $attributes);
            }
        } elseif (is_string($assets_func)) {
            $this->add($assets_func, $type, $group, $attributes);
        } else {
            throw new InvalidArgumentException('Unknown asset type: '.$assets_func);
        }
    }

    /**
     * @return void
     */
    private function resolveArray(array $asset, string $type = self::TYPE_AUTO, string $group = self::DEFAULT_GROUP, array $attributes = [])
    {
        foreach ($asset as $a) {
            if (is_array($a)) {
                $this->add($a['url'], ($a['type'] ?? $type), ($a['group'] ?? $group), $attributes);
            } else {
                $this->add($a, $type, $group, $attributes);
            }
        }
    }

    /**
     * @return void
     */
    private function resolveCSSAsset(string $asset, string $group, array $attributes = [])
    {
        // remote url
        if ($this->isRemote($asset)) {
            if (isset($this->collection[self::TYPE_CSS][$group])) {

                if (! in_array($asset, $this->collection[self::TYPE_CSS][$group])) {
                    $this->collection[self::TYPE_CSS][$group][] = $asset;
                }
            } else {
                $this->collection[self::TYPE_CSS][$group][] = $asset;
            }

            $this->attributes[self::TYPE_CSS][$asset] = $attributes;
        } // relative ulr
        elseif (preg_match(self::REGEX_CSS, $asset)) {
            $asset = asset($asset);
            if (isset($this->collection[self::TYPE_CSS][$group])) {

                if (! in_array($asset, $this->collection[self::TYPE_CSS][$group])) {
                    $this->collection[self::TYPE_CSS][$group][] = $asset;
                }
            } else {
                $this->collection[self::TYPE_CSS][$group][] = $asset;
            }

            $this->attributes[self::TYPE_CSS][$asset] = $attributes;
        } // plain css
        else {
            $this->collection[self::TYPE_CSS][$group][] = "<style {$this->renderAttributes($attributes)}>\n{$asset}\n</style>";
        }
    }

    /**
     * @return void
     */
    private function resolveJSAsset(string $asset, string $group, array $attributes = [])
    {
        // remote url
        if ($this->isRemote($asset)) {
            if (isset($this->collection[self::TYPE_JS][$group])) {

                if (! in_array($asset, $this->collection[self::TYPE_JS][$group])) {
                    $this->collection[self::TYPE_JS][$group][] = $asset;
                }
            } else {
                $this->collection[self::TYPE_JS][$group][] = $asset;
            }

            $this->attributes[self::TYPE_JS][$asset] = $attributes;
        } // relative ulr
        elseif (preg_match(self::REGEX_JS, $asset)) {
            $asset = asset($asset);
            if (isset($this->collection[self::TYPE_JS][$group])) {

                if (! in_array($asset, $this->collection[self::TYPE_JS][$group])) {
                    $this->collection[self::TYPE_JS][$group][] = $asset;
                }
            } else {
                $this->collection[self::TYPE_JS][$group][] = $asset;
            }

            $this->attributes[self::TYPE_JS][$asset] = $attributes;
        } // plain css
        else {
            $this->collection[self::TYPE_JS][$group][] = "<script type='text/javascript' {$this->renderAttributes($attributes)} >\n{$asset}\n</script>";
        }
    }

    private function resolveHTMLAsset(string $asset, string $group, array $attributes = []): void
    {
        $html = Blade::render($asset);

        if (! in_array($html, $this->collection[self::TYPE_HTML][$group])) {

            $this->collection[self::TYPE_HTML][$group][] = $html;
        }
    }

    private function renderAttributes(array $attributes = []): string
    {
        $html = '';
        foreach ($attributes as $attribute => $value) {
            $html .= " {$attribute}='{$value}' ";
        }

        return $html;
    }

    private function isRemote(string $path): bool
    {
        return Str::startsWith($path, ['https://', 'http://', '://']);
    }

    private function collectAssets(string $bag, array $groups): array
    {
        $rendered = [];

        array_walk($groups, function ($group) use (&$bag, &$rendered) {

            $assets = [];

            if ($group != self::DEFAULT_GROUP) {
                if (isset($this->presets[$group])) {
                    $assets = array_merge($assets, $this->presets[$group]);
                }
            }

            if (isset($this->collection[$bag][$group])) {
                $assets = array_merge($assets, $this->collection[$bag][$group]);
            }

            array_walk($assets, function ($asset) use (&$bag, &$rendered) {
                $rendered[] = $this->renderAssets($bag, $asset);
            });
        });

        return $rendered;
    }

    /**
     * Render the asset paths into there proper link html
     * tag formatted output
     */
    private function renderAssets(string $bag, $asset): string
    {
        $attr_html = '';

        if (isset($this->attributes[$bag][$asset])) {
            $attr_html = $this->renderAttributes($this->attributes[$bag][$asset]);
        }

        switch ($bag) {
            case self::TYPE_JS:
                return (stripos($asset, '<script') !== false)
                    ? $asset
                    : sprintf("<script type='text/javascript' src='%s' %s></script>", $asset, $attr_html);

            case self::TYPE_CSS:
                return (stripos($asset, '<style') !== false)
                    ? $asset
                    : sprintf("<link rel='stylesheet' type='text/css' href='%s' %s/>", $asset, $attr_html);

            case self::TYPE_HTML:
                return $asset;
            default:
                return '';
        }
    }

    /**
     * Return html content into encode safe laravel html string
     */
    private function toHtmlString($content): HtmlString
    {
        return new HtmlString($content);
    }
}
