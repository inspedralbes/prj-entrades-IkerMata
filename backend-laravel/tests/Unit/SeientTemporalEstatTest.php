<?php

namespace Tests\Unit;

use App\Support\SeientTemporalEstat;
use PHPUnit\Framework\TestCase;

class SeientTemporalEstatTest extends TestCase
{
    public function test_seient_venut_cap_temporal(): void
    {
        $r = (object) ['usuari_id' => 'aaa'];
        $f = SeientTemporalEstat::flags(true, $r, 'aaa');
        $this->assertFalse($f['seleccionat_per_altre']);
        $this->assertFalse($f['la_meva_reserva']);
    }

    public function test_sense_reserva(): void
    {
        $f = SeientTemporalEstat::flags(false, null, 'user-1');
        $this->assertFalse($f['seleccionat_per_altre']);
        $this->assertFalse($f['la_meva_reserva']);
    }

    public function test_reserva_dun_altre_sense_auth(): void
    {
        $r = (object) ['usuari_id' => 'aaa'];
        $f = SeientTemporalEstat::flags(false, $r, null);
        $this->assertTrue($f['seleccionat_per_altre']);
        $this->assertFalse($f['la_meva_reserva']);
    }

    public function test_reserva_dun_altre_amb_auth(): void
    {
        $r = (object) ['usuari_id' => 'aaa'];
        $f = SeientTemporalEstat::flags(false, $r, 'bbb');
        $this->assertTrue($f['seleccionat_per_altre']);
        $this->assertFalse($f['la_meva_reserva']);
    }

    public function test_la_meva_reserva(): void
    {
        $r = (object) ['usuari_id' => 'aaa'];
        $f = SeientTemporalEstat::flags(false, $r, 'aaa');
        $this->assertFalse($f['seleccionat_per_altre']);
        $this->assertTrue($f['la_meva_reserva']);
    }
}
