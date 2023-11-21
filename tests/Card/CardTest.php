<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for class Guess.
 */
class CardTest extends TestCase
{
    /**
     * Construct object without arguments and verify there is 6 tries left.
     */
    public function testCreateObjectNoArguments()
    {
        $card = new Card("S", "1");
        $this->assertInstanceOf("\App\Card\Card", $card);

        /* $res = $card->tries();
         $exp = 6; */
        //$this->assertEquals($exp, $res);
    }

    /**
     * Construct object and verify that the object has the expected
     * properties, use only first argument.
     */
    /* public function testCreateObjectFirstArgument()
    {
        $guess = new Guess(42);
        $this->assertInstanceOf("\Mos\Guess\Guess", $guess);

        $res = $guess->tries();
        $exp = 6;
        $this->assertEquals($exp, $res);

        $res = $guess->number();
        $exp = 42;
        $this->assertEquals($exp, $res);
    } */

    /**
     * Construct object and verify that the object has the expected
     * properties, use both arguments.
     */
    /* public function testCreateObjectBothArguments()
    {
        $guess = new Guess(42, 7);
        $this->assertInstanceOf("\Mos\Guess\Guess", $guess);

        $res = $guess->tries();
        $exp = 7;
        $this->assertEquals($exp, $res);

        $res = $guess->number();
        $exp = 42;
        $this->assertEquals($exp, $res);
    } */
}
