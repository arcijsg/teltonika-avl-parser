<?php

namespace Telto\AVL;

// @see https://github.com/phpinnacle/buffer/blob/master/src/ByteBuffer.php
use PHPinnacle\Buffer\{
    ByteBuffer,
    BufferOverflow
};

/**
 * Base class which abstracts a byte slice (byte buffer) with fixed and
 * well-defined contents and adds on top of it a layer of domain logic.
 */
abstract class Fragment extends ByteBuffer {

    public function __construct(string $buffer)
    {
        parent::__construct($buffer);
        $this->parse();
    }

    /**
     * @throws BufferOverflow when received byte slice is too short for
     *    parsing an expected subfield
     * @throws Telto\Exception\AVL\UnexpectedPayloadError if contents of a
     *    fragment or it's subfield does not make sense when decoded
     */
    abstract public function parse(): void;

    /**
     * Work around the feature of ByteBuffer, which returns a new instance of
     * the calling class (new static(..) instead of new self(..)), preventing
     * us from creating instances of different subclasses on parse() - causes
     * self::parse() to be called another time, with invalid underlying buffer.
     *
     * Return a ByteBuffer from which another value field class can be
     * instantinated inside of parse() method.
     *
     * @param int $numBytes
     * @param int $offset
     *
     * @return ByteBuffer
     * @throws BufferOverflow
     */
    public function sliceBytes(int $numBytes, int $offset = 0): ByteBuffer
    {
        $size = $this->size();
        if ($size < $numBytes) {
            throw new BufferOverflow;
        }

        return $size === $numBytes
            ? new ByteBuffer($this->bytes())
            : new ByteBuffer(\substr($this->bytes(), $offset, $numBytes));
    }

    /**
     * @return parsed subfields as a json-encodable array
     */
    public function toArray(): array
    {
        return [];
    }

    /**
     * @return textual representation of this fragment (log outputs, ...)
     *
     * Hex-encoded underlying byte buffer is returned by default.
     * Please, overide with something more meaningful in child classes.
     */
    public function __toString(): string
    {
        return $this->toHexString();
    }

    public function toHexString(): string
    {
        return bin2hex($this->bytes());
    }
}
