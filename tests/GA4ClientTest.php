<?php

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Blazemedia\Ga4AffiliateData\GA4Client;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\MetricType;


/// Service account credentials in JSON format
/// poi lo mettiamo da env
//define('KEY_FILE_LOCATION', __DIR__ . '/google_credentials/ga_fetcher_composed-slice-349709-ed3cff527c69.json');
//define('PROPERTY_ID', '317758145'); // GA4 PROPERTY ID di TELEFONINO.NET
//define('PROPERTY_ID', '295858603'); // TEST OMNIA GA4
        

final class GA4ClientTest extends TestCase {

    protected $client;

    protected function setUp(): void {

        parent::setUP();

        $this->client = new GA4AffiliateData();
    }

    /** @test */
    public function can_get_data() {

        $data = $this->client->getViewClickData( '2023-02-15' );

        var_dump($data);

        $this->assertIsObject( $this->client );
    }

    /**
     * Ritorna gli articoli più visti in un elenco di 
     * oggetti { slug, users }
     * 
     * @param integer $number - numero di articoli da visualizzare
     * @param string $start_date - data di inizio
     * @param string $end_date - data di fine
     * @param integer $threshold - soglia minima di utenti
     *
     * @return array
     */
    function getMostViewedPages( $number = 10, $start_date = '2daysAgo', $end_date = 'today', $threshold = 2000 ) {
        
        // Make an API call.
        // https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema?hl=en#dimensions

        $response = $this->client->runReport([
            
            'property' => 'properties/' . $this->propertyId,
            
            'dateRanges' => [ new DateRange([ 'start_date' => $start_date, 'end_date' => $end_date ]) ],
            'dimensions' => [ new Dimension([ 'name' => 'landingPage' ]) ],
            'metrics'    => [ new Metric([ 'name' => 'totalUsers' ]) ],
            'limit'      => $number

        ]);

        $mostViewed = [];

        /// getRows() è una sorta di iterator
        foreach( $response->getRows() as $row ) {

            /// la posizione [ 0 ] in dimesions è landingPage
            $path = $row->getDimensionValues() [ 0 ]->getValue();
            
            /// prende le parti del pathname dell'articolo ( esclude quelle vuote )
            $path_parts = array_filter( explode( '/', $path ), fn( $part )  => !empty($part) );

            /// prende lo slug
            $slug = array_pop( $path_parts );

            /// totalUsers è la metrica [ 0 ] 
            $totalUsers = $row->getMetricValues()[ 0 ]->getValue(); 

            /// se trova utenti  sopra la soglia desiderata
            if( $totalUsers > $threshold ) {

                /// ... aggiunge l'articolo con il numero di utenti
                $mostViewed[] = ( object ) [

                    'slug'  => $slug,
                    'users' => $totalUsers,
                ];
            }
        }

        return $mostViewed;
    }





    /**
     * Ritorna il numero di utenti che hanno visalizzato 
     * una certa pagina
     *
     * @param [type] $slug
     * @param string $start_date
     * @param string $end_date
     * @return object
     */
    function getUsers( $slug, $start_date = '2daysAgo', $end_date = 'today' ) {

        // Make an API call.
        // https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema?hl=en#dimensions

        $response = $this->client->runReport([
            
            'property' => 'properties/' . $this->propertyId,
            
            'dateRanges' => [ new DateRange([ 'start_date' => $start_date, 'end_date' => $end_date ]) ],
            'dimensions' => [ new Dimension([ 'name' => 'landingPage' ]) ],
            'metrics'    => [ new Metric([ 'name' => 'totalUsers' ]) ],
            'limit' => 1,
            'dimensionFilter' => new FilterExpression([

                'filter' => new Filter([ 
                    'field_name' => 'landingPage', 
                    'string_filter'=> new StringFilter( [ 
                        'value' => $slug,
                        'match_type' => MatchType::CONTAINS,
                        'case_sensitive' => false
                    ])
                ])
            ]),
        ]);

        $rows = $response->getRows();


        if( !isset( $rows[ 0 ] ) ) return (object) [

            'slug'  => $slug,
            'users' => 0
        ];

        $row = $rows[ 0 ];
        
        /// la posizione [ 0 ] è landingPage
        $path = $row->getDimensionValues() [ 0 ]->getValue();

        /// totalUsers è la metrica [ 0 ] 
        $totalUsers = $row->getMetricValues()[ 0 ]->getValue(); 

        return (object) [

            'slug'  => $path,
            'users' => $totalUsers 
        ];

    }

    

}
