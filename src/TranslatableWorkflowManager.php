<?php

namespace TibbsA\TranslatableWorkflowManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use TibbsA\TranslatableWorkflowManager\Helpers\ModelFinder;

class TranslatableWorkflowManager
{
    static public function getManagedModels() : array
    {
        $models = ModelFinder::hunt();

        $eligible_models = [];
        foreach ($models as $model_shortname) {
            $modelName = 'App\\Models\\' . $model_shortname;
            if (!class_exists($modelName))
                continue;
            
            $modelTraits = class_uses_recursive($modelName);
            if (!Arr::has($modelTraits, 'Spatie\\Translatable\\HasTranslations'
            ))
                continue;

            $varName = '\\' . $modelName;
            $tableName = (new $varName)->getTable();
            
            if (!Schema::hasColumn($tableName, 'translation_required'))
                continue;

            $eligible_models[] = $model_shortname;
        }

        return $eligible_models;
    }

    static public function getTranslatableFieldsHash(Model $entity, string $lang) : string
    {
        $temp = '';

        foreach ($entity->getTranslatableAttributes() as $field) {
            $value = $entity->getTranslationWithoutFallback($field, $lang);

            if ($value === null)
                $value = '';
            else
                $value = (string)$value;

            $temp .= $value;
        }

        return md5($temp);
    }
}


