<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MoveController extends Controller
{
    public function test() {
        return $this->processMoves("R", $this->getBoard());
    }

    public function processMoves($player, $board) {
        $size = $board['0'];
        $grid = $this->populateArray($board[0], $board[1]);

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                if ($grid[$y][$x] == $player) {
                    $this->processBlob($player, $grid, $x, $y); // 1, 0
                }
            }
        }
    }

    public function populateArray($size, $string) {
        $array = [];

        // populate everything as 0!
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $array[$x][$y] = '0';
            }
        }

        $x = 0;
        $y = 0;
        for ($strref = 0; $strref < strlen($string); $strref++) {
            if ($y == $size) {
                $y = 0;
                $x++;
            }

            $array[$x][$y] = substr($string, $strref, 1);
            $y++;
        }
        return $array;
    }




    public function getBoard() {
        return [5, "_R_____G____G___________G"];


        // "R____
        //  __G__
        //  __G__
        //  _____
        //  ____G"

        // "_R___
        //  __G__
        //  __G__
        //  _____
        //  ____G"
    }

    public function processBlob($player, $grid, $x, $y) {

        // populate coordinates
        $comparisons = [
            [-2, -2],
            [ 0, -2],
            [ 2,  -2],
            [-1, -1],
            [ 0, -1],
            [ 1, -1],
            [-1,  0],
            [ 1,  0],
            [-1, 1],
            [0,  1],
            [1,  1],
            [-2, 2],
            [0, 2],
            [2, 2]
        ];

        // dd($x, $y);

        $coordinates = [];

        foreach($comparisons as $translation) {
            $coordinates[] = [$x+$translation[0], $y+$translation[1]];
        }

        foreach ($coordinates as $dest) {

            if (isset($grid[$dest[0]][$dest[1]]) && $grid[$dest[0]][$dest[1]] == "_") {
                dd($dest[0], $dest[1]);
            }

        }
    }
}
