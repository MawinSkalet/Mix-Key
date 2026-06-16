<?php

namespace App\Services;

use App\Models\StaffNotification;
use Carbon\Carbon;
use SimpleXMLElement;
use Exception;

class CapService
{
    /**
     * Generate CAP v1.2 JSON array representation for outbound delivery.
     *
     * @param StaffNotification $notification
     * @return array
     */
    public function generateJson(StaffNotification $notification): array
    {
        $station = $notification->station;
        $triggeredAt = Carbon::parse($notification->triggered_at);

        $isCritical = $notification->severity === 'critical';
        $urgency = $isCritical ? 'Immediate' : 'Expected';
        $severity = $isCritical ? 'Severe' : 'Moderate';
        $headline = $isCritical ? 'แจ้งเตือนระดับน้ำวิกฤตล้นตลิ่ง' : 'แจ้งเตือนเฝ้าระวังระดับน้ำเพิ่มสูง';
        $instruction = $isCritical 
            ? 'โปรดเคลื่อนย้ายสิ่งของขึ้นที่สูง อพยพสัตว์เลี้ยง และติดตามสถานการณ์อย่างใกล้ชิด' 
            : 'โปรดเฝ้าระวังสถานการณ์น้ำอย่างใกล้ชิดและเตรียมความพร้อมในการรับมือ';

        return [
            'identifier' => 'mixkey:alert:' . $notification->id,
            'sender' => 'mixkey-system@maejhan.go.th',
            'sent' => $triggeredAt->toIso8601String(),
            'status' => 'Actual',
            'msgType' => 'Alert',
            'scope' => 'Public',
            'info' => [
                [
                    'language' => 'th-TH',
                    'category' => ['Safety'],
                    'event' => 'น้ำท่วมฉับพลัน',
                    'urgency' => $urgency,
                    'severity' => $severity,
                    'certainty' => 'Observed',
                    'headline' => $headline,
                    'description' => $notification->message,
                    'instruction' => $instruction,
                    'area' => [
                        [
                            'areaDesc' => $station ? ($station->location_description ?? $station->name) : 'อำเภอแม่จัน เชียงราย',
                            'circle' => $station ? [$station->latitude . ',' . $station->longitude . ',1.0'] : []
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Parse incoming CAP XML or JSON payload and map to local Alert schema.
     *
     * @param string $payload
     * @param string $contentType
     * @return array
     * @throws Exception
     */
    public function parseCap(string $payload, string $contentType): array
    {
        if (str_contains(strtolower($contentType), 'xml')) {
            return $this->parseXml($payload);
        } elseif (str_contains(strtolower($contentType), 'json')) {
            return $this->parseJson($payload);
        }

        // Try to auto-detect if content type is not provided or generic
        $trimmed = trim($payload);
        if (str_starts_with($trimmed, '<')) {
            return $this->parseXml($payload);
        } elseif (str_starts_with($trimmed, '{')) {
            return $this->parseJson($payload);
        }

        throw new Exception('Unsupported payload format. Expected XML or JSON.');
    }

    /**
     * Parse CAP XML.
     *
     * @param string $xmlString
     * @return array
     * @throws Exception
     */
    protected function parseXml(string $xmlString): array
    {
        try {
            // Suppress errors during parsing
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlString);
            libxml_clear_errors();

            // Validate namespace
            $namespaces = $xml->getDocNamespaces(true);
            if (!isset($namespaces['']) || !str_contains($namespaces[''], 'emergency/cap')) {
                // Not a strict namespace failure, but log warning or attempt fallback
            }

            // Register standard CAP namespace for XPath queries
            $xml->registerXPathNamespace('cap', 'urn:oasis:names:tc:emergency:cap:1.2');

            // Extract alert element values (checking both namespaced and local names)
            $sent = $this->xpathValue($xml, '//cap:sent') ?: ($xml->sent ?? now()->toIso8601String());
            
            // Info block (first one)
            $info = $xml->xpath('//cap:info');
            $infoBlock = $info[0] ?? $xml->info ?? null;

            if (!$infoBlock) {
                throw new Exception('CAP message contains no info block.');
            }

            $event = (string)($infoBlock->event ?? '');
            $headline = (string)($infoBlock->headline ?? '');
            $description = (string)($infoBlock->description ?? '');
            $severity = (string)($infoBlock->severity ?? 'Unknown');
            $effective = (string)($infoBlock->effective ?? '');
            $expires = (string)($infoBlock->expires ?? '');

            return $this->mapCapFieldsToAlert([
                'sent' => (string)$sent,
                'event' => $event,
                'headline' => $headline,
                'description' => $description,
                'severity' => $severity,
                'effective' => $effective,
                'expires' => $expires
            ]);

        } catch (Exception $e) {
            throw new Exception('XML Parse Error: ' . $e->getMessage());
        }
    }

    /**
     * Parse CAP JSON.
     *
     * @param string $jsonString
     * @return array
     * @throws Exception
     */
    protected function parseJson(string $jsonString): array
    {
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON Parse Error: ' . json_last_error_msg());
        }

        $sent = $data['sent'] ?? now()->toIso8601String();
        $infoBlock = $data['info'][0] ?? $data['info'] ?? null;

        if (!$infoBlock) {
            throw new Exception('CAP JSON message contains no info block.');
        }

        return $this->mapCapFieldsToAlert([
            'sent' => $sent,
            'event' => $infoBlock['event'] ?? '',
            'headline' => $infoBlock['headline'] ?? '',
            'description' => $infoBlock['description'] ?? '',
            'severity' => $infoBlock['severity'] ?? 'Unknown',
            'effective' => $infoBlock['effective'] ?? '',
            'expires' => $infoBlock['expires'] ?? ''
        ]);
    }

    /**
     * Extract string value via XPath.
     */
    protected function xpathValue(SimpleXMLElement $xml, string $path): ?string
    {
        $res = $xml->xpath($path);
        return !empty($res) ? (string)$res[0] : null;
    }

    /**
     * Map CAP properties to standard local Alert attributes.
     */
    protected function mapCapFieldsToAlert(array $cap): array
    {
        $event = strtolower($cap['event']);
        
        // Map event type
        $type = 'general_warning';
        if (str_contains($event, 'storm') || str_contains($event, 'พายุ') || str_contains($event, 'ฝน')) {
            $type = 'storm_warning';
        } elseif (str_contains($event, 'flood') || str_contains($event, 'น้ำท่วม') || str_contains($event, 'น้ำป่า')) {
            $type = 'flood_warning';
        }

        // Map severity
        $severity = 'low';
        $capSeverity = strtolower($cap['severity']);
        if (in_array($capSeverity, ['extreme', 'severe'])) {
            $severity = 'high';
        } elseif ($capSeverity === 'moderate') {
            $severity = 'medium';
        }

        // Announced time
        $announcedAt = !empty($cap['effective']) ? Carbon::parse($cap['effective']) : Carbon::parse($cap['sent']);

        // Expires time
        $expiresAt = !empty($cap['expires']) 
            ? Carbon::parse($cap['expires']) 
            : $announcedAt->copy()->addDay(); // default 1 day expiry

        return [
            'type' => $type,
            'title' => $cap['headline'] ?: $cap['event'] ?: 'แจ้งเตือนภัยพิบัติ',
            'message' => $cap['description'] ?: 'มีการแจ้งเตือนสถานการณ์ในพื้นที่ของท่าน',
            'severity' => $severity,
            'announced_at' => $announcedAt,
            'expires_at' => $expiresAt
        ];
    }
}
