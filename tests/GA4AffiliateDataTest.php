<?php

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;

use Blazemedia\Ga4AffiliateData\GA4AffiliateData;
use Blazemedia\Ga4AffiliateData\GA4TrafficData;



/// Service account credentials in JSON format
/// poi lo mettiamo da env
define('KEY_FILE_LOCATION', __DIR__ . '/google_credentials/ga_fetcher_composed-slice-349709-ed3cff527c69.json');
//define('PROPERTY_ID', '317758145'); // GA4 PROPERTY ID di TELEFONINO.NET
define('PROPERTY_ID', '295858603'); // TEST OMNIA GA4
        

final class GA4AffiliateDataTest extends TestCase {

    protected $client;
    protected $pvclient;

    protected function setUp(): void {

        parent::setUP();

        $this->client   = new GA4AffiliateData( KEY_FILE_LOCATION );
        $this->pvclient = new GA4TrafficData( KEY_FILE_LOCATION );
    }

    /** @test */
    public function can_get_data() {

        $data = $this->client->getViewClickData( '2023-02-15' );

        //var_dump($data);

        $this->assertIsArray( $data );
    }

    /** @test */
    public function can_get_page_views() {

        $data = $this->pvclient->getPageViews( '2023-02-15' );

         var_dump($data);

        $this->assertIsArray( $data );
    }



}
