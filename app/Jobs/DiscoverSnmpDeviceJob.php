<?php

namespace App\Jobs;

use App\Models\MonitoredHost;
use App\Models\SnmpSensor;
use App\Services\Snmp\SnmpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DiscoverSnmpDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MonitoredHost $host)
    {
    }

    public function handle(): void
    {
        if (!$this->host->snmp_enabled) {
            return;
        }

        if (!SnmpClient::isSnmpExtensionLoaded()) {
            Log::warning("DiscoverSnmpDeviceJob: PHP SNMP extension not loaded — will attempt CLI fallback.", [
                'host' => $this->host->ip,
            ]);
        }

        $client = null;
        try {
            $client = new SnmpClient($this->host);
            $client->connect();

            $sysNameRaw = $client->get('1.3.6.1.2.1.1.5.0');
            $sysDescrRaw = $client->get('1.3.6.1.2.1.1.1.0');

            Log::info("SNMP Discovery: Raw responses from {$this->host->ip}", [
                'sysName' => $sysNameRaw,
                'sysDescr' => $sysDescrRaw,
                'port' => $this->host->snmp_port ?? 161,
                'version' => $this->host->snmp_version,
            ]);

            $sysName = $this->cleanString($sysNameRaw);
            $sysDescr = $this->cleanString($sysDescrRaw);

            if ($sysName) {
                $this->host->name = $sysName;
            }

            // Always create Uptime sensor
            $this->createSensor('System Uptime', '1.3.6.1.2.1.1.3.0', 'uptime', null, null, null, 'system');

            // --- Vendor Detection based on sysDescr ---
            $discoveredType = 'generic';

            if (stripos($sysDescr, 'Cisco') !== false || stripos($sysDescr, 'IOS') !== false) {
                $discoveredType = 'cisco';
                $this->host->type = 'switch';
                $this->createSensor('CPU Usage (5m)', '1.3.6.1.4.1.9.9.109.1.1.1.1.8.1', 'gauge', '%', 85, 95, 'system');
                $this->createSensor('Free Memory', '1.3.6.1.4.1.9.9.48.1.1.1.6.1', 'gauge', 'bytes', null, null, 'system');
            } elseif (stripos($sysDescr, 'Printer') !== false || stripos($sysDescr, 'HP LaserJet') !== false || stripos($sysDescr, 'Lexmark') !== false) {
                $discoveredType = 'printer';
                $this->host->type = 'printer';
                $this->createSensor('Page Count', '1.3.6.1.2.1.43.10.2.1.4.1.1', 'counter', 'pages', null, null, 'system');
            } elseif (stripos($sysDescr, 'Grandstream') !== false || stripos($sysDescr, 'UCM') !== false || stripos($sysName, 'UCM') !== false) {
                $discoveredType = 'grandstream';
                $this->host->type = 'server';
                // Grandstream specific sensors from GS-UCM63XX-SNMP-MIB
                $this->createSensor('Concurrent Calls', '1.3.6.1.4.1.12581.2.2.9.0', 'gauge', 'calls');
                $this->createSensor('CPU Usage', '1.3.6.1.4.1.12581.2.2.8.0', 'gauge', '%', 85, 95);
                $this->createSensor('Memory Usage', '1.3.6.1.4.1.12581.2.2.7.0', 'gauge', '%', 85, 95);
                $this->createSensor('Disk Usage', '1.3.6.1.4.1.12581.2.2.6.0', 'gauge', '%', 85, 95);
            } elseif (stripos($sysDescr, 'Linux') !== false) {
                $discoveredType = 'linux';
                $this->host->type = 'server';
                $this->createSensor('Load Average 1m', '1.3.6.1.4.1.2021.10.1.3.1', 'gauge', null, null, null, 'system');
                $this->createSensor('CPU Idle', '1.3.6.1.4.1.2021.11.11.0', 'gauge', '%', null, null, 'system');
            } elseif (stripos($sysDescr, 'Windows') !== false) {
                $discoveredType = 'windows';
                $this->host->type = 'server';
            }

            $this->host->discovered_type = $discoveredType;
            $this->host->save();

            Log::info("SNMP Discovery completed for {$this->host->ip}", [
                'discovered_type' => $discoveredType,
                'sysName' => $sysName,
            ]);

            // Fire Interface Discovery automatically
            DiscoverSnmpInterfacesJob::dispatchSync($this->host);

        } catch (\Exception $e) {
            Log::error("DiscoverSnmpDeviceJob failed", [
                'host' => $this->host->ip,
                'port' => $this->host->snmp_port ?? 161,
                'version' => $this->host->snmp_version,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $client?->close();
        }
    }

    protected function cleanString(string|false|null $value): ?string
    {
        if (!$value || $value === false) return null;
        $value = preg_replace('/^[a-zA-Z]+:\s*/', '', $value);
        return trim(trim($value, '"'));
    }

    protected function createSensor(
        string $name,
        string $oid,
        string $dataType,
        ?string $unit = null,
        ?float $warn = null,
        ?float $crit = null,
        ?string $sensorGroup = null
    ): void {
        $this->host->snmpSensors()->firstOrCreate(
            ['oid' => $oid],
            [
                'name' => $name,
                'data_type' => $dataType,
                'unit' => $unit,
                'poll_interval' => 60,
                'warning_threshold' => $warn,
                'critical_threshold' => $crit,
                'graph_enabled' => true,
                'sensor_group' => $sensorGroup,
            ]
        );
    }
}
