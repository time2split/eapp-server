<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class JDMDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'JDM:loadDump {file=jdm-dump.txt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge le dump de JeuxDeMots';

    /**
     * La taille d'un block de données (lignes) à envoyer à mongo
     * 
     * @var integer
     */
    private $block = 4000;

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
        $path = $this->argument( 'file' );

        if ( !file_exists( $path ) )
            $this->error( "Le fichier $path n'existe pas" );

        $databaseName = 'jdm';
        $mongo        = new \MongoDB\Client();
        $mongo->dropDatabase( $databaseName );
        $db           = $mongo->selectDatabase( $databaseName );
        $size         = filesize( $path );
        $file         = fopen( $path, 'r' );

        $bar = $this->output->createProgressBar( $size );

        $coll = $db->selectCollection( 'relationTypes' );

        /*
         * 0 : rtid
         */
        $state = 0;
        $start = time();

        while ( true )
        {
            $ccoll = $coll;
            $lines = [];
            $read  = true;

            for ( $i = 0; $i < $this->block; )
            {
                if ( $read )
                    $l    = fgets( $file );
                else
                    $read = true;

                if ( $l === false )
                    break;

                $l = trim( $l );

                switch ( $state )
                {
                    case 0:

                        if ( strpos( $l, 'eid' ) === 0 )
                        {
                            $state++;
                            $coll = $db->selectCollection( 'nodes' );
                            $read = false;
                            break 2;
                        }
                        break;

                    case 1:

                        if ( strpos( $l, 'rid' ) === 0 )
                        {
                            $state++;
                            $coll = $db->selectCollection( 'relations' );
                            $read = false;
                            break 2;
                        }
                        break;
                }

                if ( empty( $l ) || $l[0] == '/' )
                    continue;

                //Ajout dans $lines
                $l = explode( '|', $l );

                foreach ( $l as $k => &$v )
                {
                    $tmp = explode( "=", $v, 2 );
                    $vv  = $tmp[1];
                    $kk  = $tmp[0];

                    if ( preg_match( '#^".*"$#', $vv ) )
                        $vv = utf8_encode( substr( $vv, 1, -1 ) );
                    else
                        $vv = (int) $vv;

                    $v = [$kk => $vv];
                }
                $l = array_merge( ...$l );
                $l['_id'] = $l[array_keys( $l )[0]];
                $lines[]  = $l;
                $i++;
            }
            $bar->setProgress( ftell( $file ) );
            $ccoll->insertMany( $lines );

            if ( $l === false || feof( $file ) )
            {
                break;
            }
        }
        $time = time() - $start;
        $this->line( "\nComplete in $time sec" );
        fclose( $file );
    }
}