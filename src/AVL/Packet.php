<?php

namespace Telto\AVL;

use PHPinnacle\Buffer\{
    ByteBuffer,
    BufferOverflow
};

// $hex = "0000000000000055080100000176eebf5fc0000e6cb78c21efdd6c0013006f0a0000001208ef000100b401510059006f00a000eb0007423123430f5e5400965500005a00007000007303660353000035ff5710421c006400002b8100010000d126";
/**
 * Represents an AVL data packet, conforming to Teltonika Data Sending Protocol
 * and encoded using coded 8
 *
 * @see https://wiki.teltonika-gps.com/view/Teltonika_Data_Sending_Protocols#Codec_for_device_data_sending
 * @var https://github.com/phpinnacle/buffer/blob/master/src/ByteBuffer.php
 */
class Packet extends ByteBuffer
{
    // Note: in HEX representation, there would be 2 bytes of HEX-encoded str
    // for every byte of raw binary string
    // carefully @see https://stackoverflow.com/a/27002912
    const MIN_PACKET_LENGTH = 45;   // in bytes
    const MAX_PACKET_LENGTH = 1280; // in bytes

    // Give name for byte offsets of meaningful data fields
    const BYTE_OFFSET_PREAMBLE = 0;
    const BYTE_OFFSET_DATA_LENGTH = 4;
    const BYTE_OFFSET_CODEC = 8;
    const BYTE_OFFSET_DATA_REC_COUNT = 9;
    const BYTE_OFFSET_PAYLOAD = 10;
    // Other fields (Number of Data 2 (doubles BYTE_OFFSET_DATA_REC_COUNT)) (1 byte)
    // and CRC16 (last 4 bytes)
    // are at unknown positions - depends on data payload length

    // For validation
    const CODEC_VERSION_SIGNATURE_8 = 0x08;
    const PREAMBLE = 0x00000000;

    // MARK: Factory & convenience methods for creating
    // -----------------------------------------------------------------------

    static public function fromHexEncoded(string $hexEncodedStr): self
    {
        $bytes = hex2bin($hexEncodedStr); // TODO: ensure no warning is output
        if (false === $bytes) {
            throw new Exception("Raw HEX packet cannt be encoded to binary string");
        }

        return new self($bytes);
    }

    // MARK: Instance methods
    // -----------------------------------------------------------------------

    public function __construct(string $buffer)
    {
        parent::__construct($buffer);
        $this->parse();
    }

    public function parse()
    {
        // Codec8 – a main FM device protocol that is used for sending data to the server.
        // TODO: preamble
        // TODO: codec
        // TODO: extract payload -> convert to value object
        // TODO: crc16
    }

    /**
     * @return 32 zero bits
     */
    public function getPreamble(): int // is int ok?
    {
        $this->readUint32(self::BYTE_OFFSET_PREAMBLE);
    }

    /**
     * @return unsinged int codec identificator
     */
    public function getCodecID(): int
    {
        // TODO: see if works good with signed/unsigned ints?
        return $this->readUInt8(self::BYTE_OFFSET_CODEC);
    }

    /**
     * @return parsed sample from data payload (AVL data) field
     */
    public function getDataSample(): Sample
    {
        $buffer = $this->getDataSampleBytes();
        return new Sample($buffer->bytes());
    }

    /**
     * @return parsed sample from data payload (AVL data) field
     *
     * @throws BufferOverflow
     */
    public function getDataSampleBytes(): ByteBuffer
    {
        $numBytes = $this->getDataLengthInBytes();
        return $this->slice($numBytes, self::BYTE_OFFSET_PAYLOAD);
    }

    /**
     * @return reported sample payload size, in bytes
     */
    public function getDataLengthInBytes(): int
    {
        $this->readUInt32(self::BYTE_OFFSET_DATA_LENGTH);
    }

    public function isValid(): boolean
    {
        // TODO:
        // * preamble
        // * supported codec ID (0x08)
        // * recourd count 1 & 2 match
        // * ensure packet length consistent with envelope size (known) and
        //   reported data length
        // * CRC
        return true;
    }

}
