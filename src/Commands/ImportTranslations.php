<?php

namespace TibbsA\TranslatableWorkflowManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TibbsA\TranslatableWorkflowManager\TranslatableWorkflowManager;

class ImportTranslations extends Command
{
    protected $tmgr;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature =
        'xlat:import
         {filename* : The name(s) of one or more JSON files to be imported}
         {--d|dry-run : Do not complete import; just verify it will work}
         {--r|reflang=en : Reference (source) language}
         {--t|targetlang=fr : Target language into which we are importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translated records';

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
        DB::beginTransaction();
        $hadErrors = false;
        $hadUpdates = false;

        try {
            foreach ($this->argument('filename') as $filename) {
                $import_data_raw = file_get_contents($filename);
                if ($import_data_raw === FALSE) {
                    throw new ErrorException('Error loading file "' . $filename . '"');
                }

                $import_data = json_decode($import_data_raw, TRUE);

                DB::connection()->enableQueryLog();

                $results = $this->doImportFromData(
                    $import_data,
                    $this->option('reflang'),
                    $this->option('targetlang')
                );

                DB::connection()->disableQueryLog();

                if (count($results['accepted']) > 0) {
                    $hadUpdates = true;
                    foreach ($results['accepted'] as $an => $ad) {
                        $this->info('Updating ' . count($ad) . ' instances of ' . $an . ':');
                        foreach ($ad as $identifier => $acceptData) {
                            $num = substr($identifier, 1, strpos($identifier, ':') - 1);
                            if (array_key_exists('title', $acceptData['data']))
                                $num .= ' (' . $acceptData['data']['title'] . ')';

                            $this->info ('- ' . $num);
                        }
                    }
                }

                if (count($results['rejected']) > 0) {
                    $hadErrors = true;
                    foreach ($results['rejected'] as $cn => $cd) {
                        $this->error('Unable to import ' . count($cd) . ' instances of ' . $cn . ':');
                        foreach ($cd as $identifier => $rejectData) {
                            $num = substr($identifier, 1, strpos($identifier, ':') - 1);
                            if (array_key_exists('title', $rejectData['data']))
                                $num .= ' (' . $rejectData['data']['title'] . ')';

                            $this->error ('- ' . $rejectData['reason'] . ': ' . $num);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            report($e);
            $this->error($e->getMessage());
            $this->info('Abandoning database updates and rolling back');
            DB::rollBack();
            return;
        }

        if ($this->option('dry-run')) {
            $this->info('Test mode - abandoning database updates and rolling back');
            DB::rollBack();
        } else if ($hadUpdates) {
            //DB::connection()->enableQueryLog();
            dump(DB::getQueryLog());

            if ($hadErrors ? ($this->confirm('Proceed with database updates notwithstanding these errors?')) : ($this->confirm('Apply database updates?'))) {
                DB::commit();
            }
            else
                DB::rollBack();
        } else {
            $this->info('No updates to apply');
            DB::rollBack();
        }
    }


    protected function doImportFromData (array $data, $srcLang, $targetLang) : array
    {
        // retrieve list of possible models
        $models = $this->tmgr->getManagedModels();
        $accepts = [];
        $rejects = [];

        foreach ($data as $modelName => $modelData) {
            $modelPath = null;
            foreach ($models as $model_candidate) {
                if ($modelName == '\\App\\Models\\' . $model_candidate)
                    $modelPath = $modelName;
            }

            // If we couldn't find a matching model, we might be trying to
            // import something the system can't support
            if (!$modelPath) {
                $rejects [$modelName] = [];
                foreach ($modelData as $mid => $mdd) {
                    $rejects [$modelName] [$mid] = [
                        'reason' => 'Unrecognized model "' . $modelName . '"',
                        'data' => $mdd
                    ];
                }

                // nothing more we can do here
                continue;
            }

            foreach ($modelData as $mid => $mdata) {
                // extract model ID number and hash
                $matches = [];
                if (!preg_match('/^#(\d+):([[:xdigit:]]{32})$/i', $mid, $matches)) {
                    $rejects [$modelName] [$mid] = [
                        'reason' => 'Item identifier malformed',
                        'data' => $mdata
                    ];

                    continue;
                }

                $idNum = $matches[1];
                $expectedHash = $matches[2];

                try {
                    $item = $modelPath::find($idNum);
                } catch (ModelNotFoundException $e) {
                    $rejects [$modelName] [$mid] = [
                        'reason' => 'Item not found in database',
                        'data' => $mdata
                    ];

                    continue;
                }

                if (
                    $expectedHash !=
                    $this->tmgr->getTranslatableFieldsHash($item, $srcLang)
                ) {
                    $rejects [$modelName] [$mid] = [
                        'reason' => 'Source translation has evolved',
                        'data' => $mdata
                    ];
                }

                foreach ($mdata as $fieldName => $fieldValue) {
                    $item->setTranslation($fieldName, $targetLang, $fieldValue);
                }

                // Only if we actually got an updated translation, i.e. some
                // field changed, do we clear the "translation required" flag
                if ($item->isDirty()) {
                    $item->translation_required = false;
                    $item->save();
                    $accepts [$modelName] [$mid] = [
                        'data' => $mdata,
                        'updated_item' => $item
                    ];
                } else {
                    $rejects [$modelName] [$mid] = [
                        'reason' => 'No changes submitted',
                        'data' => $mdata
                    ];
                }

            }
        }

        return [ 'accepted' => $accepts, 'rejected' => $rejects ];
    }
}
