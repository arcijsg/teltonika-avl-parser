<?php

namespace Telto\AVL;

// @see https://github.com/phpinnacle/buffer/blob/master/src/ByteBuffer.php
use PHPinnacle\Buffer\{
    ByteBuffer,
    BufferOverflow
};

/**
 * Represents a sample from AVL data packet, conforming to Teltonika Data Sending Protocol
 * (encoded using coded 8)
 *
 * @see https://wiki.teltonika-gps.com/view/Teltonika_Data_Sending_Protocols#Codec_for_device_data_sending
 *      ("Codec 8 -> AVL Data" under )
 */
class GPS extends Fragment
{
    /**
     * @const int coefficient with which geo cordinates (latitude and longitude)
     *  had been multiplied at encoding time to store them as an integer
     *  (in order not to worry about binary encoding of floating point numbers?)
     */
    const PRECISION_MULTIPLIER = 10000000;
    const SPEED_INVALID = 0x0000;

    const BYTE_OFFSET_LONGITUDE = 0;
    const BYTE_OFFSET_LATITUDE = 4;
    const BYTE_OFFSET_ALTITUDE = 8;
    const BYTE_OFFSET_ANGLE = 10;
    const BYTE_OFFSET_SAT_COUNT = 12;
    const BYTE_OFFSET_SPEED = 13;

    /**
     * Note on hpw GPS coordinates are encoded:
     *
     * Longitude and latitude are 32-bit integer values built from
     * degrees, minutes, seconds and milliseconds by formula:
     *
     * (degrees + minutes / 60 + seconds / 60 + milliseconds / 60) * 10000000
     *
     * If longitude is in west or latitude in south, result wasmultiplied by –1
     * (and resulting integer is negative - it's most significant bit is 1)
     */

     /**
      * @var Longitude – east – west position.
      * 4 bytes
      */
    protected $longitude;
    /**
     * @var Latitude – north – south position.
     * 4 bytes
     */
    protected $latitude;
    /**
     * @var Altitude – meters above sea level.
     * 2 bytes
     */
    protected $altitude;
    /**
     * @var Angle – degrees from north pole.
     * 2 bytes
     */
    protected $angle;
    /**
     * @var int unsigned Satellites – number of visible satellites.
     * 1 byte
     */
    protected $satellitesCount;
    /**
     * @var Speed – speed calculated from satellites.
     * 2 bytes
     */
    protected $speed;

    /**
     * @todo throw if unexpected size or format?
     */
    public function parse(): void
    {
        // Is "float" (unpack("f", ..)) the right representation of
        // latitude/longitude values (32-bit +/- decimal number)?
        $this->longitude = $this->readInt32(self::BYTE_OFFSET_LONGITUDE);
        $this->latitude = $this->readInt32(self::BYTE_OFFSET_LATITUDE);
        // Could it be a signed int? below sea level could be a legit altitude
        // in a deep valley, or - somewhere anywhere in Netherlands :)
        $this->altitude = $this->readInt16(self::BYTE_OFFSET_ALTITUDE);
        $this->angle = $this->readInt16(self::BYTE_OFFSET_ANGLE); // 0..360? Or -180..180?
        $this->satellitesCount = $this->readUInt8(self::BYTE_OFFSET_SAT_COUNT);
        $this->speed = $this->readUInt16(self::BYTE_OFFSET_SPEED);
    }

    public function humanizeLatitude(): float
    {
        return $this->latitude / self::PRECISION_MULTIPLIER;
    }

    public function humanizeLongitude(): float
    {
        return $this->longitude / self::PRECISION_MULTIPLIER;
    }

    public function toArray(): array
    {
        return [
            "latitude" => $this->humanizeLatitude(),
            "longitude" => $this->humanizeLongitude(),
        ];
    }

}
