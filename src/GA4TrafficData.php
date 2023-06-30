<?php

namespace Blazemedia\Ga4AffiliateData;

use GPBMetadata\Google\Api\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;

class GA4TrafficData extends GA4Client {

    use DataJoin;

    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ str_replace('customEvent:bmaff_page_', '', $dimensionHeader->getName() ) ] = $idx;    
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
    public function getPageViews( $propertyId = '295858603', $date = 'yesterday' ) {

        $pageViewDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'pagePath' ]),            
            new Dimension([ 'name' => 'customEvent:bmaff_page_domain'    ]),
            new Dimension([ 'name' => 'customEvent:bmaff_page_postid'    ]),
            new Dimension([ 'name' => 'customEvent:bmaff_page_author'    ]), 
            new Dimension([ 'name' => 'customEvent:bmaff_page_alias'     ]),            
            new Dimension([ 'name' => 'customEvent:bmaff_page_type'      ]),            
            new Dimension([ 'name' => 'customEvent:bmaff_page_custom'    ])            
        ];

        $viewRows = $this->getData( $propertyId,  $date,  $pageViewDimensions, 'page_view' ) ;

        /// questa parte si potrà anche rimuovere una volta stabilizzati i postid
        $viewRows = $this->checkPostIds( $viewRows );

        return $viewRows;        
    }

    
    
    protected function checkPostIds( $rows ) {

        $filteredRows = $this->indexOn( $rows,  ['Date', 'page_view', 'pagePath' ] );
        
        $indexedRows  = $this->indexOn( $rows,  ['Date', 'page_view', 'pagePath', 'postid' ] );

        foreach( array_keys( $filteredRows ) as $filteredIndex ) {

            foreach( $indexedRows as $index => $row ) {

                if( str_contains( $filteredIndex, $index ) ) {

                    if( !in_array( $row['postid'], [ 0, '', '(not set)' ] ) ) {

                        $filteredRows[ $filteredIndex ] = $row;
                    };
                }                
            }
        }

        return array_values($filteredRows);
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

    // protected function getEventMap( $event ) {

    //     return str_replace(
    //             [
    //                 'BM View',
    //                 'BM Click'
    //             ],[ 
    //                 'bm_views',
    //                 'bm_clicks'
    //             ], 
    //             $event);
    // }



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
