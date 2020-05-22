<?php

namespace TibbsA\TranslatableWorkflowManager\Commands;

use Illuminate\Console\Command;
use App\Library\Translation\TranslationManager;

class ExportTranslations extends Command
{
    protected $tmgr = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature =
        'xlat:export
         {model* : The name of one or models to be exported (or "all")}
         {--O|output-dir=translations : Path to save exported docs (default=translations)}
         {--s|seperate-files : Output each entity type as a separate file}
         {--r|reflang=en : Reference (source) language}
         {--t|targetlang=fr : Target language}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export records which require translation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TranslationManager $tmgr)
    {
        parent::__construct();
        $this->tmgr = $tmgr;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // build list of possible models among those that have been
        // requested
        $models = $this->tmgr->getManagedModels();

        $requested_models = $this->argument('model');
        if ($requested_models[0] == 'all')
            $requested_models = $models;
        else {
            $failed = false;
            foreach ($requested_models as $rm) {
                if (!in_array($rm, $models)) {
                    $this->error ('Requested model "' . $rm . '" is not eligible for export');
                    $failed = true;

                }
            }

            if ($failed) {
                $this->info ('Eligible models: ' . implode (', ', $models));
                return;
            }
        }

        if ($this->option('seperate-files')) {
            foreach ($requested_models as $model) {
                // export both the source and target languages
                foreach ([$this->option('reflang'),
                          $this->option('targetlang')] as $lang) {
                    $export_data = $this->getExportData($model, $lang);

                    // Skip if there is in fact nothing to export
                    if ($export_data !== false) {
                        $export_filename = $this->option('output-dir') . '/' . date('Y-m-d_His') . '_' . $model . '_' . $lang . '.json';
                        file_put_contents($export_filename, json_encode($export_data));
                    }
                }
            }
        } else {
            // export both the source and target languages
            foreach ([$this->option('reflang'),
                      $this->option('targetlang')] as $lang) {
                $export_data = [];
                foreach ($requested_models as $model) {
                    $model_export_data = $this->getExportData($model, $lang);
                    if ($model_export_data !== false)
                        $export_data = array_merge($export_data, $model_export_data);
                }

                // Skip if there is in fact nothing to export
                if (count($export_data) > 0) {
                    $export_filename = $this->option('output-dir') . '/' . date('Y-m-d_His') . '_' . $lang . '.json';
                    file_put_contents($export_filename, json_encode($export_data));
                }
            }
        }

    }

    protected function getExportData ($modelName, $lang)
    {
        $entityPath = '\\App\\Models\\' . $modelName;

        $export_data[$entityPath] = [];

        $entities = $entityPath::where('translation_required', TRUE)->get();

        foreach ($entities as $entity) {
            $entity_export = [];
            foreach ($entity->getTranslatableAttributes() as $field) {
                $value = $entity->getTranslationWithoutFallback($field, $lang);
                if ($value)
                    $entity_export [$field] = $value;
            }

            // If we didn't export any fields (no contents) then skip
            // this entity
            if (count($entity_export) == 0)
                continue;

            $identifier = '#' . $entity->id .
                ':' . $this->tmgr->getTranslatableFieldsHash($entity, 'en');

            $export_data[$entityPath][$identifier] = $entity_export;
        }

        if (count($export_data[$entityPath]) == 0) {
            $this->info('Model "' . $modelName . '": nothing to export (language=' . $lang . ')');
            return false;
        } else {
            $this->info('Model "' . $modelName . '": exported ' . count($export_data[$entityPath]) . ' items (language=' . $lang . ')');
            return $export_data;
        }
    }
}
