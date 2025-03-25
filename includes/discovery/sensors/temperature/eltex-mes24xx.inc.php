<?php

/*
 * LibreNMS discovery module for Eltex-MES24xx SFP Temperature
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2024 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

use LibreNMS\Util\Oid;

echo 'eltexPhyTransceiverDiagnosticTable' . PHP_EOL;
$snmpData = SnmpQuery::cache()->hideMib()->walk('ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticTable')->table(3);
if (! empty($snmpData)) {
    foreach ($snmpData as $index => $typeData) {
        foreach ($typeData as $type => $data) {
            $eltexPhyTransceiverDiagnosticTable[$type][$index] = array_shift($data);
        }
    }
}

$divisor = 1;
$multiplier = 1;
if (! empty($eltexPhyTransceiverDiagnosticTable['temperature'])) {
    foreach ($eltexPhyTransceiverDiagnosticTable['temperature'] as $ifIndex => $data) {
        $value = $data['eltexPhyTransceiverDiagnosticCurrentValue'] / $divisor;
        if ($value) {
            $high_limit = $data['eltexPhyTransceiverDiagnosticHighAlarmThreshold'];
            $high_warn_limit = $data['eltexPhyTransceiverDiagnosticHighWarningThreshold'];
            $low_warn_limit = $data['eltexPhyTransceiverDiagnosticLowWarningThreshold'];
            $low_limit = $data['eltexPhyTransceiverDiagnosticLowAlarmThreshold'];
            $descr = get_port_by_index_cache($device['device_id'], $ifIndex)['ifName'];
            $oid = Oid::of('ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.' . $ifIndex . '.1.1')->toNumeric();

            app('sensor-discovery')->discover(new \App\Models\Sensor([
                'poller_type' => 'snmp',
                'sensor_class' => 'temperature',
                'sensor_oid' => $oid,
                'sensor_index' => 'SfpTemp' . $ifIndex,
                'sensor_type' => 'ELTEX-PHY-MIB',
                'sensor_descr' => 'SfpTemp-' . $descr,
                'sensor_divisor' => $divisor,
                'sensor_multiplier' => $multiplier,
                'sensor_limit_low' => $low_limit,
                'sensor_limit_low_warn' => $low_warn_limit,
                'sensor_limit_warn' => $high_warn_limit,
                'sensor_limit' => $high_limit,
                'sensor_current' => $value,
                'entPhysicalIndex' => $ifIndex,
                'entPhysicalIndex_measured' => 'port',
                'user_func' => null,
                'group' => 'transceiver',
            ]));
        }
    }
}
