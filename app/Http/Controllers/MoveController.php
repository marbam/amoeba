<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MoveController extends Controller
{
    public function test($advance = 0) {

        // check advance is odd, return early.
        if ($advance % 2 != 0) {
            return;
        }

        $board = $this->getBoard();
        $size = $board[0];
        $player = "R";
        $moves = $this->getMoves($player, $board);

        if ($advance == 0) {
            // needs singular lookup adding in here!
            return $moves;
        }

        $moves = $this->populateCounters($moves, $size, $player, 1, $advance);
    }

    public function populateCounters(&$moves, $size, $player, $level, $advance) {
        $player = $this->flipPlayer($player);
        foreach($moves as $index => $move) {
            $moves[$index]['counters'] = $this->getMoves($player, [$size, $move['end_grid']]);
            if ($level < $advance) {
                $this->populateCounters($moves[$index]['counters'], $size, $player, $level+1, $advance);
            }
        }
        return $moves;
    }

    public function flipPlayer($player) {
        return $player == "R" ? "G" : "R";
    }

    public function getMoves($player, $board) {
        $size = $board['0'];

        if(!is_array($board[1])) {
            $grid = $this->populateArray($board[0], $board[1]);
        } else {
            $grid = $board[1];
        }

        $moves = [];
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                if ($grid[$y][$x] == $player) {
                    $moves_for_blob = $this->processBlob($player, $grid, $x, $y); // 1, 0
                    foreach ($moves_for_blob as $move) {
                        $moves[] = $move;
                    }
                }
            }
        }

        return($moves);

        // initial return logic below, will need a refactor at a later point when
        // recursive counters is sorted!

        $payload = [
            'outcome' => 'GAMEOVER',
            'reason'  => 'NO_MOVES',
        ];

        $best_moves = [];

        foreach($moves as $index => $move) {
            if($index == 0) {
                $best_moves[] = $move;
            } else {
                if ($move['score'] > $best_moves[0]['score']) {
                    $best_moves = [$move];
                } else if ($move['score'] == $best_moves[0]['score']) {
                    $best_moves[] = $move;
                }
            }
        }

        $number_of_moves = count($best_moves);

        if ($number_of_moves) {
            $payload = [
                'outcome' => 'MOVE',
                'reason'  => '',
            ];

            $index = rand(0, ($number_of_moves-1));
            $payload['move'] = $best_moves[$index];

        }

        dd($payload);

        return $payload;
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
        // return [5, "_R_____G____G___________G"];
        return [5, "_R_____G____G___R_______G"];
        // return [5, "_______G____G___R_______G"];

        // return [5, "______RBR__R_R__RRR______"];


        // "R____
        //  __G__
        //  __G__
        //  _____
        //  ____G"

        // "_R___
        //  __G__
        //  __G__
        //  _R___
        //  ____G"

        // _____
        // _RBR_
        // _R_R_
        // _RRR_
        // _____
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

        $from = ['x' => $x, 'y' => $y];

        $results = [];
        foreach ($coordinates as $dest) {
            if (isset($grid[$dest['toY']][$dest['toX']]) && $grid[$dest['toY']][$dest['toX']] == "_") {
                $results[] = $this->calculate_converts($from, $dest, $grid, $player);
            }
        }

        return $results;
    }

    public function calculate_converts($from, $dest, $grid, $player) {
        $new_grid = $grid;
        $new_grid[$dest['toY']][$dest['toX']] = $player; // move into empty
        $score = 0;
        if ($dest['type'] == "Split") {
            $score = 1;
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
            $translation[] = $dest['toY']+$going_to[1];
            $translation[] = $dest['toX']+$going_to[0];
            $translations[] = $translation;
        }

        foreach($translations as $check) {
            if (isset($new_grid[$check[0]][$check[1]]) && !in_array($new_grid[$check[0]][$check[1]], ["_", $player])) {
                $new_grid[$check[0]][$check[1]] = $player;
                $score++;
            }
        }

        // remove the original blob if it's a jump
        if ($dest['type'] == "Jump") {
            $new_grid[$from['y']][$from['x']] = '_';
        }

        return [
                'from' => $from,
                'destination' => $dest,
                'start_grid' => $grid,
                'end_grid' => $new_grid,
                'player' => $player,
                'score' => $score
            ];
    }
}
