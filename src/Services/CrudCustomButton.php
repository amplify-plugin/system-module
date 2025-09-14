<?php

namespace Amplify\System\Services;

class CrudCustomButton
{
    private string $name;

    private string $route;

    private string $stack;

    private string $icon = 'la la-question';

    private string $classes = 'btn btn-sm btn-link text-capitalize';

    public function __construct(string $name, string $route, ?array $attributes)
    {

        $this->name = $name;
        $this->route = $route;

        if ($attributes) {
            $this->assignAttributes($attributes);
        }
    }

    private function assignAttributes(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStack()
    {
        return $this->stack;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getButtonClasses()
    {
        return $this->classes;
    }
}
