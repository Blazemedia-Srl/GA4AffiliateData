<?php

namespace Blazemedia\Ga4AffiliateData;

use Blazemedia\Ga4AffiliateData\Contract\AbstractTrafficData;
use Blazemedia\Ga4AffiliateData\Utils\DataJoin;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\FilterExpressionList;
use Google\Analytics\Data\V1beta\Metric;

class PaidCampaignTrafficData extends GA4Client
{
    protected array $defaultFields = ['programs' => '', 'subjects' => '', 'type' => '', 'revenuestreams' => '', 'alias' => '', 'author' => '', 'custom' => ''];

    use DataJoin;

    protected function getDimensionsMap($dimensionHeaders) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[str_replace('customEvent:bmaff_page_', '', $dimensionHeader->getName())] = $idx;
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
    public function getPageViews($propertyId = '295858603', $date = 'yesterday', string $path = '') {

        $pageViewDimensions = [
            new Dimension(['name' => 'Date']),
            new Dimension(['name' => 'pagepath']),
            new Dimension(['name' => 'eventName']),
            new Dimension(['name' => 'sessionDefaultChannelGroup']),
            
        ];

        $viewRows = $this->getDataCampaign($propertyId, $date, $pageViewDimensions, 'page_view', $path);

        return $viewRows;
    }

    /**
     * Ritorna i dati di un certo evento
     *
     * @param string $propertyId - stream GA4 da cui estrarre i dati
     * @param string $eventName  - nome dell'evento
     * @param array  $dimensions  - campi da estrarre ( array di max 9 string )
     * @param string $date       - data da considerare
     * @return array
     */
    public function getDataCampaign( string $propertyId, $date = 'yesterday', array $dimensions = [], string $eventName = '', string $path = '') {

        $params = [

            'property' => "properties/{$propertyId}",
            
            'dateRanges' => [ new DateRange([ 'start_date' => $date, 'end_date' => $date ]) ],

            'metrics'    => [ new Metric([ 'name' => 'eventCount' ]) ],
            
            'dimensions' => $dimensions,
            
            'limit' => 100000,

            'dimensionFilter' => new FilterExpression([
                'and_group' => new FilterExpressionList([
                    'expressions' => [
                        new FilterExpression([
                            'filter' => new Filter([
                                'field_name'    => 'eventName',
                                'string_filter' => new StringFilter( [ 'value' => $eventName, 'match_type' => MatchType::EXACT ] )
                            ])
                        ]),

                        new FilterExpression([
                            'filter' => new Filter([
                                'field_name'    => 'sessionDefaultChannelGroup',
                                'string_filter' => new StringFilter( [ 'value' => 'paid search' , 'match_type' =>  MatchType::EXACT ])
                            ])
                        ]),

                        new FilterExpression([
                            'filter' => new Filter([
                                'field_name'    => 'pagepath',
                                'string_filter' => new StringFilter( [ 'value' => $path , 'match_type' => MatchType::CONTAINS ])
                            ])
                        ])
                    ]
                ])
            ])
        ];
            
             
        /// prende i dati da GA4
        $response = $this->client->runReport( $params );

        /// prende i nomi delle colonne ( le ripulisce da customEvent:data_bmaff_ ) e li associa agli indici
        /// è un elenco di  [ nome_colonna => indice ]
        $dimensions = $this->getDimensionsMap( $response->getDimensionHeaders() );
        
        /// cicla le righe
        $rows = [];
        foreach( $response->getRows() as $row ) {

            $rows[] = array_reduce( array_keys( $dimensions ), function( $dataRow, $dimension ) use( $row, $dimensions ) {
 
                /// assengna ogni valore alla dimensione corrispondente
                $dataRow[ $dimension ] = $row->getDimensionValues()[ $dimensions[ $dimension ] ]->getValue();

                /// e restituisce la riga
                return $dataRow;

            }, [ $this->getEventMap( $eventName ) => $row->getMetricValues()[0]->getValue() ] );
        }

        return $rows;
    }
}
