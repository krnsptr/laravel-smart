<?php

namespace Deiucanta\Smart;

use Exception;
use Illuminate\Validation\Rule;

class Field
{
    use FieldTypes, FieldRules;

    public $name;

    public $type;
    public $typeArgs = [];

    public $cast;
    public $rules = [];

    public $guarded = false;
    public $fillable = false;
    public $label = null;

    public $index = null;
    public $unique = null;
    public $primary = null;
    public $nullable = null;
    public $unsigned = null;

    public $belongsTo = null;
    public $belongsToMany = null;
    public $hasOne = null;
    public $hasMany = null;

    public $default = null;

    public $uniqueClosure;
    public $validateRawValue;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function make($name)
    {
        return new static($name);
    }

    public function type($type, $typeArgs = [])
    {
        $this->type = $type;
        $this->typeArgs = $typeArgs;

        return $this;
    }

    public function cast($cast)
    {
        $this->cast = $cast;

        return $this;
    }

    public function rule($rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function guarded()
    {
        $this->guarded = true;

        return $this;
    }

    public function fillable()
    {
        $this->fillable = true;

        return $this;
    }

    public function label(string $value)
    {
        $this->label = $value;

        return $this;
    }

    public function index()
    {
        $this->index = true;

        return $this;
    }

    public function unique($uniqueClosure = null)
    {
        $this->unique = true;
        $this->uniqueClosure = $uniqueClosure;

        return $this;
    }

    public function primary()
    {
        $this->primary = true;

        return $this;
    }

    public function default($default)
    {
        $this->default = $default;

        return $this;
    }

    public function nullable()
    {
        $this->nullable = true;
        $this->rule('nullable');

        return $this;
    }

    public function unsigned()
    {
        $this->unsigned = true;
        $this->rule('min:0');

        return $this;
    }

    public function belongsTo($model, $foreignKey=null, $otherKey=null)
    {
        $this->belongsTo = [
            'model' => $model,
            'foreignKey' => $foreignKey,
            'otherKey' => $otherKey
        ];

        return $this;
    }

    public function belongsToMany($model, $joinTable, $otherKey, $modelKey)
    {
        $this->belongsToMany = [
            'model' => $model,
            'joinTable' => $joinTable,
            'otherKey' => $otherKey,
            'modelKey' => $modelKey
        ];

        return $this;
    }

    /**
     * @param $model String - Full model name
     * @param $foreignKey String - Foreign key in the target model
     * @param $localKey String - The key to use for the foreign key: Ex: code, uuid
     * @return Field
     */
    public function hasOne($model, $foreignKey, $localKey)
    {
        $this->hasOne = [
            'model' => $model,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey
        ];

        return $this;
    }

    /**
     * @param $model String - Full model name
     * @param $foreignKey String - Foreign key in the target model
     * @param $localKey String - The key to use for the foreign key: Ex: code, uuid
     * @return Field
     */
    public function hasMany($model, $foreignKey, $localKey)
    {
        $this->hasMany = [
            'model' => $model,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey
        ];

        return $this;
    }

    public function validateRawValue()
    {
        $this->validateRawValue = true;

        return $this;
    }

    public function getValidationRules($model)
    {
        $rules = $this->rules;

        if ($this->unique) {
            $rules[] = $this->makeUniqueRule($model);
        }

        return $rules;
    }

    protected function makeUniqueRule($model)
    {
        $rule = Rule::unique($model->getTable(), $this->name);

        if ($model->getKey()) {
            $rule->ignore($model->getKey(), $model->getKeyName());
        }

        if ($this->uniqueClosure instanceof Closure) {
            $rule->where($this->uniqueClosure);
        }

        return $rule;
    }

    public function getSchemaData()
    {
        $output = [];

        if ($this->type === null) {
            throw new Exception("Field `{$this->name}` doesn't have a type.");
        }

        $output['type'] = $this->type;
        $output['typeArgs'] = $this->typeArgs;

        if ($this->index) {
            $output['index'] = $this->index;
        }
        if ($this->unique) {
            $output['unique'] = $this->unique;
        }
        if ($this->primary) {
            $output['primary'] = $this->primary;
        }

        if ($this->default) {
            $output['default'] = $this->default;
        }

        if ($this->nullable) {
            $output['nullable'] = $this->nullable;
        }
        if ($this->unsigned) {
            $output['unsigned'] = $this->unsigned;
        }

        if ($this->belongsTo) {
            $output['belongsTo'] = $this->belongsTo;
        }

        return $output;
    }
}
