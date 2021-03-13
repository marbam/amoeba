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
            [-2,  -2, 'Jump'],
            [ 0,  -2, 'Jump'],
            [ 2,  -2, 'Jump'],
            [-1,  -1, 'Split'],
            [ 0,  -1, 'Split'],
            [ 1,  -1, 'Split'],
            [-2,   0, 'Jump'],
            [-1,   0, 'Split'],
            [ 1,   0, 'Split'],
            [ 2,   0, 'Jump'],
            [-1,   1, 'Split'],
            [ 0,   1, 'Split'],
            [ 1,   1, 'Split'],
            [-2,   2, 'Jump'],
            [ 0,   2, 'Jump'],
            [ 2,   2, 'Jump']
        ];

        $coordinates = [];

        foreach($comparisons as $translation) {
            $coordinate['type'] = $translation[2];
            $coordinate['toY'] = $y+$translation[0];
            $coordinate['toX'] = $x+$translation[1];
            $coordinates[] = $coordinate;
        }


        $results = [];
        foreach ($coordinates as $dest) {
            if (isset($grid[$dest['toY']][$dest['toX']]) && $grid[$dest['toY']][$dest['toX']] == "_") {
                $results[] = $this->calculate_converts($dest, $grid, $player);
            }
        }

        dd($results);
    }

    public function calculate_converts($dest, $grid, $player) {
        $new_grid = $grid;
        $new_grid[$dest['toX']][$dest['toY']] = $player; // move into empty
        $count = 0;
        if ($dest['type'] == "Split") {
            $count = 1;
        }

        // also need to remove the originals. Urgh, my brain. Review later!

        // convert and calculate converts

        $surrounding = [
            [-1,  -1],
            [ 0,  -1],
            [ 1,  -1],
            [-1,   0],
            [ 1,   0],
            [-1,   1],
            [ 0,   1],
            [ 1,   1],
        ];

        $translations = [];
        foreach($surrounding as $going_to) {
            $translation = [];
            $translation[] = $dest['toX']+$going_to[0];
            $translation[] = $dest['toY']+$going_to[1];
            $translations[] = $translation;
        }

        foreach($translations as $check) {
            if (isset($new_grid[$check[0]][$check[1]]) && !in_array($new_grid[$check[0]][$check[1]], ["_", $player])) {
                $new_grid[$check[0]][$check[1]] = $player;
                $count++;
            }
        }
        return [$dest, $grid, $new_grid, $player, $count];
    }
}
