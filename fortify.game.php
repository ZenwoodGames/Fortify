<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Fortify implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * fortify.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class Fortify extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        $this->initGameStateLabels(array(
            "actionsRemaining" => 10,
            "isFirstRound" => 11,
            "isVeryFirstTurn" => 12,
            "gameVariant" => 100
        ));
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "fortify";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    function setupNewGame($players, $options = array())
    {
        // Define colors
        $colors = array("red", "green");  // Red, Green

        // Initialize players
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        $i = 0;
        foreach ($players as $player_id => $player) {
            $color = $colors[$i];  // Assign red to first player, green to second
            $canal = $player['player_canal'];
            $name = addslashes($player['player_name']);
            $avatar = addslashes($player['player_avatar']);
            $values[] = "('$player_id','$color','$canal','$name','$avatar')";
            $i++;
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);

        $this->activeNextPlayer();

        // Initialize the first round flag
        $this->setGameStateInitialValue('isFirstRound', 1);
        // Initialize the actions counter (1 for first round)
        $this->setGameStateInitialValue('actionsRemaining', 1);

        // Get the ID of the first player (assuming you want the first registered player to go first)
        $first_player_id = array_keys($players)[0];

        // Explicitly set the first player as active
        $this->gamestate->changeActivePlayer($first_player_id);

        // Go to the first player's turn
        //$this->gamestate->nextState(ST_PLAYER_F_TURN);
        // Go to the first player's turn
        //$this->gamestate->nextState(ST_PLAYER_F_TURN);

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    public function getAllDatas()
    {
        $result = array('players' => array(), 'units' => array());

        // Get players & their data
        $sql = "SELECT player_id id, player_name name, player_score score FROM player";
        $result['players'] = self::getCollectionFromDb($sql);

        // Get units & their data
        $sql = "SELECT id, type, player_id, x, y, unit_id, is_fortified FROM units";
        $result['units'] = self::getObjectListFromDB($sql);

        // Get reinforcement track data
        $result['reinforcementTrack'] = $this->getReinforcementTrackState();

        // Initialize decks (example data)
        $result['decks'] = array(
            'bottom' => array(
                'infantry' => array(
                    array('type' => 'infantry', 'player' => 'red'),
                    array('type' => 'infantry', 'player' => 'red'),
                    array('type' => 'infantry', 'player' => 'red'),
                    array('type' => 'infantry', 'player' => 'red')
                ),
                'battleship' => array(
                    array('type' => 'battleship', 'player' => 'red'),
                    array('type' => 'battleship', 'player' => 'red'),
                    array('type' => 'battleship', 'player' => 'red'),
                    array('type' => 'battleship', 'player' => 'red')
                ),
                'tank' => array(
                    array('type' => 'tank', 'player' => 'red'),
                    array('type' => 'tank', 'player' => 'red'),
                    array('type' => 'tank', 'player' => 'red'),
                    array('type' => 'tank', 'player' => 'red')
                )
            ),
            'top' => array(
                'infantry' => array(
                    array('type' => 'infantry', 'player' => 'green'),
                    array('type' => 'infantry', 'player' => 'green'),
                    array('type' => 'infantry', 'player' => 'green'),
                    array('type' => 'infantry', 'player' => 'green')
                ),
                'battleship' => array(
                    array('type' => 'battleship', 'player' => 'green'),
                    array('type' => 'battleship', 'player' => 'green'),
                    array('type' => 'battleship', 'player' => 'green'),
                    array('type' => 'battleship', 'player' => 'green')
                ),
                'tank' => array(
                    array('type' => 'tank', 'player' => 'green'),
                    array('type' => 'tank', 'player' => 'green'),
                    array('type' => 'tank', 'player' => 'green'),
                    array('type' => 'tank', 'player' => 'green')
                )
            )
        );

        // If the game variant is Special Warfare (3), add chopper and artillery units
        if (self::getGameStateValue('gameVariant') == 3) {
            $result['decks']['bottom']['chopper'] = array(
                array('type' => 'chopper', 'player' => 'red')
            );
            $result['decks']['bottom']['artillery'] = array(
                array('type' => 'artillery', 'player' => 'red')
            );

            $result['decks']['top']['chopper'] = array(
                array('type' => 'chopper', 'player' => 'green')
            );
            $result['decks']['top']['artillery'] = array(
                array('type' => 'artillery', 'player' => 'green')
            );
        }

        // Game variant
        $result['gameVariant'] = self::getGameStateValue('gameVariant');

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    function stNextPlayer()
    {
        // Activate next player
        $player_id = $this->activeNextPlayer();
        self::giveExtraTime($player_id);

        $isFirstRound = $this->getGameStateValue('isFirstRound');

        if ($isFirstRound) {
            // First round: 2 actions per player, first must be enlist
            $this->setGameStateValue('actionsRemaining', 2);
            $this->gamestate->nextState('playerFirstEnlist');
        } else {
            // Regular rounds: 2 actions per player
            $this->setGameStateValue('actionsRemaining', 2);
            $this->gamestate->nextState('playerTurn');
        }
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function argPlayerTurn()
    {
        return array(
            'actionsRemaining' => $this->getGameStateValue('actionsRemaining')
        );
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in fortify.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        $this->checkAction( 'playCard' ); 
        
        $player_id = $this->getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        $this->notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => $this->getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    function enlist($unitType, $x, $y, $unitId)
    {
        // Check if it's a valid action
        if (!$this->checkAction('enlist')) {
            throw new BgaUserException(self::_("It is not your turn or this action is not allowed at this time"));
        }

        // Get the current player id
        $player_id = self::getActivePlayerId();

        // Check if it's really this player's turn
        $current_player_id = self::getCurrentPlayerId();
        if ($player_id != $current_player_id) {
            throw new BgaUserException(self::_("It's not your turn"));
        }

        // Validate the unit type
        $validUnitTypes = ['infantry', 'tank', 'battleship', 'chopper', 'artillery'];
        if (!in_array($unitType, $validUnitTypes)) {
            throw new BgaUserException(self::_("Invalid unit type"));
        }

        // Get the player's color
        $players = self::loadPlayersBasicInfos();
        $player_color = $players[$player_id]['player_color'];

        // Extract the color from the unitId (assuming unitId format like "infantry_red_001")
        $unit_color = explode('_', $unitId)[1];

        // Check if the player is selecting the correct color
        if ($player_color != $unit_color) {
            throw new BgaUserException(self::_("You can only select tokens of your own color"));
        }

        // Validate the coordinates
        if ($x < 0 || $x > 3 || $y < 0 || $y > 4) {
            throw new BgaUserException(self::_("Invalid coordinates"));
        }

        // Check if the space is empty
        if ($unitType != 'chopper') {
            $sql = "SELECT COUNT(*) FROM units WHERE x = $x AND y = $y";
            $count = self::getUniqueValueFromDB($sql);
            if ($count > 0) {
                throw new BgaUserException(self::_("This space is already occupied"));
            }
        }

        // Check if the player has available units of this type
        $sql = "SELECT COUNT(*) FROM units WHERE player_id = $player_id AND type = '$unitType'";
        $count = self::getUniqueValueFromDB($sql);
        if ($count >= 4) {
            throw new BgaUserException(self::_("You have no more units of this type available"));
        }

        if ($unitType == 'chopper' && $this->gamestate == '3') {
            // Check if the chopper is being enlisted on top of a friendly battleship
            $sql = "SELECT * FROM units WHERE x = $x AND y = $y AND type = 'battleship' AND player_id = $player_id";
            $battleship = self::getObjectFromDB($sql);

            if (!$battleship) {
                throw new BgaUserException(self::_("Chopper can only be enlisted on top of a friendly battleship"));
            }

            // Add the chopper on top of the battleship
            $sql = "INSERT INTO units (type, player_id, x, y, unit_id, is_stacked) VALUES ('chopper', $player_id, $x, $y, '$unitId', 1)";
            self::DbQuery($sql);

            // Update the battleship to show it's being occupied
            $sql = "UPDATE units SET is_occupied = 1 WHERE unit_id = '{$battleship['unit_id']}'";
            self::DbQuery($sql);

            // Notify all players about the new unit
            self::notifyAllPlayers('unitEnlisted', clienttranslate('${player_name} enlists a ${unit_type} at (${x},${y})'), [
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'unit_type' => $unitType,
                'x' => $x,
                'y' => $y,
                'unitId' => $unitId,
                'player_color' => $player_color
            ]);

        } else {

            // Add the unit to the board
            $sql = "INSERT INTO units (type, player_id, x, y, unit_id) VALUES ('$unitType', $player_id, $x, $y, '$unitId')";
            self::DbQuery($sql);

            // Get the player's color
            $players = self::loadPlayersBasicInfos();
            $player_color = $players[$player_id]['player_color'];

            // Notify all players about the new unit
            self::notifyAllPlayers('unitEnlisted', clienttranslate('${player_name} enlists a ${unit_type} at (${x},${y})'), [
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'unit_type' => $unitType,
                'x' => $x,
                'y' => $y,
                'unitId' => $unitId,
                'player_color' => $player_color,
                'special_unit_id' => 'chopper_'.$player_color.'_000'
            ]);
        }

        // Decrease the action counter
        $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
        $this->setGameStateValue('actionsRemaining', $actionsRemaining);

        // Check if this was the first round
        $isFirstRound = $this->getGameStateValue('isFirstRound');
        $isVeryFirstTurn = $this->getGameStateValue('isVeryFirstTurn');

        if ($isFirstRound) {
            if ($isVeryFirstTurn || $actionsRemaining == 0) {
                // If it's the very first turn or we've used all actions, move to next player
                $this->gamestate->nextState('nextPlayerFirstTurn');
            } else {
                // Otherwise, go to regular first round turn
                $this->gamestate->nextState('playerFirstTurn');
            }

            // Check if all players have placed their first unit
            $sql = "SELECT COUNT(*) FROM units";
            $unitCount = self::getUniqueValueFromDB($sql);
            if ($unitCount == count($this->loadPlayersBasicInfos())) {
                // If all players have placed their first unit, end first round
                $this->setGameStateValue('isFirstRound', 0);
            }
        } else {
            // Regular round logic
            if ($actionsRemaining == 0) {
                $this->gamestate->nextState('endTurn');
            } else {
                // Notify clients about the updated action count
                self::notifyAllPlayers('actionsRemaining', '', array(
                    'actionsRemaining' => $actionsRemaining
                ));
                // Stay in the current state without transitioning
                $this->gamestate->nextState('stayInState');
            }
        }
    }

    function move($unitId, $toX, $toY)
    {
        self::checkAction('move');

        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        $player_color = $players[$player_id]['player_color'];

        // Get the current position of the unit
        $sql = "SELECT x, y FROM units WHERE unit_Id = '$unitId'";
        $unit = self::getObjectFromDB($sql);

        if (!$unit) {
            throw new BgaUserException(self::_("Invalid unit"));
        }

        $fromX = $unit['x'];
        $fromY = $unit['y'];

        // Check if the move is valid
        if (!$this->isValidMove($player_color, $fromX, $fromY, $toX, $toY)) {
            throw new BgaUserException(self::_("Invalid move"));
        }

        if ($unit['type'] == 'chopper' && $this->gamestate == 3) {
            // Choppers can move anywhere
            $sql = "UPDATE units SET x = $toX, y = $toY WHERE unit_id = '$unitId'";
            self::DbQuery($sql);

            // Check if there's a unit in the destination
            $sql = "SELECT * FROM units WHERE x = $toX AND y = $toY AND unit_id != '$unitId'";
            $occupiedUnit = self::getObjectFromDB($sql);

            if ($occupiedUnit) {
                // Stack the chopper on top of the unit
                $sql = "UPDATE units SET is_stacked = 1 WHERE unit_id = '$unitId'";
                self::DbQuery($sql);

                // Mark the occupied unit as non-functional
                $sql = "UPDATE units SET is_occupied = 1 WHERE unit_id = '{$occupiedUnit['unit_id']}'";
                self::DbQuery($sql);
            } else {
                // If moving to an empty space, remove stacked status
                $sql = "UPDATE units SET is_stacked = 0 WHERE unit_id = '$unitId'";
                self::DbQuery($sql);
            }

            // Remove occupied status from the previous position
            $sql = "UPDATE units SET is_occupied = 0 WHERE x = {$unit['x']} AND y = {$unit['y']} AND unit_id != '$unitId'";
            self::DbQuery($sql);
        } else {
            // Perform the move
            $sql = "UPDATE units SET x = $toX, y = $toY WHERE unit_Id = '$unitId'";
            self::DbQuery($sql);

            // Notify all players about the move
            self::notifyAllPlayers('unitMoved', clienttranslate('${player_name} moved a unit from (${fromX},${fromY}) to (${toX},${toY})'), [
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'unit_Id' => $unitId,
                'fromX' => $fromX,
                'fromY' => $fromY,
                'toX' => $toX,
                'toY' => $toY
            ]);
        }

        // Decrease the action counter
        $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
        $this->setGameStateValue('actionsRemaining', $actionsRemaining);

        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('endTurn');
        } else {
            $this->gamestate->nextState('stayInState');
        }
    }

    function isValidMove($player_color, $fromX, $fromY, $toX, $toY)
    {
        $this->serverLog("entered function isValidMove", "");
        // Check if the destination is empty
        $sql = "SELECT COUNT(*) FROM units WHERE x = $toX AND y = $toY";
        if (self::getUniqueValueFromDB($sql) > 0) {
            $this->serverLog("more than 1 unique value found", "");
            return false;
        }

        // Check if it's an orthogonal adjacent move
        if (($fromX == $toX && abs($fromY - $toY) == 1) || ($fromY == $toY && abs($fromX - $toX) == 1)) {
            $this->serverLog("not an orthogonal adjacent move", "");
            return true;
        }

        // Check if it's an orthogonal jump to a space adjacent to a friendly unit
        $sql = "SELECT COUNT(*) FROM units WHERE player_id = (SELECT player_id FROM player WHERE player_color = '$player_color') AND (
        (x = $toX AND (y = $toY - 1 OR y = $toY + 1)) OR
        (y = $toY AND (x = $toX - 1 OR x = $toX + 1))
    )";
        if (self::getUniqueValueFromDB($sql) > 0) {
            $this->serverLog("an orthogonal jump to a space adjacent to a friendly unit", "");
            return true;
        }

        return false;
    }

    function fortify($unitId)
    {
        self::checkAction('fortify');

        $player_id = self::getActivePlayerId();

        // Fetch the selected unit from the database
        $sql = "SELECT unit_id, x, y, type, player_id FROM units WHERE unit_id = " . self::escapeString($unitId);
        $unit = self::getObjectFromDB($sql);

        $this->serverLog("Selected unit", $unit);

        if (!$unit) {
            throw new BgaUserException(self::_("Invalid unit"));
        }

        if ($unit['player_id'] != $player_id) {
            throw new BgaUserException(self::_("You can only fortify your own units"));
        }

        // Get adjacent units
        $adjacentUnits = $this->getAdjacentUnits($unit);
        $this->serverLog("Adjacent units", $adjacentUnits);

        // Check if there's a valid formation
        $formation = $this->findValidFormation($unit, $adjacentUnits);

        $this->serverLog("Resulting formation", $formation);

        if (!$formation) {
            throw new BgaUserException(self::_("No valid formation found"));
        }

        // Perform the fortification only on the selected unit
        $sql = "UPDATE units SET is_fortified = 1 WHERE unit_id = " . self::escapeString($unitId);
        self::DbQuery($sql);

        // Notify all players about the fortification
        self::notifyAllPlayers('unitsFortified', clienttranslate('${player_name} fortified a ${unit_type}'), [
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'unit_type' => $unit['type'],
            'unit' => $unit
        ]);

        // Decrease the action counter
        $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
        $this->setGameStateValue('actionsRemaining', $actionsRemaining);

        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('endTurn');
        } else {
            $this->gamestate->nextState('stayInState');
        }
    }

    private function findValidFormation($centerUnit, $adjacentUnits)
    {
        $this->serverLog("Entering findValidFormation", "");
        $this->serverLog("Center unit", $centerUnit);
        $this->serverLog("Adjacent units", $adjacentUnits);

        switch ($centerUnit['type']) {
            case 'battleship':
                return $this->checkBattleshipFormation($centerUnit, $adjacentUnits);
            case 'infantry':
                return $this->checkInfantryFormation($centerUnit, $adjacentUnits);
            case 'tank':
                return $this->checkTankFormation($centerUnit, $adjacentUnits);
            default:
                $this->serverLog("Unknown unit type", $centerUnit['type']);
                return null;
        }
    }

    private function checkBattleshipFormation($centerUnit, $adjacentUnits)
    {
        $this->serverLog("Checking Battleship Formation", "");
        $this->serverLog("Center Unit", $centerUnit);
        $this->serverLog("Adjacent Units", $adjacentUnits);

        $battleships = array_filter($adjacentUnits, function ($unit) {
            return $unit['type'] == 'battleship';
        });

        $this->serverLog("Filtered Battleships", $battleships);

        if (count($battleships) < 2) {
            $this->serverLog("Not enough adjacent battleships", count($battleships));
            return null;
        }

        // Convert string coordinates to integers
        $centerX = intval($centerUnit['x']);
        $centerY = intval($centerUnit['y']);

        $formations = [
            // Horizontal
            [[$centerX - 1, $centerY], [$centerX + 1, $centerY]],
            // Vertical
            [[$centerX, $centerY - 1], [$centerX, $centerY + 1]],
            // L-shape (4 possibilities)
            [[$centerX, $centerY - 1], [$centerX + 1, $centerY - 1]],
            [[$centerX, $centerY - 1], [$centerX - 1, $centerY - 1]],
            [[$centerX, $centerY + 1], [$centerX + 1, $centerY + 1]],
            [[$centerX, $centerY + 1], [$centerX - 1, $centerY + 1]],
            [[$centerX - 1, $centerY], [$centerX - 1, $centerY + 1]],
            [[$centerX - 1, $centerY], [$centerX - 1, $centerY - 1]],
            [[$centerX + 1, $centerY], [$centerX + 1, $centerY + 1]],
            [[$centerX + 1, $centerY], [$centerX + 1, $centerY - 1]]
        ];

        foreach ($formations as $index => $formation) {
            $this->serverLog("Checking formation", $index);
            $valid = true;
            $potentialFormation = [$centerUnit];
            foreach ($formation as $position) {
                $found = false;
                foreach ($battleships as $battleship) {
                    if (intval($battleship['x']) == $position[0] && intval($battleship['y']) == $position[1]) {
                        $potentialFormation[] = $battleship;
                        $found = true;
                        $this->serverLog("Found matching battleship", $battleship);
                        break;
                    }
                }
                if (!$found) {
                    $valid = false;
                    $this->serverLog("No matching battleship found for position", $position);
                    break;
                }
            }
            if ($valid) {
                $this->serverLog("Valid formation found", $potentialFormation);
                return $potentialFormation;
            }
        }

        $this->serverLog("No valid formation found", "");
        return null;
    }

    private function checkInfantryFormation($centerUnit, $adjacentUnits)
    {
        $infantry = array_filter($adjacentUnits, function ($unit) {
            return $unit['type'] == 'infantry';
        });

        if (count($infantry) < 1) {
            return null;
        }

        foreach ($infantry as $partner) {
            $potentialFormation = [$centerUnit, $partner];
            foreach ($adjacentUnits as $thirdUnit) {
                if ($thirdUnit['unit_id'] != $partner['unit_id'] && $this->isAdjacent($partner, $thirdUnit)) {
                    $potentialFormation[] = $thirdUnit;
                    return $potentialFormation;
                }
            }
        }

        return null;
    }

    private function checkTankFormation($centerUnit, $adjacentUnits)
    {
        $tanks = array_filter($adjacentUnits, function ($unit) {
            return $unit['type'] == 'tank';
        });

        if (count($tanks) < 1) {
            return null;
        }

        foreach ($tanks as $partner) {
            $potentialFormation = [$centerUnit, $partner];
            $dx = $partner['x'] - $centerUnit['x'];
            $dy = $partner['y'] - $centerUnit['y'];
            $thirdUnitPosition = [
                'x' => $partner['x'] + $dx,
                'y' => $partner['y'] + $dy
            ];
            foreach ($adjacentUnits as $thirdUnit) {
                if ($thirdUnit['x'] == $thirdUnitPosition['x'] && $thirdUnit['y'] == $thirdUnitPosition['y']) {
                    $potentialFormation[] = $thirdUnit;
                    return $potentialFormation;
                }
            }
        }

        return null;
    }

    private function isAdjacent($unit1, $unit2)
    {
        return abs($unit1['x'] - $unit2['x']) + abs($unit1['y'] - $unit2['y']) == 1;
    }

    private function getAdjacentUnits($unit)
    {
        $sql = "SELECT unit_id, x, y, type FROM units 
            WHERE player_id = " . self::escapeString($unit['player_id']) . "
            AND (
                (ABS(x - " . $unit['x'] . ") <= 1 AND ABS(y - " . $unit['y'] . ") <= 1)
                AND NOT (x = " . $unit['x'] . " AND y = " . $unit['y'] . ")
            )";
        $result = self::getObjectListFromDB($sql);
        $this->serverLog("getAdjacentUnits SQL", $sql);
        $this->serverLog("getAdjacentUnits result", $result);
        return $result;
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////// Attack ///////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////

    function attack($attackingUnitId, $defendingUnitId)
    {
        // Check if it's a valid action
        self::checkAction('attack');

        $player_id = self::getActivePlayerId();

        // Get attacking unit details
        $attackingUnit = $this->getUnitDetails($attackingUnitId);

        // Get defending unit details
        $defendingUnit = $this->getUnitDetails($defendingUnitId);

        // Check if the attacking unit belongs to the current player
        if ($attackingUnit['player_id'] != $player_id) {
            throw new BgaUserException(self::_("You can only attack with your own units"));
        }

        // Check if the attacking unit is in formation or fortified
        if (!$this->isUnitInFormation($attackingUnit) && !$attackingUnit['is_fortified']) {
            throw new BgaUserException(self::_("The attacking unit must be in formation or fortified"));
        }

        // Check if units are orthogonally adjacent
        if (!$this->areUnitsAdjacent($attackingUnit, $defendingUnit)) {
            throw new BgaUserException(self::_("The units must be orthogonally adjacent"));
        }

        if ($attackingUnit['type'] == 'chopper' && $this->gamestate == 3) {
            // Check if the chopper is attacking the unit directly beneath it
            if ($attackingUnit['x'] != $defendingUnit['x'] || $attackingUnit['y'] != $defendingUnit['y']) {
                throw new BgaUserException(self::_("Chopper can only attack the unit directly beneath it"));
            }

            // Check if the defending unit is also a chopper
            if ($defendingUnit['type'] == 'chopper') {
                throw new BgaUserException(self::_("Choppers cannot attack other choppers"));
            }
        } elseif ($defendingUnit['type'] == 'chopper' && $this->gamestate == 3) {
            // Choppers can't be attacked by other choppers
            if ($attackingUnit['type'] == 'chopper') {
                throw new BgaUserException(self::_("Choppers cannot attack other choppers"));
            }
        }

        // Check if a fortified unit is being attacked by a non-fortified unit
        if ($defendingUnit['is_fortified'] && !$attackingUnit['is_fortified']) {
            throw new BgaUserException(self::_("A fortified unit can only be attacked by another fortified unit"));
        }

        // Move the defending unit to the reinforcement track
        $this->moveUnitToReinforcementTrack($defendingUnit);

        // Get the updated reinforcement track state
        $updatedReinforcementTrack = $this->getReinforcementTrackState();

        // Notify all players about the attack
        self::notifyAllPlayers('unitAttacked', clienttranslate('${player_name} attacked ${defending_unit_type} with ${attacking_unit_type}'), [
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'attacking_unit_type' => $attackingUnit['type'],
            'defending_unit_type' => $defendingUnit['type'],
            'attacking_unit_id' => $attackingUnitId,
            'defending_unit_id' => $defendingUnitId,
            'reinforcementTrack' => $updatedReinforcementTrack
        ]);

        // Decrease the action counter
        $this->decreaseActionCounter();
    }

    private function getUnitDetails($unitId)
    {
        $sql = "SELECT * FROM units WHERE unit_id = '$unitId'";
        return self::getObjectFromDB($sql);
    }

    private function isUnitInFormation($unit)
    {
        // Get adjacent units
        $adjacentUnits = $this->getAdjacentUnits($unit);

        // Check formation based on unit type
        switch ($unit['type']) {
            case 'battleship':
                return $this->checkBattleshipFormation($unit, $adjacentUnits) !== null;
            case 'infantry':
                return $this->checkInfantryFormation($unit, $adjacentUnits) !== null;
            case 'tank':
                return $this->checkTankFormation($unit, $adjacentUnits) !== null;
            default:
                return false; // Unknown unit type
        }
    }

    private function areUnitsAdjacent($unit1, $unit2)
    {
        $dx = abs($unit1['x'] - $unit2['x']);
        $dy = abs($unit1['y'] - $unit2['y']);
        return ($dx + $dy == 1);
    }

    private function moveUnitToReinforcementTrack($unit)
    {
        // Check if the unit is already in the reinforcement track
        $sql = "SELECT * FROM reinforcement_track WHERE unit_id = '" . $unit['unit_id'] . "'";
        $existingUnit = self::getObjectFromDB($sql);

        if ($existingUnit) {
            // If the unit is already in the track, update its position
            $sql = "UPDATE reinforcement_track SET position = 1, is_fortified = " . ($unit['is_fortified'] ? '1' : '0') . " WHERE unit_id = '" . $unit['unit_id'] . "'";
            self::DbQuery($sql);
        } else {
            // If the unit is not in the track, insert it
            $sql = "INSERT INTO reinforcement_track (unit_id, position, is_fortified) VALUES ('" . $unit['unit_id'] . "', 1, " . ($unit['is_fortified'] ? '1' : '0') . ")";
            self::DbQuery($sql);
        }

        // Move all other units down one position
        $sql = "UPDATE reinforcement_track SET position = position + 1 WHERE unit_id != '" . $unit['unit_id'] . "'";
        self::DbQuery($sql);

        // Check if any unit has moved to position 5
        $sql = "SELECT * FROM reinforcement_track WHERE position = 5";
        $unitsToReturn = self::getCollectionFromDb($sql);

        foreach ($unitsToReturn as $unitToReturn) {
            $this->moveUnitToSupply($unitToReturn);
            $sql = "DELETE FROM reinforcement_track WHERE unit_id = '" . $unitToReturn['unit_id'] . "'";
            self::DbQuery($sql);
        }

        // Remove the unit from the board
        $sql = "DELETE FROM units WHERE unit_id = '" . $unit['unit_id'] . "'";
        self::DbQuery($sql);

        // Notify clients about the reinforcement track update
        self::notifyAllPlayers('reinforcementTrackUpdated', '', [
            'reinforcementTrack' => $this->getReinforcementTrackState()
        ]);
    }

    private function moveUnitToSupply($unit)
    {
        // Add the unit back to the units table
        $sql = "INSERT INTO units (id, type, player_id, x, y, unit_id, is_fortified) 
                VALUES (NULL, '{$unit['type']}', {$unit['player_id']}, NULL, NULL, '{$unit['unit_id']}', {$unit['is_fortified']})";
        self::DbQuery($sql);

        $sql = "DELETE FROM reinforcement_track WHERE unit_id = '{$unit['unit_id']}'";
        self::DbQuery($sql);

        // Notify clients about the unit returning to supply
        self::notifyAllPlayers('unitReturnedToSupply', clienttranslate('A ${unit_type} has returned to ${player_name}\'s supply'), [
            'unit_type' => $unit['type'],
            'player_name' => self::getPlayerNameById($unit['player_id']),
            'unit_id' => $unit['unit_id'],
            'is_fortified' => $unit['is_fortified']
        ]);
    }

    private function getReinforcementTrackState()
    {
        $sql = "SELECT * FROM reinforcement_track ORDER BY position ASC";
        return self::getCollectionFromDb($sql);
    }

    private function decreaseActionCounter()
    {
        $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
        $this->setGameStateValue('actionsRemaining', $actionsRemaining);

        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('endTurn');
        } else {
            self::notifyAllPlayers('actionsRemaining', '', [
                'actionsRemaining' => $actionsRemaining
            ]);
        }
    }

    protected function escapeString($string)
    {
        if (is_null($string)) {
            return 'NULL';
        }

        return "'" . self::escapeStringForDB($string) . "'";
    }

    private function serverLog($varName, $varValue)
    {
        $this->dump($varName, json_encode($varValue, JSON_PRETTY_PRINT));
    }

    public function endTurn()
    {
        self::checkAction('endTurn');

        // Any end-of-turn logic here

        $this->gamestate->nextState('next');
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        //        if( $from_version <= 1404301345 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //        }
        //        if( $from_version <= 1405061421 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //        }
        //        // Please add your future database scheme changes here
        //
        //


    }
}
