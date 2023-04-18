<?php

namespace Blazemedia\Ga4AffiliateData;

use GPBMetadata\Google\Api\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;

class GA4TrafficData extends GA4Client {

    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ str_replace('customEvent:data_bmaff_', '', $dimensionHeader->getName() ) ] = $idx;    
        }

        return $dimensions;
    }


    /**
     * Prende i dati di view e click di una property 
     * per un giorno specifico
     *
     * @param string $date
     * @param string $propertyId
     * @return array
     */
    public function getPageViews( $date = 'yesterday' , $propertyId = '295858603' ) {

        $pageViewDimensions = [
            new Dimension([ 'name' => 'Date' ]),            
            new Dimension([ 'name' => 'pagePath' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_domain' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ])
        ];
        
        return $this->getData( $propertyId, 'page_view',  $pageViewDimensions,   $date ) ;
        
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
