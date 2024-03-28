<?php

namespace Enjin\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class ModelResolver extends Model
{
    protected static $resolvedClassNamespace = [];
    private $model;

    /**
     * Create a new Eloquent model instance.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $class = static::resolveClassFqn();

        $this->model = new $class($attributes);

        $this->appends = $this->model->appends ?? [];
        $this->casts = $this->model->casts ?? [];
        $this->guarded = $this->model->guarded ?? ['*'];
        $this->attributes = $this->model->getAttributes() ?? [];
        $this->fillable = $this->model->getFillable() ?? [];
    }

    /**
     * Dynamically pass methods to the model.
     */
    public function __call($method, $parameters)
    {
        return $this->model->{$method}(...$parameters);
    }

    /**
     * Dynamically pass static methods to the model.
     */
    public static function __callStatic($method, $parameters)
    {
        $class = static::resolveClassFqn(static::class);

        return $class::$method(...$parameters);
    }

    /**
     * Resolve the class function.
     */
    public static function resolveClassFqn($classReference = null): string
    {
        if ($classReference == null) {
            $classReference = static::class;
        }

        $className = class_basename($classReference);
        $driverName = config('database.default');
        $driver = in_array($driverName, DB::supportedDrivers()) ? 'Laravel' : Str::ucfirst($driverName);

        if (!isset(static::$resolvedClassNamespace[$classReference])) {
            static::$resolvedClassNamespace[$classReference] = (new ReflectionClass($classReference))->getNamespaceName();
        }

        return static::$resolvedClassNamespace[$classReference] . "\\{$driver}\\{$className}";
    }
}
