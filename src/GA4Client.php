<?php 

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;


class GA4Client {

    private $client;

    function __construct( private string $keyPath, private string $propertyId ) {

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
            
            'property' => 'properties/' . $this->propertyId,
            
            'dateRanges' => [ new DateRange([ 'start_date' => 'yesterday', 'end_date' => 'yesterday' ]) ],

            'eventName' => 'Page View',
            'metrics'   => [ new Metric([ 'name' => 'eventCount' ]) ],
            

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
}