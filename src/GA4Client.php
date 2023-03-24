<?php 

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;


class GA4Client {

    protected $client;

    function __construct( protected string $keyPath ) {

        $this->client = $this->getClient();
    }
    

    /**
     * Make an API call.
     * reference : https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema?hl=en#dimensions
     * 
     * @param array $args
     */
    public function runReport( array $args ) {

        return $this->client->runReport( array_merge( [
            
            'property' => 'properties/295858603', // TEST OMNIA GA4
            
            'dateRanges' => [ new DateRange([ 'start_date' => 'yesterday', 'end_date' => 'yesterday' ]) ],

            'metrics'   => [ new Metric([ 'name' => 'eventCount' ]) ],

            'dimensionFilter' => new FilterExpression( [
                'filter' => new Filter([
                    'field_name'    => 'eventName',
                    'string_filter' => new StringFilter( [ 'value' => 'Page View'] )
                ])
            ]),

        ], $args ) );
    }

    
    /**
     * Istanzia un client per GA4
     *
     * @return BetaAnalyticsDataClient client
     */
    protected function getClient() {

        $credentials = file_get_contents( $this->keyPath );

        if( empty( $credentials ) ) throw new \ErrorException( 'Credentials file not found' );

        return new BetaAnalyticsDataClient([
            'credentials' => json_decode( $credentials, true )
        ]);
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
    public function getData( string $propertyId, string $eventName, array $dimensions, $date = 'yesterday' ) {

        /// prende i dati da GA4
        $response = $this->client->runReport( [

            'property' => "properties/{$propertyId}",
            
            'dateRanges' => [ new DateRange([ 'start_date' => $date, 'end_date' => $date ]) ],

            'dimensionFilter' => new FilterExpression( [
                'filter' => new Filter([
                    'field_name'    => 'eventName',
                    'string_filter' => new StringFilter( [ 'value' => $eventName ] )
                ])
            ]),

            'metrics'   => [ new Metric([ 'name' => 'eventCount' ]) ],
            
            'dimensions' => $dimensions        
        ]);
       
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

            }, [ $eventName => $row->getMetricValues()[0]->getValue() ] );
        }

        return $rows;
    }


    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ $dimensionHeader->getName() ] = $idx;    
        }

        return $dimensions;
    }

    
}