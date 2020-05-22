<?php

namespace TibbsA\TranslatableWorkflowManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class TranslatableWorkflowManager
{
    static public function getManagedModels() : array
    {
        $models = TranslatableWorkflowManager::enumModels(app_path('Models'));

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

    static protected function enumModels($path)
    {
        $out = [];
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') continue;
            $filename = $path . '/' . $result;
            if (is_dir($filename)) {
                $out = array_merge($out, getModels($filename));
            }else{
                $out[] = (string) (Str::of($filename)->basename('.php'));
            }
        }
        return $out;
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


