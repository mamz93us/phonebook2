<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\MonitoredHost;
use App\Models\SnmpSensor;
use Illuminate\Support\Facades\Log;

class SnmpMonitorService
{
    /**
     * Poll all enabled hosts for metrics.
     */
    public function pollAll(): void
    {
        $hosts = MonitoredHost::where('snmp_enabled', true)->get();

        foreach ($hosts as $host) {
            $this->pollHost($host);
        }
    }

    /**
     * Poll a specific host for its metrics and custom sensors.
     */
    public function pollHost(MonitoredHost $host): array
    {
        if (!$host->snmp_enabled) {
            return ['status' => 'error', 'message' => 'SNMP is not enabled for this host.'];
        }

        // Check if PHP SNMP extension exists
        if (!extension_loaded('snmp')) {
            Log::warning("SnmpMonitorService: SNMP extension not loaded. Mocking results for {$host->ip}");
            return $this->mockPoll($host);
        }

        try {
            $results = [];
            $session = $this->createSession($host);

            // 1. Standard Metrics (System Uptime as example)
            $uptime = $session->get('.1.3.6.1.2.1.1.3.0');
            if ($uptime) {
                $this->saveMetric($host, 'uptime', $this->parseValue($uptime));
            }

            // 2. Custom Sensors
            $sensors = $host->snmpSensors;
            foreach ($sensors as $sensor) {
                $val = $session->get($sensor->oid);
                if ($val) {
                    $this->saveMetric($host, $sensor->description ?: $sensor->oid, $this->parseValue($val));
                }
            }

            $host->update(['status' => 'up', 'last_checked_at' => now()]);
            return ['status' => 'success', 'data' => $results];

        } catch (\Exception $e) {
            Log::error("SnmpMonitorService: Poll failed for {$host->ip}", ['error' => $e->getMessage()]);
            $host->update(['status' => 'down', 'last_checked_at' => now()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function createSession(MonitoredHost $host): \SNMP
    {
        $version = match($host->snmp_version) {
            'v1' => \SNMP::VERSION_1,
            'v3' => \SNMP::VERSION_3,
            default => \SNMP::VERSION_2c,
        };

        // Community is auto-decrypted by cast
        return new \SNMP($version, $host->ip, $host->snmp_community);
    }

    protected function saveMetric(MonitoredHost $host, string $name, float $value): void
    {
        Metric::create([
            'host_id' => $host->id,
            'metric_name' => $name,
            'value' => $value,
            'recorded_at' => now(),
        ]);
    }

    protected function parseValue($value): float
    {
        // Simple parsing of SNMP output (e.g. "INTEGER: 50" or "Timeticks: (500) 0:00:05.00")
        if (preg_match('/(\d+(\.\d+)?)/', $value, $matches)) {
            return (float)$matches[1];
        }
        return 0;
    }

    protected function mockPoll(MonitoredHost $host): array
    {
        $this->saveMetric($host, 'cpu_usage', rand(5, 45));
        $this->saveMetric($host, 'memory_usage', rand(20, 80));
        
        $host->update(['status' => 'up', 'last_checked_at' => now()]);
        
        return ['status' => 'success', 'mocked' => true];
    }
}
