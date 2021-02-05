<?php

namespace Telto\Decoder;

use \Telto\AVL\Packet as AVLPacket;

/**
 * Takes a hex-encoded fraw TCP packet and convert it into AVL Packet samples.
 *
 * @see https://wiki.teltonika-gps.com/view/Teltonika_Data_Sending_Protocols#Codec_for_device_data_sending
 */
class AVL
{
    /**
     * @see https://wiki.teltonika-gps.com/view/Teltonika_Data_Sending_Protocols
     *
     * @param string $hexStr as received from API endpoint which publishes
     *    a stream of sensor readings
     * @return AVLPacket
     * @throw ?
     */
    public function __invoke(string $hexStr = ""): AVLPacket
    {
        $decoded = AVLPacket::fromHexEncoded($hexStr);
        return $decoded;
    }
}
