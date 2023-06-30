<?php

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Dimension;

class GA4AffiliateData extends GA4Client {

    use DataJoin;
    
    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ str_replace(
                [
                    'customEvent:data_bmaff_trackingid',
                    'customEvent:data_bmaff_'
                ],[ 
                    'tracking_id',
                    ''
                ], $dimensionHeader->getName() ) ] = $idx;    
        }

        return $dimensions;
    }

    protected function getEventMap( $event ) {

        return str_replace(
                [
                    'BM View',
                    'BM Click'
                ],[ 
                    'bm_views',
                    'bm_clicks'
                ], 
                $event);
    }


    /**
     * Prende i dati di view e click di una property 
     * per un giorno specifico
     *
     * @param string $date
     * @param string $propertyId
     * @return array
     */
    public function getViewClickData( $propertyId = '295858603', $date = 'yesterday' ) : array {

        $viewDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'pagePath' ]), 
            new Dimension([ 'name' => 'customEvent:data_bmaff_postid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),            
        ];

        $viewChunkedDimensions = [

            array_merge( $viewDimensions, [                
                new Dimension([ 'name' => 'customEvent:data_bmaff_subject' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_program' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_platform' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_filone' ])
            ]),

            array_merge( $viewDimensions, [
                new Dimension([ 'name' => 'customEvent:data_bmaff_domain' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]), 
                new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ])
            ]),

            array_merge( $viewDimensions, [                                                
                new Dimension([ 'name' => 'customEvent:data_bmaff_format' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_alias' ]), 
                                            
            ])
        ];


        $clickDimensions =  [
            new Dimension([ 'name' => 'Date' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_postid' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),            
        ];

        
        $viewRowsPartials = array_map( fn( $dimensions ) => $this->getData( $propertyId, $date, $dimensions, 'BM View'), $viewChunkedDimensions);

        $viewRows = array_shift($viewRowsPartials);

        $viewRows = array_reduce( 
            $viewRowsPartials, 
            fn( $rows, $partial ) => $this->leftJoin( $rows, $partial, [ 'Date', 'tracking_id', 'bm_views', 'pagePath', 'postid' ], [ 'format' => '', 'custom' => '' ] ),
            $viewRows 
        );


        /// questa parte si potrà anche rimuovere una volta stabilizzati i postid
        $viewRows = $this->checkPostIds( $viewRows );

        $clickRows = $this->getData( $propertyId, $date, $clickDimensions, 'BM Click' );

        $blend = $this->leftJoin( $viewRows, $clickRows, [ 'Date', 'tracking_id', 'postid' ], ['bm_clicks' => 0] );

        return $blend;
    }
    

    protected function checkPostIds( $rows ) {

        $filteredRows = $this->indexOn( $rows,  ['Date', 'tracking_id', 'bm_views', 'pagePath' ] );
        
        $indexedRows  = $this->indexOn( $rows,  ['Date', 'tracking_id', 'bm_views', 'pagePath', 'postid' ] );

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


    protected function printrows( $rows, $num) {

        echo "\n".implode(' ', array_keys($rows[0] ))."\n";

        foreach( array_splice($rows,0,$num) as $row ) {

            echo "\n".implode(' ', $row )."\n";

        }
    }
}