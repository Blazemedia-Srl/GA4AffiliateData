<?php

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;

use Blazemedia\Ga4AffiliateData\GA4AffiliateData;
use Blazemedia\Ga4AffiliateData\GA4TrafficData;

// Service account credentials in JSON format
// poi lo mettiamo da env
define('KEY_FILE_LOCATION', __DIR__ . '/google_credentials/ga_fetcher_composed-slice-349709-ed3cff527c69.json');
//define('PROPERTY_ID', '317758145'); // GA4 PROPERTY ID di TELEFONINO.NET
define('PROPERTY_ID', '317738872'); // GA4 PROPERTY ID di MELABLOG.IT
//define('PROPERTY_ID', '295858603'); // TEST OMNIA GA4

final class GA4AffiliateDataTest extends TestCase
{

    protected $client;
    protected $pvclient;
    protected $date;
    protected $property_id;

    protected function setUp(): void
    {

        parent::setUP();

        $this->date = '2023-10-23';
        $this->property_id = '317758145'; // oppure omnia PROPERTY_ID

        $this->client   = new GA4AffiliateData(KEY_FILE_LOCATION);
        $this->pvclient = new GA4TrafficData(KEY_FILE_LOCATION);
    }

    /** @test */
    public function can_get_data()
    {
        $data =  $this->client->getViewClickData($this->property_id, $this->date);

        $data = array_filter($data, fn($item) => $item['postid'] == 506599);

        $clicks = array_reduce($data, fn($sum, $item) => $sum + $item['bm_clicks']);

        foreach ($data as $item) {
            echo array_keys($item)[0] . " - " . implode(' ', $item) . "\n";
        }

        $this->assertIsArray($data);
    }

    /** @_test */
    public function can_get_page_views()
    {
        $data = $this->pvclient->getPageViews($this->property_id,  $this->date);

        echo "\nPageViews del {$this->date}\n\n";

        foreach ($data as $item) {
            echo implode(' ', $item) . "\n";
        }

        $this->assertIsArray($data);
    }
}
