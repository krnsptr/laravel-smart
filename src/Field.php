<?php

namespace Deiucanta\Smart;

use Closure;
use Exception;
use Illuminate\Support\Str;
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
    public $hidden = false;
    public $visible = false;
    public $label = null;

    public $index = null;
    public $unique = null;
    public $primary = null;
    public $nullable = null;
    public $unsigned = null;

    public $belongsTo = null;
    public $belongsToMany = null;

    public $useCurrent = null;
    public $rawDefault = null;

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
        if (is_string($rule) && !Str::contains($rule, 'regex:')) {
            $rule = explode('|', $rule);
        }

        $rules = is_array($rule) ? $rule : [$rule];

        foreach ($rules as $rule) {
            $this->rules[] = $rule;
        }

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

    public function hidden()
    {
        $this->hidden = true;

        return $this;
    }

    public function visible()
    {
        $this->visible = true;

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

    public function useCurrent()
    {
        $this->useCurrent = true;

        return $this;
    }

    public function rawDefault($rawValue)
    {
        $this->rawDefault = $rawValue;

        return $this;
    }

    public function belongsTo($model)
    {
        $this->unsignedInteger();

        $relationship = $model->{$this->name}();
        $this->belongsTo = [
            'model' => get_class($relationship->getRelated()),
            'foreignKey' => $relationship->getForeignKeyName()
        ];

        return $this;
    }

    public function belongsToMany($model)
    {
        $relationship = $model->{$this->name}();
        $this->belongsToMany = [
            'parentModel' => get_class($model),
            'relatedModel' => get_class($relationship->getRelated()),
            'joinTable' => $relationship->getTable(),
            'relatedKey' => $relationship->getRelatedPivotKeyName(),
            'parentKey' => $relationship->getForeignPivotKeyName()
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
        $rule = Rule::unique($model->getConnectionName() . '.' . $model->getTable(), $this->name);

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

        if ($this->type === null && !$this->belongsToMany) {
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

        if ($this->rawDefault) {
            $output['rawDefault'] = $this->rawDefault;
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

        if ($this->belongsToMany) {
            $output['belongsToMany'] = $this->belongsToMany;
        }

        if ($this->useCurrent) {
            $output['useCurrent'] = $this->useCurrent;
        }

        return $output;
    }
}
