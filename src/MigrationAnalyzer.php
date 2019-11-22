<?php

namespace Deiucanta\Smart;

class MigrationAnalyzer
{
    /**
     * @param array $baseModels Models from configuration
     * @return array Models including join models generated using $baseModels
     */
    public function scan($baseModels)
    {
        $data = [];
        foreach ($baseModels as $baseModel) {
            $instance = new $baseModel();
            $tableName = $instance->getTable();
            $data[$tableName] = [
                'model' => get_class($instance),
                'modelArgs' => [],
                'fields' => $this->getFieldsWithSchemaData($instance)
            ];

            // Add JoinModels to data
            $fields = $instance->getSmartFields();
            foreach ($fields as $field) {
                if ($field->belongsToMany) {
                    $joinModel = new JoinModel($field->belongsToMany);
                    $data[$joinModel->getTable()] = [
                        'model' => get_class($joinModel),
                        'modelArgs' => $field->belongsToMany,
                        'fields' => $this->getFieldsWithSchemaData($joinModel)
                    ];
                    unset($data[$tableName]['fields'][$field->name]);
                }
            }
        }

        return $data;
    }

    protected function getFieldsWithSchemaData($model)
    {
        $data = [];
        $fields = $model->getSmartFields();
        foreach ($fields as $field) {
            $fieldName = $field->name;
            $data[$fieldName] = $field->getSchemaData();
        }

        return $data;
    }

    public function diff($oldData, $newData)
    {
        $created = $updated = $deleted = [];

        foreach ($newData as $table => $modelData) {
            if (isset($oldData[$table]) === false) {
                $created[$table] = $this->modelDiff([], $modelData['fields']);
            } else {
                $diff = $this->modelDiff($oldData[$table]['fields'], $newData[$table]['fields']);
                if ($diff) {
                    $updated[$table] = $diff;
                }
            }
        }

        foreach ($oldData as $table => $modelData) {
            if (isset($newData[$table]) === false) {
                foreach ($oldData[$table]['fields'] as $fieldName => $data) {
                    unset($oldData[$table]['fields'][$fieldName]['belongsTo']);
                    unset($oldData[$table]['fields'][$fieldName]['belongsToMany']);
                }
                $deleted[$table] = $this->modelDiff($oldData[$table]['fields'], $newData[$table]['fields'] ?? $oldData[$table]['fields']);
            }
        }

        $result = [];

        if (count($created)) {
            $result['created'] = $created;
        }
        if (count($updated)) {
            $result['updated'] = $updated;
        }
        if (count($deleted)) {
            $result['deleted'] = $deleted;
        }

        return count($result) ? $result : null;
    }

    protected function modelDiff($oldFields, $newFields)
    {
        $created = $updated = $deleted = [];

        foreach ($newFields as $name => $field) {
            if (isset($oldFields[$name]) === false) {
                $created[$name] = $field;
            } else {
                $diff = $this->fieldDiff($oldFields[$name], $field);
                if ($diff) {
                    $updated[$name] = $diff;
                }
            }
        }

        foreach ($oldFields as $name => $field) {
            if (isset($newFields[$name]) === false) {
                $deleted[$name] = $oldFields[$name];
            }
        }

        $result = [];

        if (count($created)) {
            $result['created'] = $created;
        }
        if (count($updated)) {
            $result['updated'] = $updated;
        }
        if (count($deleted)) {
            $result['deleted'] = $deleted;
        }

        return count($result) ? $result : null;
    }

    protected function fieldDiff($oldField, $newField)
    {
        ksort($oldField);
        ksort($newField);

        if ($oldField === $newField) {
            return;
        }

        $output = [];

        if (isset($newField['type'])) {
            $output['type'] = $newField['type'];
        }
        if (isset($newField['typeArgs'])) {
            $output['typeArgs'] = $newField['typeArgs'];
        }

        if (isset($newField['default'])) {
            $output['default'] = $newField['default'];
        }
        if (isset($newField['nullable'])) {
            $output['nullable'] = $newField['nullable'];
        }
        if (isset($newField['unsigned'])) {
            $output['unsigned'] = $newField['unsigned'];
        }

        if (isset($newField['belongsTo'])) {
            $output['belongsTo'] = $newField['belongsTo'];
        }

        if (isset($newField['index']) && !isset($oldField['index'])) {
            $output['index'] = true;
        }
        if (!isset($newField['index']) && isset($oldField['index'])) {
            $output['index'] = false;
        }

        if (isset($newField['unique']) && !isset($oldField['unique'])) {
            $output['unique'] = true;
        }
        if (!isset($newField['unique']) && isset($oldField['unique'])) {
            $output['unique'] = false;
        }

        if (isset($newField['primary']) && !isset($oldField['primary'])) {
            $output['primary'] = true;
        }
        if (!isset($newField['primary']) && isset($oldField['primary'])) {
            $output['primary'] = false;
        }

        return $output;
    }
}
