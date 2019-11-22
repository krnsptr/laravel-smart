<?php


namespace Deiucanta\Smart;


class JoinModel extends Model
{
    protected $smartRelationship = null;
    public $timestamps = false;

    public function __construct($relationship = null)
    {
        if (!$relationship) {
            return;
        }

        $this->smartRelationship = $relationship;
        $this->table = $relationship['joinTable'];

        parent::__construct([]);
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields[] = Field::make($this->smartRelationship['parentKey'])->belongsTo($this);
        $fields[] = Field::make($this->smartRelationship['relatedKey'])->belongsTo($this);

        return $fields;
    }

    public function getModelName()
    {
        return ucfirst($this->table).'Model';
    }

    public function __call($name, $arguments)
    {
        if ($name === $this->smartRelationship['parentKey'])
        {
            return $this->belongsTo($this->smartRelationship['parentModel']);
        }
        else if ($name === $this->smartRelationship['relatedKey'])
        {
            return $this->belongsTo($this->smartRelationship['relatedModel']);
        }
        return null;
    }
}
