<?php

namespace Telto\AVL;

use Carbon\Carbon;

/**
 * Represents a sample from AVL data packet, conforming to Teltonika Data Sending Protocol
 * (encoded using coded 8)
 *
 * @see https://wiki.teltonika-gps.com/view/Teltonika_Data_Sending_Protocols#Codec_for_device_data_sending
 *      ("Codec 8 -> AVL Data" under )
 */
class Sample extends Fragment
{
    const BYTE_OFFSET_TIMESTAMP = 0;
    const BYTE_OFFSET_PRIORITY = 8;
    const BYTE_OFFSET_GPS = 9;
    const BYTE_OFFSET_IO = 24;  // is it right?

    const PRIORITY_LOW = 0x00;
    const PRIORITY_HIGH = 0x01;
    const PRIORITY_PANIC = 0x02;

    /**
     * @var Carbon\Carbon timestamp when the sample had been taken – a difference,
     *  in milliseconds, between the current time and midnight, January, 1970 UTC (UNIX time).
     */
    protected $timestamp;
    /**
     * @var PRIORITY_??? constant
     */
    protected $priority;
    protected $gps;
    protected $io;

    /**
     * @todo throw if unexpected size or format?
     */
    public function parse(): void
    {
        $tsBytes = $this->readUInt64(self::BYTE_OFFSET_TIMESTAMP);
        $this->timestamp = Carbon::createFromTimestampMs($tsBytes);

        $this->priority = $this->readUInt8(self::BYTE_OFFSET_PRIORITY);

        // TODO: GPS
        $gpsBytes = $this->sliceBytes(15, self::BYTE_OFFSET_GPS);
        $this->gps = new GPS($gpsBytes);

        // TODO: IO
        $ioBytesCount = $this->size() - 24; // 24 bytes is taken by other fields in total
        $gpsBytes = $this->sliceBytes($ioBytesCount, self::BYTE_OFFSET_IO);
        // $this->gps = new IO($ioBytes);
    }
}
