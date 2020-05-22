<?php
namespace TibbsA\TranslatableWorkflowManager\Helpers;

use Illuminate\Support\Str;

class ModelFinder
{
    /**
     * Identify all of the models that should be considered to be under
     * the management of the translation workflow manager.  
     * 
     * Currently this derives the list by hunting through the application's 
     * Models folder alone.
     *
     * Returns an array of relative class names (from the Model path)
     * that have been found.
     */
    static public function hunt() : array
    {
        $out = [];
        
        foreach (ModelFinder::enumModelFiles(app_path('Models')) as $filename)
        {
            $out[] = (string) Str::of($filename)
                ->replaceFirst(app_path('Models') . '/', '')
                ->replace('/', '\\')
                ->replaceLast('.php', '');
        }

        return $out;
    }
    
    static protected function enumModelFiles($path)
    {
        $out = [];
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') continue;

            $filename = $path . '/' . $result;
            if (is_dir($filename)) {
                $out = array_merge(
                    $out,
                    ModelFinder::enumModelFiles($filename)
                );
            } else {
                $out[] = $filename;
            }
        }
        
        return $out;
    }
}


