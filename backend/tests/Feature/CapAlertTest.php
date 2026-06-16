<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use App\Models\StaffNotification;
use App\Models\Station;
use App\Models\Alert;
use App\Services\CapService;

class CapAlertTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test CapService generates valid CAP JSON format.
     */
    public function test_cap_service_generates_correct_json_schema(): void
    {
        $station = Station::create([
            'id' => 999,
            'name' => 'Test Station',
            'latitude' => 20.0,
            'longitude' => 99.0,
            'warning_datum_m' => 2.0,
            'critical_datum_m' => 3.0,
            'water_height_datum' => 1.5
        ]);

        $notification = StaffNotification::create([
            'station_id' => $station->id,
            'severity' => 'critical',
            'message' => 'ระดับน้ำสูงวิกฤตล้นตลิ่งเกินขีดจำกัด',
            'triggered_at' => now(),
            'is_read' => false
        ]);

        $capService = app(CapService::class);
        $capJson = $capService->generateJson($notification);

        $this->assertEquals('mixkey:alert:' . $notification->id, $capJson['identifier']);
        $this->assertEquals('mixkey-system@maejhan.go.th', $capJson['sender']);
        $this->assertEquals('Actual', $capJson['status']);
        $this->assertEquals('Alert', $capJson['msgType']);
        $this->assertEquals('Public', $capJson['scope']);

        $info = $capJson['info'][0];
        $this->assertEquals('th-TH', $info['language']);
        $this->assertEquals('น้ำท่วมฉับพลัน', $info['event']);
        $this->assertEquals('Immediate', $info['urgency']);
        $this->assertEquals('Severe', $info['severity']);
        $this->assertEquals('Observed', $info['certainty']);
        $this->assertEquals('ระดับน้ำสูงวิกฤตล้นตลิ่งเกินขีดจำกัด', $info['description']);
        $this->assertEquals('Test Station', $info['area'][0]['areaDesc']);
        $this->assertEquals('20,99,1.0', $info['area'][0]['circle'][0]);
    }

    /**
     * Test Inbound CAP XML parsing and mapping.
     */
    public function test_inbound_cap_xml_is_parsed_and_mapped_to_database(): void
    {
        $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>
<alert xmlns="urn:oasis:names:tc:emergency:cap:1.2">
  <identifier>sri-gateway-112233</identifier>
  <sender>sri-gateway@kku.ac.th</sender>
  <sent>2026-06-16T18:00:00+07:00</sent>
  <status>Actual</status>
  <msgType>Alert</msgType>
  <scope>Public</scope>
  <info>
    <category>Met</category>
    <event>Storm Warning (พายุดีเปรสชัน)</event>
    <urgency>Expected</urgency>
    <severity>Severe</severity>
    <certainty>Likely</certainty>
    <headline>แจ้งเตือนพายุดีเปรสชันกำลังเคลื่อนตัวเข้าเชียงราย</headline>
    <description>ตรวจพบความกดอากาศต่ำกำลังทวีความรุนแรง ขอให้ประชาชนเฝ้าระวังน้ำป่าไหลหลากและพายุลมแรง</description>
    <instruction>โปรดตรวจสอบความแข็งแรงของอาคารบ้านเรือน</instruction>
    <effective>2026-06-16T18:00:00+07:00</effective>
    <expires>2026-06-19T18:00:00+07:00</expires>
    <area>
      <areaDesc>อำเภอแม่จัน จังหวัดเชียงราย</areaDesc>
    </area>
  </info>
</alert>';

        $response = $this->withHeaders([
            'Content-Type' => 'application/xml',
        ])->postJson('/api/v1/cap/alert', [], ['RAW_BODY' => $xmlPayload]); // workaround to send raw body in laravel testing context

        // Wait, standard postJson might not send raw string body in older Laravel versions,
        // let's send it using call method to specify content
        $response = $this->call(
            'POST',
            '/api/v1/cap/alert',
            [], // parameters
            [], // cookies
            [], // files
            ['HTTP_CONTENT_TYPE' => 'application/xml'], // server
            $xmlPayload // content
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Verify entry in database
        $this->assertDatabaseHas('alerts', [
            'title' => 'แจ้งเตือนพายุดีเปรสชันกำลังเคลื่อนตัวเข้าเชียงราย',
            'severity' => 'high', // Severe mapped to high
            'type' => 'storm_warning', // event mapped to storm_warning
        ]);
    }

    /**
     * Test Inbound CAP JSON parsing.
     */
    public function test_inbound_cap_json_is_parsed_and_mapped_to_database(): void
    {
        $jsonPayload = json_encode([
            'identifier' => 'sri-gateway-json-4455',
            'sender' => 'sri-gateway@kku.ac.th',
            'sent' => '2026-06-16T18:00:00+07:00',
            'status' => 'Actual',
            'msgType' => 'Alert',
            'scope' => 'Public',
            'info' => [
                [
                    'category' => ['Safety'],
                    'event' => 'Flash Flood (น้ำท่วมฉับพลัน)',
                    'urgency' => 'Immediate',
                    'severity' => 'Extreme',
                    'certainty' => 'Observed',
                    'headline' => 'เฝ้าระวังน้ำล้นตลิ่งลุ่มน้ำแม่จัน',
                    'description' => 'ปริมาณน้ำไหลผ่านจุดวัดน้ำวิกฤต คาดว่าจะมีน้ำล้นตลิ่งเข้าท่วมขังในพื้นที่ราบลุ่มช่วง 1-2 ชั่วโมงนี้',
                    'area' => [
                        [
                            'areaDesc' => 'ตำบลป่าตึง'
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->call(
            'POST',
            '/api/v1/cap/alert',
            [],
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            $jsonPayload
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('alerts', [
            'title' => 'เฝ้าระวังน้ำล้นตลิ่งลุ่มน้ำแม่จัน',
            'severity' => 'high', // Extreme mapped to high
            'type' => 'flood_warning'
        ]);
    }

    /**
     * Test Outbound API post trigger during telemetry ingestion.
     */
    public function test_outbound_sri_gateway_api_is_called_on_critical_water_level(): void
    {
        // Fake HTTP client requests
        Http::fake([
            'https://sri-alert.kku.ac.th/alert' => Http::response(['success' => true], 200)
        ]);

        // Reset existing station ID 1 to normal level (1.50m) to ensure state transitions to critical
        $station = Station::find(1);
        $station->update([
            'water_height_datum' => 1.50,
            'warning_datum_m' => 2.00,
            'critical_datum_m' => 3.00,
        ]);

        // Ingest telemetry that breaches critical limit (3.50m)
        $response = $this->withHeaders([
            'X-API-KEY' => 'iot_secret_credentials_key_2026'
        ])->postJson('/api/v1/telemetry/ingest', [
            'sensor_id' => 'PT-01', // maps to station 1
            'water_level_cm' => 350.0, // 3.50m -> critical
            'battery_voltage' => 3.75,
            'signal_strength_rssi' => -70
        ]);

        $response->assertStatus(200);

        // Verify Sri Gateway post request was dispatched
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sri-alert.kku.ac.th/alert' &&
                   $request->hasHeader('Authorization', 'Bearer mock_token_sri_gateway_2026') &&
                   $request['identifier'] !== null &&
                   $request['info'][0]['severity'] === 'Severe';
        });
    }
}
