<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class JDMChunkRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'JDM:chunkRelations {dir=chunk} {collection=relations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prépare relations pour mongodb restore';

    /**
     * La taille d'un block de données (lignes) à envoyer à mongo
     * 
     * @var integer
     */
    private $block = 100000;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->argument('dir');
        $path = rtrim($path, '/') . '/';

        if (!is_dir($path)) {
            $this->error("Le répertoire $path n'existe pas");
            return;
        }
        $databaseName = 'jdm';
        $collection   = $this->argument('collection');

        $mongo = new \MongoDB\Client();

        $db         = $mongo->selectDatabase($databaseName);
        $collection = $db->selectCollection($collection);
        $count      = $collection->count();

        $bar = $this->output->createProgressBar($count);

        //Scan du répertoire
//        $files = glob("$path*.json");
//        $nums  = array_map(function($e) {
//            preg_match('#\d+#', $e, $matches);
//            return (int) $matches[0];
//        }, $files);
//            $data = $collection->find(['_id' => ['$gte' => $_id]], ['skip' => $nb, 'limit' => $block]);
        $block = $this->block;
//        $page  = empty($nums) ? 0 : max($nums) + 1;
//        $nb    = $page * $block;

        $start = time();
        $_id   = -1;
        $nb    = 0;
        $data  = [];
        $page  = 0;

        while (true) {
            $data = $collection->find(['_id' => ['$gt' => $_id]], ['_id' => SORT_ASC, 'limit' => $block])->toArray();

            if (empty($data))
                break;
            
            $_id      = $data[count($data) - 1]['_id'];
            $filePath = "$path$page.json";
            $page++;
            file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_SLASHES));
            unset($data);
            $bar->advance($block);
        }
//        while (true) {
////            $data = $collection->find(['_id' => ['$gte' => $_id]], ['skip' => $nb, 'limit' => $block]);
//            $nb += $block;
//
//            if ($data === []) {
//                return;
//            }
//            $filePath = "$path$page.json";
//            file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_SLASHES));
//            $bar->setProgress($nb);
//            unset($data);
//        }
        $time = time() - $start;
        $this->line("\nComplete in $time sec");
    }
}