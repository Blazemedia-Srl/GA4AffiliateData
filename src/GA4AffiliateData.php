<?php

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;


/// Service account credentials in JSON format
/// poi lo mettiamo da env
//define('KEY_FILE_LOCATION', __DIR__ . '/google_credentials/ga_fetcher_composed-slice-349709-ed3cff527c69.json');
//define('PROPERTY_ID', '317758145'); // GA4 PROPERTY ID di TELEFONINO.NET
// define('PROPERTY_ID', '295858603'); // TEST OMNIA GA4
        

final class GA4AffiliateData {

    protected $client;

    function __construct( $keyFilePath, $propertyId )  {
    
        $this->client = new GA4Client( $keyFilePath, $propertyId );
    }

    
    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ str_replace('customEvent:data_bmaff_', '', $dimensionHeader->getName() ) ] = $idx;    
        }

        return $dimensions;
    }


    public function getViewClickData(  $date = 'yesterday' ) {

        $viewPrimaryDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_domain' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_subject' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_program' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_platform' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_filone' ]),
        ];

        $viewSecondaryDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_format' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ]),
        ];

        $clickDimensions =  [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),            
        ];

        $viewPrimaryRows   = $this->getData( 'bm_views',  $viewPrimaryDimensions,   $date, $date ) ;
        $viewSecondaryRows = $this->getData( 'bm_views',  $viewSecondaryDimensions, $date, $date ) ;

        // var_dump(count($viewPrimaryRows), count($viewSecondaryRows) ); die;

        $viewRows = $this->leftJoin( $viewPrimaryRows, $viewSecondaryRows, [ 'Date', 'trackingid' ], [ 'format' => '', 'custom' => '' ] );
        
        $clickRows = $this->getData( 'bm_clicks', $clickDimensions, $date, $date );

        return $this->leftJoin( $viewRows, $clickRows, [ 'Date', 'trackingid' ], ['bm_clicks' => 0] );
        
    }


    /**
     * Effettua una left join tra due array,
     * inserisce campi vuoti se non trova corrispondenze
     *
     * @param array $left
     * @param array $right
     * @param array $onFields   - array dei campi sui queli effettuare la join
     * @param array $defaultRight - campi vuoti per le corrispondenze mancanti
     * @return array
     */
    public function leftJoin( array $left, array $right, array $onFields, $defaultRight = [] ) : array {

        $indexedLeft  = $this->indexOn( $left,  $onFields );
        $indexedRight = $this->indexOn( $right, $onFields );


        return array_map( fn( $index ) => isset( $indexedRight[$index] ) ? 
                                            array_merge( $indexedLeft[ $index ], $indexedRight[$index] ) : 
                                            array_merge( $indexedLeft[ $index ], $defaultRight ),

                          array_keys( $indexedLeft )
        );

    }


    /**
     * Crea una versione indicizzata dell'array in base ai campi stabiliti
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    protected function indexOn( array $data, array $fields  ) : array {

        return array_reduce( $data, function( $indexedData, $row ) use ( $fields ) {

            $index = array_reduce( $fields, fn( $index, $field ) => $index . $row[$field], '' );
            
            /// da rimuovere l'if utilizzato solo per i test sui dati, altrimenti da trattare come eccezione
            if( isset( $indexedData[ $index ] ) ) {

                echo "[!] attenzione duplicato {$index}\n" ;

                $indexedData[ $index ] ['bm_views'] += $row['bm_views'];

                return $indexedData;
            }
            
            $indexedData[ $index ] = $row;            
            
            return $indexedData;

        }, [] );

    }

    
    /**
     * Ritorna il numero di utenti che hanno visalizzato 
     * una certa pagina
     *
     * @param [type] $slug
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getData(string $eventName, array $dimensions, $start_date = '2daysAgo', $end_date = 'yesterday') {

        $response = $this->client->runReport( [
            
            'dateRanges' => [ new DateRange([ 'start_date' => $start_date, 'end_date' => $end_date ]) ],

            'eventName' => $eventName,

            'metrics'   => [ new Metric([ 'name' => 'eventCount' ]) ],
            
            'dimensions' => $dimensions        
        ]);
       
        $dimensions = $this->getDimensionsMap( $response->getDimensionHeaders() );
      
        $rows = [];
        foreach( $response->getRows() as $row ) {

            $rows[] = array_reduce( array_keys( $dimensions ), function( $dataRow, $dimension ) use( $row, $dimensions ) {
 
                $dataRow[ $dimension ] = $row->getDimensionValues()[ $dimensions[ $dimension ] ]->getValue();

                return $dataRow;

            }, [ $eventName => $row->getMetricValues()[0]->getValue() ] );
        }

        return $rows;
    }
}
