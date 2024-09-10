<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Fortify implementation : © Nirmatt Gopal nrmtgpl@gmail.com
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
    // Add these new properties at the beginning of the Fortify class
    private $volleyCount = 0;
    private $playerVolleyWins = array();

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
        // Initialize the very first turn flag
        $this->setGameStateInitialValue('isVeryFirstTurn', 1);
        // Initialize the actions counter (1 for first round)
        $this->setGameStateInitialValue('actionsRemaining', 1);

        // Get the ID of the first player (assuming you want the first registered player to go first)
        $first_player_id = array_keys($players)[0];

        // Explicitly set the first player as active
        $this->gamestate->changeActivePlayer($first_player_id);

        // Initialize player scores
        $sql = "UPDATE player SET player_score = 0";
        self::DbQuery($sql);

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
        $result = array(
            'players' => array(),
            'units' => array(),
            'POINTS_TITLE' => clienttranslate("Points"),
            'POINTS_LABEL' => clienttranslate("pts")
        );

        self::reloadPlayersBasicInfos();
        // Get players & their data
        $sql = "SELECT player_id id, player_name name, player_score score, 
                infantry_enlist_count infantryEnlistCount FROM player";
        $result['players'] = self::getCollectionFromDb($sql);

        // $this->serverLog("players", $result['players']);

        // Get units & their data
        $sql = "SELECT id, type, player_id, x, y, unit_id, is_fortified, in_formation FROM units";
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
        if (self::getGameStateValue('gameVariant') == 3 || self::getGameStateValue('gameVariant') == 5) {
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
        // Get the current volley count
        $volleyCount = $this->getVolleyCount();

        // Get the total number of units per player
        $totalUnits = (self::getGameStateValue('gameVariant') == 3 
                        || self::getGameStateValue('gameVariant') == 5) ? 14 : 12;
        $player_id = self::getActivePlayerId();

        // Calculate the progress of the current volley
        $player1FortifiedUnits = $this->getPlayerFortifiedUnitsCount($player_id);
        $player2FortifiedUnits = $this->getPlayerFortifiedUnitsCount(self::getPlayerAfter($player_id));

        $currentVolleyProgress = (($player1FortifiedUnits + $player2FortifiedUnits) / (2 * $totalUnits)) * 100;

        // Calculate the overall game progress
        $overallProgress = (($volleyCount - 1) * 100 + $currentVolleyProgress) / 3;

        // Ensure the progress doesn't exceed 100%
        return min(100, max(0, $overallProgress));
    }

    // Helper function to get the count of fortified units for a player
    private function getPlayerFortifiedUnitsCount($playerId)
    {
        $fortifiedOnBoard = self::getUniqueValueFromDB("SELECT COUNT(*) FROM units WHERE player_id = $playerId AND is_fortified = 1");
        $fortifiedInReinforcement = self::getUniqueValueFromDB("SELECT COUNT(*) FROM reinforcement_track WHERE player_id = $playerId AND is_fortified = 1");
        return $fortifiedOnBoard + $fortifiedInReinforcement;
    }

    function stNextPlayer()
    {
        $this->serverLog("Entered stNextPlayer method", "");

        // Check for game end conditions
        if ($this->checkGameEnd()) {
            return; // The game has ended, no need to proceed
        }

        $isFirstRound = $this->getGameStateValue('isFirstRound');
        $isVeryFirstTurn = $this->getGameStateValue('isVeryFirstTurn');
        $actionsRemaining = $this->getGameStateValue('actionsRemaining');

        $this->serverLog("Current state", array(
            "isFirstRound" => $isFirstRound,
            "isVeryFirstTurn" => $isVeryFirstTurn,
            "actionsRemaining" => $actionsRemaining
        ));

        // Move to the next player
        $player_id = self::getActivePlayerId();

        // Reset the infantry enlistment count for the new active player
        $this->setInfantryEnlistCount($player_id, 0);

        if ($isFirstRound) {
            if ($isVeryFirstTurn) {
                $this->setGameStateValue('isVeryFirstTurn', 0);
                $actionsRemaining = 2;
                $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                $this->gamestate->nextState('playerFirstEnlist');
            } else {
                // First turn is over.
                $unitCount = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM units");
                $playerCount = count($this->loadPlayersBasicInfos());

                // self::serverLog("Infantry Enlist Count", self::getInfantryEnlistCount($player_id));

                if (self::getInfantryEnlistCount($player_id)) {
                    // Not all players have placed their first unit yet

                    $actionsRemaining = 2;
                    $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                    $this->gamestate->nextState('playerFirstTurn');
                } else {
                    // All players have placed their first unit, end first round
                    $player_id = self::activeNextPlayer();
                    self::giveExtraTime($player_id);

                    $this->setGameStateValue('isFirstRound', 0);
                    $actionsRemaining = 2;
                    $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                    $this->gamestate->nextState('playerTurn');
                }
            }
        } else {
            if ($actionsRemaining == 0) {
                // $this->serverLog("No actions are remaining", "");
                $actionsRemaining = 2;
                $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                $player_id = self::activeNextPlayer();
                self::giveExtraTime($player_id);
            }

            $this->gamestate->nextState('playerTurn');
        }

        // $this->serverLog("Notify clients about the updated action count", "");
        // $this->serverLog("actionsRemaining", $actionsRemaining);
        // Notify clients about the updated action count
        self::notifyAllPlayers('actionsRemaining', '', array(
            'actionsRemaining' => $actionsRemaining
        ));

        $this->serverLog("Exited stNextPlayer method", "");
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

    // Get all the units in DB
    private function getAllUnits()
    {
        $sql = "SELECT * FROM units";
        return self::getObjectListFromDB($sql);
    }

    private function calculateAndUpdatePoints($playerId)
    {
        $this->serverLog("Inside calculateAndUpdatePoints method", "");
        // $this->serverLog("playerId", $playerId);

        $points = 0;

        // Points for fortified units on the board
        $sql = "SELECT type, COUNT(*) as count 
                FROM units 
                WHERE player_id = $playerId AND is_fortified = 1 
                GROUP BY type";
        $fortifiedUnitsOnBoard = self::getCollectionFromDb($sql);
        // $this->serverLog("fortifiedUnitsOnBoard", $fortifiedUnitsOnBoard);

        // Points for fortified units in reinforcement track
        $sql = "SELECT type, COUNT(*) as count 
                FROM reinforcement_track 
                WHERE player_id = $playerId AND is_fortified = 1";
        $fortifiedUnitsInReinforcement = self::getCollectionFromDb($sql);
        // Debug logging
        // $this->serverLog("Fortified units on board:", $fortifiedUnitsOnBoard);
        // $this->serverLog("Fortified units in reinforcement:", $fortifiedUnitsInReinforcement);

        // Combine the results safely
        $allFortifiedUnits = $fortifiedUnitsOnBoard;
        foreach ($fortifiedUnitsInReinforcement as $type => $data) {
            if (isset($allFortifiedUnits[$type])) {
                $allFortifiedUnits[$type]['count'] += $data['count'];
            } else {
                $allFortifiedUnits[$type] = $data;
            }
        }

        // Debug logging
        $this->serverLog("All fortified units:", $allFortifiedUnits);

        foreach ($allFortifiedUnits as $unit) {
            switch ($unit['type']) {
                case 'battleship':
                case 'artillery':
                    $points += 3 * $unit['count'];
                    break;
                case 'tank':
                    $points += 2 * $unit['count'];
                    break;
                case 'infantry':
                case 'chopper':
                    $points += $unit['count'];
                    break;
            }
        }

        // Deduct points for units in Reinforcement Track
        $sql = "SELECT COUNT(*) as count FROM reinforcement_track WHERE player_id = $playerId";
        $reinforcementUnits = self::getUniqueValueFromDB($sql);
        // $this->serverLog("reinforcementUnits", $reinforcementUnits);
        // $this->serverLog("points", $points);
        $points -= 2 * $reinforcementUnits;

        // Add 7 points if player won by 2x2 Fortification
        if ($this->check2x2Fortification($playerId)) {
            $points += 7;
        }

        // Update player's points
        $this->setPlayerPoints($playerId, $points);

        // Notify all players about the updated points
        self::notifyAllPlayers('pointsUpdated', '', array(
            'playerId' => $playerId,
            'points' => $points
        ));
    }

    private function setPlayerPoints($player_id, $points)
    {
        $sql = "UPDATE player SET player_score = $points WHERE player_id = $player_id";
        self::DbQuery($sql);
    }

    private function getGameVariant()
    {
        return self::getGameStateValue('gameVariant');
    }

    private function setUnitFormationAndUpdate($unit, $value)
    {
        self::serverLog("Inside setUnitFormation method", "");

        if (($value == 1 && $unit['in_formation'] == 0) || ($value == 0 && $unit['in_formation'] == 1)) {
            $sql = "UPDATE units SET in_formation = $value
                    WHERE unit_id = " . self::escapeString($unit['unit_id']);
            self::DbQuery($sql);

            self::notifyUpdateUnit($unit['unit_id'], $value);
        }
    }

    // Notify all players about the unit in formation
    private function notifyUpdateUnit($unit_id, $value)
    {
        self::notifyAllPlayers('updateUnit', clienttranslate(''), [
            'unit_id' => $unit_id,
            'is_in_formation' => $value
        ]);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in fortify.action.php)
    */

    function enlist($unitType, $x, $y, $unitId, $is_fortified)
    {
        // $this->serverLog("is_fortified", $is_fortified);
        $is_fortified = (int)$is_fortified;

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

        // Extract the color from the unitId (unitId format like "infantry_red_001")
        $unit_color = explode('_', $unitId)[1];

        // Check if the player is selecting the correct color
        if ($player_color != $unit_color) {
            throw new BgaUserException(self::_("You can only select tokens of your own color"));
        }

        // Validate the coordinates
        if (self::getGameStateValue('gameVariant') == 4 || self::getGameStateValue('gameVariant') == 5) {
            if ($x < 0 || $x > 4 || $y < 0 || $y > 4) {
                throw new BgaUserException(self::_("Invalid coordinates"));
            }
        } else {
            if ($x < 0 || $x > 3 || $y < 0 || $y > 4) {
                throw new BgaUserException(self::_("Invalid coordinates"));
            }
        }

        // Check if the space is empty
        if ($unitType != 'chopper') {
            $sql = "SELECT COUNT(*) FROM units WHERE x = $x AND y = $y";
            $count = self::getUniqueValueFromDB($sql);
            if ($count > 0) {
                throw new BgaUserException(self::_("This space is already occupied"));
            }
        }

        // $this->serverLog("unitType", $unitType);
        // $this->serverLog("gamestate", $this->gamestate);

        $infantryEnlistCount = $this->getInfantryEnlistCount($player_id);

        $notificationData = [
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'unit_type' => $unitType,
            'x' => $x,
            'y' => $y,
            'unitId' => $unitId,
            'player_color' => $player_color,
        ];

        // Perform the action:
        if ($unitType == 'chopper' && (
                self::getGameStateValue('gameVariant') == 3 
                || self::getGameStateValue('gameVariant') == 5)) {
            // Check if the chopper is being enlisted on top of a friendly battleship
            $sql = "SELECT * FROM units WHERE x = $x AND y = $y AND type = 'battleship' AND player_id = $player_id";
            $battleship = self::getObjectFromDB($sql);

            if (!$battleship) {
                throw new BgaUserException(self::_("Chopper can only be enlisted on top of a friendly battleship"));
            }

            // Add the chopper on top of the battleship
            $sql = "INSERT INTO units (type, player_id, x, y, unit_id, is_stacked, is_fortified) 
                    VALUES ('chopper', $player_id, $x, $y, '$unitId', 1, '$is_fortified')";
            self::DbQuery($sql);

            // Update the battleship to show it's being occupied
            $sql = "UPDATE units SET is_occupied = 1 WHERE unit_id = '{$battleship['unit_id']}'";
            self::DbQuery($sql);

            $notificationData['special_unit_id'] = 'chopper_' . $player_color . '_000';
        } else {

            // Add the unit to the board
            $sql = "SELECT COUNT(*) FROM units WHERE unit_id = '$unitId'";
            $count = self::getUniqueValueFromDB($sql);

            // If the unit is already added in units db, then update the position for enlisting
            if ($count > 0) {
                $sql = "UPDATE units SET x = $x, y = $y WHERE unit_id = '$unitId'";
                self::DbQuery($sql);
            } else {
                $sql = "INSERT INTO units (type, player_id, x, y, unit_id, is_fortified) 
                        VALUES ('$unitType', $player_id, $x, $y, '$unitId', '$is_fortified')";
                self::DbQuery($sql);
            }

            // Get the player's color
            $players = self::loadPlayersBasicInfos();
            $player_color = $players[$player_id]['player_color'];

            // If unit type is infantry, one more infantry unit can be enlisted for free 
            if ($unitType == 'infantry') {
                if ($infantryEnlistCount == 0) {
                    // This is the first infantry enlistment
                    $this->setInfantryEnlistCount($player_id, 1);
                    $infantryEnlistCount = 1;
                } else {
                    // This is the second infantry enlistment
                    $this->setInfantryEnlistCount($player_id, 0);
                    $infantryEnlistCount = 0;
                }
            } else {
                // If it's not an infantry, reset the infantry enlist count
                $this->setInfantryEnlistCount($player_id, 0);
                $infantryEnlistCount = 0;
            }

            $notificationData['infantryEnlistCount'] = $infantryEnlistCount;
        }

        // Notify all players about the new unit
        self::notifyAllPlayers(
            'unitEnlisted',
            clienttranslate('${player_name} enlists a ${unit_type} at (${x},${y})'),
            $notificationData
        );

        // Check if this was the first round
        $isFirstRound = $this->getGameStateValue('isFirstRound');
        $isVeryFirstTurn = $this->getGameStateValue('isVeryFirstTurn');
        $actionsRemaining = $this->getGameStateValue('actionsRemaining');

        $this->serverLog("After processing", array(
            "isFirstRound" => $isFirstRound,
            "isVeryFirstTurn" => $isVeryFirstTurn,
            "actionsRemaining" => $actionsRemaining
        ));

        if ($isFirstRound) {
            if (($isVeryFirstTurn || $actionsRemaining == 0)) {
                if ($infantryEnlistCount == 1) {
                    // $this->serverLog("First infantry enlisted", "");
                    //$this->gamestate->nextState('stayInState');
                } else {
                    // If it's the very first turn or we've used all actions, move to next player
                    // $this->serverLog("If it's the very first turn or we've used all actions, move to next player", "");
                    $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
                    $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                    $this->setGameStateValue('isVeryFirstTurn', 0);
                    $this->gamestate->nextState('nextPlayer');
                }
            } else {
                // Either this is second player's first turn or
                // Otherwise, go to regular first round turn
                // $this->serverLog("Otherwise, go to regular first round turn", "");
                if ($infantryEnlistCount == 1) {
                    // $this->serverLog("First infantry enlisted", "");
                } else {
                    // $this->serverLog("Enlist is done or skipped", "");
                    $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
                    $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                    $this->gamestate->nextState('nextPlayer');
                }
            }
            if ($infantryEnlistCount != 1) {
                // Check if all players have placed their first unit

                $this->serverLog("Check if all players have placed their first unit", "");

                $sql = "SELECT COUNT(*) FROM units";
                $unitCount = self::getUniqueValueFromDB($sql);

                // $this->serverLog("unitCount", $unitCount);
                // $this->serverLog("playerCount", count($this->loadPlayersBasicInfos()));

                if ($unitCount >= count($this->loadPlayersBasicInfos())) {
                    // If all players have placed their first unit, end first round
                    // $this->serverLog("If all players have placed their first unit, end first round", "");

                    $this->setGameStateValue('isFirstRound', 0);
                    $this->gamestate->nextState('nextPlayer');
                }
            }
        } else {
            // Regular round logic
            // $this->serverLog("Regular round logic", "");

            if ($actionsRemaining == 0) {
                if ($infantryEnlistCount == 1) {
                    // $this->serverLog("one infantry enlisted. infantryEnlistCount = ", "$infantryEnlistCount");
                } else {
                    // $this->serverLog("No infantry to enlist. infantryEnlistCount = ", "$infantryEnlistCount");

                    $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
                    $this->setGameStateValue('actionsRemaining', $actionsRemaining);
                    $this->gamestate->nextState('nextPlayer');
                }
            } else {
                // $this->serverLog("More than 1 actions remaining. actionsRemaining = ", "$actionsRemaining");
                // $this->serverLog("infantryEnlistCount = ", "$infantryEnlistCount");

                if ($infantryEnlistCount == 1) {
                } else {
                    $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
                    $this->setGameStateValue('actionsRemaining', $actionsRemaining);

                    if ($actionsRemaining == 0) {
                        //$actionsRemaining = 2;
                        //$this->setGameStateValue('actionsRemaining', $actionsRemaining);
                        // Notify clients about the updated action count

                        $this->gamestate->nextState('nextPlayer');
                    } else if ($actionsRemaining == 1) {
                        self::notifyAllPlayers('actionsRemaining', '', array(
                            'actionsRemaining' => $actionsRemaining
                        ));
                    }
                }
            }
        }

        // Update state of units
        $units = self::getAllUnits();
        self::serverLog("all units during fortify", $units);

        foreach ($units as $unit) {
            self::isUnitInFormation($unit);
        }
    }

    /**
     * Get the infantry enlist count for a player
     * 
     * @param int $playerId The ID of the player
     * @return int The infantry enlist count
     */
    private function getInfantryEnlistCount($playerId)
    {
        return (int)self::getUniqueValueFromDB("SELECT infantry_enlist_count FROM player WHERE player_id = $playerId");
    }

    /**
     * Set the infantry enlist count for a player
     * 
     * @param int $playerId The ID of the player
     * @param int $count The new infantry enlist count
     */
    private function setInfantryEnlistCount($playerId, $count)
    {
        self::DbQuery("UPDATE player SET infantry_enlist_count = $count WHERE player_id = $playerId");
    }

    function move($unitId, $unitType, $toX, $toY)
    {
        $this->serverLog("Entered move action", "");

        self::checkAction('move');

        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        $player_color = $players[$player_id]['player_color'];

        $unit = self::getUnitDetails($unitId);

        if (!$unit) {
            throw new BgaUserException(self::_("Invalid unit"));
        }

        $fromX = $unit['x'];
        $fromY = $unit['y'];

        // Check if the move is valid
        if ($unitType != 'tank' && $unitType != 'chopper' && !$this->isValidMove($player_color, $fromX, $fromY, $toX, $toY)) {
            throw new BgaUserException(self::_("Invalid move"));
        }

        // $this->serverLog("gameVariant", self::getGameStateValue('gameVariant'));

        if ($unitType == 'chopper' && (
            self::getGameStateValue('gameVariant') == 3
            || self::getGameStateValue('gameVariant') == 5)) {
            // Choppers can move anywhere
            $sql = "UPDATE units SET x = $toX, y = $toY WHERE unit_id = '$unitId'";
            self::DbQuery($sql);

            // Check if there's a unit in the destination
            $sql = "SELECT * FROM units WHERE x = $toX AND y = $toY AND unit_id != '$unitId'";
            $occupiedUnit = self::getObjectFromDB($sql);

            $this->serverLog("occupiedUnit sql", $sql);

            if ($occupiedUnit) {
                // $this->serverLog("has occupiedUnit", $occupiedUnit);
                // Stack the chopper on top of the unit
                $sql = "UPDATE units SET is_stacked = 1 WHERE unit_id = '$unitId'";
                self::DbQuery($sql);

                // $this->serverLog("updated is_stacked = 1", "");

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
            // Check if player has enough actions
            if ($unitType == 'artillery' && $this->getGameStateValue('actionsRemaining') < 2) {
                throw new BgaUserException(self::_("Artillery requires 2 actions to move"));
            }

            // Perform the move
            $sql = "UPDATE units SET x = $toX, y = $toY WHERE unit_Id = '$unitId'";
            self::DbQuery($sql);
        }

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

        // If infantry enlist was previous move, cancel the free infantry enlist
        // And decrease the actionremaining counter by 1
        $infantryEnlistCount = $this->getInfantryEnlistCount($player_id);
        if ($infantryEnlistCount == 1) {
            $this->setInfantryEnlistCount($player_id, 0);
            $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
            $this->setGameStateValue('actionsRemaining', $actionsRemaining);
        }

        if ($unitType == 'artillery') {
            // Artillery consumes 2 actions for a move
            $actionsRemaining = 0;
            $this->setGameStateValue('actionsRemaining', $actionsRemaining);
        } else {
            // Decrease the action counter
            $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
            $this->setGameStateValue('actionsRemaining', $actionsRemaining);
        }


        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('endTurn');
        } else {
            $this->gamestate->nextState('stayInState');
        }

        // Update state of units
        $units = self::getAllUnits();
        self::serverLog("all units during fortify", $units);

        foreach ($units as $unit) {
            self::isUnitInFormation($unit);
        }

        // If the move causes the 2x2 foritified formation to occur, trigger win condition
        if ($this->checkGameEnd()) {
            return; // The game has ended, no need to proceed
        }
    }

    function isValidMove($player_color, $fromX, $fromY, $toX, $toY)
    {
        $this->serverLog("entered function isValidMove", "");
        // Check if the destination is empty
        $sql = "SELECT COUNT(*) FROM units WHERE x = $toX AND y = $toY";
        if (self::getUniqueValueFromDB($sql) > 0) {
            // $this->serverLog("more than 1 unique value found", "");
            return false;
        }

        // Check if it's an orthogonal adjacent move
        if (($fromX == $toX && abs($fromY - $toY) == 1) || ($fromY == $toY && abs($fromX - $toX) == 1)) {
            // $this->serverLog("not an orthogonal adjacent move", "");
            return true;
        }

        // Check if it's an orthogonal jump to a space adjacent to a friendly unit
        $sql = "SELECT COUNT(*) FROM units WHERE player_id = 
                (SELECT player_id FROM player WHERE player_color = '$player_color') AND (
                (x = $toX AND (y = $toY - 1 OR y = $toY + 1)) OR
                (y = $toY AND (x = $toX - 1 OR x = $toX + 1)))";
        if (self::getUniqueValueFromDB($sql) > 0) {
            // $this->serverLog("an orthogonal jump to a space adjacent to a friendly unit", "");
            return true;
        }

        return false;
    }

    function fortify($unitId)
    {
        self::serverLog("Inside Fortify method", "");
        self::checkAction('fortify');

        $player_id = self::getActivePlayerId();

        $infantryEnlistCount = $this->getInfantryEnlistCount($player_id);
        // self::serverLog("infantryEnlistCount", $infantryEnlistCount);

        $unit = self::getUnitDetails($unitId);

        // $this->serverLog("Selected unit for fortification", $unit);

        if (!$unit) {
            throw new BgaUserException(self::_("Invalid unit"));
        }

        if ($unit['player_id'] != $player_id) {
            throw new BgaUserException(self::_("You can only fortify your own units"));
        }

        if ($unit['type'] == 'artillery' && $this->getGameStateValue('actionsRemaining') < 2) {
            throw new BgaUserException(self::_("Artillery requires 2 actions to fortify"));
        }

        // Get adjacent units
        $adjacentUnits = $this->getAdjacentUnits($unit);
        // $this->serverLog("Adjacent units", $adjacentUnits);

        // Check if there's a valid formation
        $formation = $this->findValidFormation($unit, $adjacentUnits);

        // $this->serverLog("Resulting formation", $formation);

        if (!$formation && $unit['type'] != 'artillery') {
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

        // If infantry enlist was previous move, cancel the free infantry enlist
        // And decrease the actionremaining counter by 1

        if ($infantryEnlistCount == 1) {
            $actionsRemaining = $this->getGameStateValue('actionsRemaining');
            // self::serverLog("actions remaining", $actionsRemaining);

            if ($actionsRemaining == 2) {
                $this->setInfantryEnlistCount($player_id, 0);
                $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
                $this->setGameStateValue('actionsRemaining', $actionsRemaining);
            } else {
                throw new BgaUserException("This action is not allowed!");
            }
        }

        if ($unit['type'] == 'artillery') {
            // Artillery consumes 2 actions for a fortification
            $actionsRemaining = 0;
            $this->setGameStateValue('actionsRemaining', $actionsRemaining);
        } else {
            // Decrease the action counter
            $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
            $this->setGameStateValue('actionsRemaining', $actionsRemaining);
        }

        // Check for game end conditions
        if ($this->checkGameEnd()) {
            return; // Game has ended, no need to proceed
        }

        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('endTurn');
        } else {
            // Update the UI about remaining actions
            self::notifyAllPlayers('actionsRemaining', '', [
                'actionsRemaining' => $actionsRemaining
            ]);
            $this->gamestate->nextState('stayInState');
        }

        // Update state of units
        $units = self::getAllUnits();
        self::serverLog("all units during fortify", $units);

        foreach ($units as $unit) {
            self::isUnitInFormation($unit);
        }
    }

    private function findValidFormation($centerUnit, $adjacentUnits)
    {
        $this->serverLog("Entering findValidFormation", "");
        // $this->serverLog("Center unit", $centerUnit);
        // $this->serverLog("Adjacent units", $adjacentUnits);

        switch ($centerUnit['type']) {
            case 'battleship':
                $formation = $this->checkBattleshipFormation($centerUnit, $adjacentUnits);
                $this->serverLog("Battleship formation result", $formation);
                return $formation;
            case 'infantry':
                return $this->checkInfantryFormation($centerUnit, $adjacentUnits);
            case 'tank':
                return $this->checkTankFormation($centerUnit, $adjacentUnits);
            case 'chopper':
                return $this->checkChopperFormation($centerUnit);
            default:
                $this->serverLog("Unknown unit type", $centerUnit['type']);
                return null;
        }
    }

    private function checkChopperFormation($chopper)
    {
        self::serverLog("Inside method checkChopperFormation", "");
        self::serverLog("chopper", $chopper);

        // Check if there's a friendly fortified battleship below
        $battleshipBelow = $this->getUnitAtPosition($chopper['x'], $chopper['y'], 'battleship');
        self::serverLog("battleshipBelow", $battleshipBelow);

        if (
            !$battleshipBelow || $battleshipBelow['type'] !== 'battleship' ||
            $battleshipBelow['player_id'] != $chopper['player_id'] || !$battleshipBelow['is_fortified']
        ) {
            self::setUnitFormationAndUpdate($chopper, 0);
            throw new BgaUserException(self::_("Chopper must be above a friendly fortified Battleship to fortify"));
        }
        self::setUnitFormationAndUpdate($chopper, 1);
        return $battleshipBelow;
    }

    private function getUnitAtPosition($x, $y, $unitType)
    {
        $sql = "SELECT * FROM units WHERE x = $x AND y = $y AND type = " . self::escapeString($unitType);
        return self::getObjectFromDB($sql);
    }

    private function checkBattleshipFormation($centerUnit, $adjacentUnits)
    {
        $this->serverLog("Checking Battleship Formation", "");
        // $this->serverLog("Center Unit", $centerUnit);
        // $this->serverLog("Adjacent Units", $adjacentUnits);

        // Check if the center unit is a single Battleship on a Shore space
        if ($this->isBattleshipOnShore($centerUnit)) {
            // $this->serverLog("Single Battleship on Shore space - can fortify", $centerUnit);

            self::setUnitFormationAndUpdate($centerUnit, 1);
            return [$centerUnit];
        }

        // Get all battleships in a wider area
        $allNearbyBattleships = $this->getNearbyBattleships($centerUnit);
        // $this->serverLog("All Nearby Battleships", $allNearbyBattleships);

        // Add the center unit to the nearby battleships list
        array_unshift($allNearbyBattleships, $centerUnit);
        // $this->serverLog("All Battleships including center", $allNearbyBattleships);

        // Check for any valid formation
        $formation = $this->checkFormationWithBattleships($centerUnit, $allNearbyBattleships);
        if ($formation !== null) {
            // $this->serverLog("Valid formation found", $formation);
            self::setUnitFormationAndUpdate($centerUnit, 1);
            return $formation;
        }

        self::setUnitFormationAndUpdate($centerUnit, 0);
        $this->serverLog("No valid formation found", "");
        return null;
    }

    private function checkFormationWithBattleships($centerUnit, $nearbyBattleships)
    {
        $centerX = intval($centerUnit['x']);
        $centerY = intval($centerUnit['y']);

        $this->serverLog("Checking formation with center at ($centerX, $centerY)", "");

        // Check vertical formations
        $topUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX, $centerY - 1);
        $bottomUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX, $centerY + 1);
        $farBottomUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX, $centerY + 2);

        // Check horizontal formations
        $leftUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX - 1, $centerY);
        $rightUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX + 1, $centerY);
        $farRightUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX + 2, $centerY);

        // Check diagonal units for L-shape formations
        $topRightUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX + 1, $centerY - 1);
        $bottomRightUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX + 1, $centerY + 1);
        $topLeftUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX - 1, $centerY - 1);
        $bottomLeftUnit = $this->findBattleshipAtPosition($nearbyBattleships, $centerX - 1, $centerY + 1);

        $this->serverLog("Adjacent Units", [
            'Top' => $topUnit,
            'Bottom' => $bottomUnit,
            'Left' => $leftUnit,
            'Right' => $rightUnit,
            'FarBottom' => $farBottomUnit,
            'FarRight' => $farRightUnit,
            'TopRight' => $topRightUnit,
            'BottomRight' => $bottomRightUnit,
            'TopLeft' => $topLeftUnit,
            'BottomLeft' => $bottomLeftUnit
        ]);

        // Check all possible formations
        $formations = [
            // Vertical formations
            [$topUnit, $centerUnit, $bottomUnit],
            [$centerUnit, $bottomUnit, $farBottomUnit],
            // Horizontal formations
            [$leftUnit, $centerUnit, $rightUnit],
            [$centerUnit, $rightUnit, $farRightUnit],
            // L-shape formations
            [$centerUnit, $rightUnit, $topRightUnit],
            [$centerUnit, $rightUnit, $bottomRightUnit],
            [$topUnit, $centerUnit, $rightUnit],
            [$bottomUnit, $centerUnit, $rightUnit],
            // Inverse L-shape formations
            [$topRightUnit, $rightUnit, $centerUnit],
            [$bottomRightUnit, $rightUnit, $centerUnit],
            [$rightUnit, $topUnit, $centerUnit],
            [$rightUnit, $bottomUnit, $centerUnit],
            // New L-shape formations with center at corner
            [$centerUnit, $topUnit, $leftUnit],
            [$centerUnit, $topUnit, $rightUnit],
            [$centerUnit, $topUnit, $topRightUnit],
            [$centerUnit, $bottomUnit, $leftUnit],
            [$centerUnit, $bottomUnit, $rightUnit],
            [$centerUnit, $leftUnit, $topLeftUnit],
            [$centerUnit, $leftUnit, $bottomLeftUnit],
        ];

        foreach ($formations as $formation) {
            if ($formation[0] && $formation[1] && $formation[2]) {
                $this->serverLog("Valid formation found", $formation);

                foreach ($formation as $unit) {
                    self::setUnitFormationAndUpdate($unit, 1);
                }

                return $formation;
            }
        }

        self::setUnitFormationAndUpdate($centerUnit, 0);

        $this->serverLog("No valid formation found", "");
        return null;
    }

    private function getNearbyBattleships($centerUnit)
    {
        $sql = "SELECT unit_id, x, y, type, is_fortified, in_formation FROM units 
        WHERE player_id = " . self::escapeString($centerUnit['player_id']) . "
        AND type = 'battleship'";
        $result = self::getObjectListFromDB($sql);
        // $this->serverLog("getNearbyBattleships SQL", $sql);
        // $this->serverLog("getNearbyBattleships result", $result);
        return $result;
    }

    private function findBattleshipAtPosition($battleships, $x, $y)
    {
        foreach ($battleships as $battleship) {
            if (intval($battleship['x']) == $x && intval($battleship['y']) == $y) {
                // $this->serverLog("Battleship found at position ($x, $y)", $battleship);
                return $battleship;
            }
        }
        // $this->serverLog("No battleship found at position ($x, $y)", "");
        return null;
    }

    // New helper function to check if a Battleship is on a Shore space
    private function isBattleshipOnShore($unit)
    {
        // Check if the unit is a Battleship
        if ($unit['type'] !== 'battleship') {
            return false;
        }

        // Get the space type for the unit's position
        $spaceType = $this->getSpaceType($unit['x'], $unit['y']);

        // Check if the space is a Shore space
        return $spaceType === 'shore';
    }

    // Helper function to get the space type (you may need to implement this based on your game board structure)
    private function getSpaceType($x, $y)
    {
        $this->serverLog("Entered getSpaceType method", "");
        // $this->serverLog("GameVariant =", self::getGameStateValue('gameVariant'));
        // $this->serverLog("x =", $x);
        // $this->serverLog("y =", $y);

        if (self::getGameStateValue('gameVariant') == 4 || self::getGameStateValue('gameVariant') == 5) {
            $shoreSpaces = [
                [2, 0],
                [2, 1],
                [2, 2],
                [2, 3],
                [2, 4],
            ];
        } else {
            $shoreSpaces = [
                [0, 3],
                [1, 2],
                [2, 1],
                [3, 0],
            ];
        }

        foreach ($shoreSpaces as $space) {
            if ($space[0] == $x && $space[1] == $y) {
                return 'shore';
            }
        }

        // Default to 'water' if not found in shore spaces
        return 'water';
    }

    private function checkInfantryFormation($centerUnit, $adjacentUnits)
    {
        $this->serverLog("Checking infantry formation for unit", $centerUnit);
        // $this->serverLog("Adjacent units", $adjacentUnits);

        // Find adjacent friendly infantry units
        $adjacentInfantry = array_filter($adjacentUnits, function ($unit) use ($centerUnit) {
            return $unit['type'] == 'infantry' && $unit['player_id'] == $centerUnit['player_id'];
        });

        // $this->serverLog("Adjacent friendly infantry units", $adjacentInfantry);

        // If there's no adjacent friendly infantry, return null
        if (empty($adjacentInfantry)) {
            // $this->serverLog("No adjacent friendly infantry found", null);
            self::setUnitFormationAndUpdate($centerUnit, 0);
            return null;
        }

        foreach ($adjacentInfantry as $partnerInfantry) {
            // $this->serverLog("Checking potential formation with partner", $partnerInfantry);

            // Check if the partner infantry is actually adjacent
            if (!$this->areUnitsAdjacent($centerUnit, $partnerInfantry)) {
                // $this->serverLog("Partner infantry is not adjacent", null);
                continue;
            }

            $potentialFormation = [$centerUnit, $partnerInfantry];

            // Get all units adjacent to both infantry units
            $allAdjacentUnits = array_merge(
                $this->getAdjacentUnits($centerUnit),
                $this->getAdjacentUnits($partnerInfantry)
            );

            // $this->serverLog("All adjacent units to the formation", $allAdjacentUnits);

            // Check if there's any fortified friendly unit adjacent to either infantry
            foreach ($allAdjacentUnits as $adjacentUnit) {
                if (
                    $adjacentUnit['player_id'] == $centerUnit['player_id'] &&
                    $adjacentUnit['is_fortified'] == '1' &&
                    $adjacentUnit['unit_id'] != $centerUnit['unit_id'] &&
                    $adjacentUnit['unit_id'] != $partnerInfantry['unit_id'] &&
                    ($this->areUnitsAdjacent($centerUnit, $adjacentUnit) || $this->areUnitsAdjacent($partnerInfantry, $adjacentUnit))
                ) {
                    // $this->serverLog("Valid formation found (adjacent fortified unit)", $potentialFormation);
                    self::setUnitFormationAndUpdate($centerUnit, 1);
                    return $potentialFormation;
                }
            }
        }

        self::setUnitFormationAndUpdate($centerUnit, 0);
        // $this->serverLog("No valid formation found", null);
        return null;
    }

    private function checkTankFormation($centerUnit, $adjacentUnits)
    {
        // First, let's find adjacent tanks
        $adjacentTanks = array_filter($adjacentUnits, function ($unit) {
            return $unit['type'] == 'tank';
        });

        // If there are no adjacent tanks, return null
        if (count($adjacentTanks) == 0) {
            self::setUnitFormationAndUpdate($centerUnit, 0);
            return null;
        }

        foreach ($adjacentTanks as $adjacentTank) {
            $dx = $adjacentTank['x'] - $centerUnit['x'];
            $dy = $adjacentTank['y'] - $centerUnit['y'];

            // Check both directions for the third unit
            $directions = [
                ['x' => $centerUnit['x'] - $dx, 'y' => $centerUnit['y'] - $dy],  // Opposite to adjacentTank
                ['x' => $adjacentTank['x'] + $dx, 'y' => $adjacentTank['y'] + $dy]  // Beyond adjacentTank
            ];

            foreach ($directions as $direction) {
                $thirdUnit = $this->findUnitAtPosition($adjacentUnits, $direction['x'], $direction['y']);
                if ($thirdUnit !== null) {
                    // Found a valid formation
                    $formation = [$centerUnit, $adjacentTank, $thirdUnit];

                    self::setUnitFormationAndUpdate($centerUnit, 1);
                    return $formation;
                }
            }

            // Check for formations where the center unit is at the end of the line
            $extendedDirections = [
                ['x' => $adjacentTank['x'] + $dx, 'y' => $adjacentTank['y'] + $dy],
                ['x' => $adjacentTank['x'] + 2 * $dx, 'y' => $adjacentTank['y'] + 2 * $dy]
            ];

            foreach ($extendedDirections as $direction) {
                $thirdUnit = $this->findFriendlyUnitAtPosition($centerUnit['player_id'], $direction['x'], $direction['y']);
                if ($thirdUnit !== null) {
                    // Found a valid tank formation
                    self::serverLog("Found a valid tank formation", "");
                    $formation = [$centerUnit, $adjacentTank, $thirdUnit];

                    self::setUnitFormationAndUpdate($centerUnit, 1);
                    return $formation;
                }
            }
        }

        self::setUnitFormationAndUpdate($centerUnit, 0);

        // No valid formation found
        return null;
    }

    // Helper function to find a friendly unit at a specific position
    private function findFriendlyUnitAtPosition($playerId, $x, $y)
    {
        $sql = "SELECT * FROM units WHERE player_id = $playerId AND x = $x AND y = $y";
        return self::getObjectFromDB($sql);
    }

    // Helper function to find a unit at a specific position
    private function findUnitAtPosition($units, $x, $y)
    {
        foreach ($units as $unit) {
            if ($unit['x'] == $x && $unit['y'] == $y) {
                return $unit;
            }
        }
        return null;
    }

    private function getAdjacentUnits($unit)
    {
        self::serverLog("Inside getAdjacentUnits", $unit);

        $sql = "SELECT id, type, player_id, x, y, unit_id, is_fortified,is_occupied, 
                is_stacked, in_formation FROM units 
            WHERE (
                (ABS(x - " . $unit['x'] . ") <= 1 AND ABS(y - " . $unit['y'] . ") <= 1)
                AND NOT (x = " . $unit['x'] . " AND y = " . $unit['y'] . ")
            )";
        $result = self::getObjectListFromDB($sql);
        // $this->serverLog("getAdjacentUnits SQL", $sql);
        // $this->serverLog("getAdjacentUnits result", $result);
        return $result;
    }

    // Get orthogonally adjacent units
    private function getOrthogonallyAdjacentUnits($unit)
    {
        $sql = "SELECT id, type, player_id, x, y, unit_id, is_fortified,is_occupied, 
                is_stacked, in_formation FROM units 
            WHERE ((ABS(x - " . $unit['x'] . ") + ABS(y - " . $unit['y'] . ") = 1))";
        $result = self::getObjectListFromDB($sql);
        // $this->serverLog("get orthogonally AdjacentUnits SQL", $sql);
        // $this->serverLog("get orthogonally AdjacentUnits result", $result);
        return $result;
    }

    public function skipEnlist()
    {
        $this->serverLog("Entered skipEnlist method", "");
        // Check if it's a valid action
        if (!$this->checkAction('skipEnlist')) {
            throw new BgaUserException(self::_("It is not your turn or this action is not allowed at this time"));
        }

        $player_id = self::getActivePlayerId();

        // Reset the infantry enlist count
        $this->setInfantryEnlistCount($player_id, 0);

        // Decrease the action counter
        $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;

        $this->serverLog("actionsRemaining", $actionsRemaining);

        $this->setGameStateValue('actionsRemaining', $actionsRemaining);

        // Notify all players about the skipped enlistment
        self::notifyAllPlayers(
            'enlistSkipped',
            clienttranslate('${player_name} skipped the second infantry enlistment'),
            [
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName()
            ]
        );

        // Check if the turn should end
        if ($actionsRemaining == 0) {
            self::setGameStateValue('isFirstRound', 0);
            self::setGameStateValue('isVeryFirstTurn', 0);
            $this->serverLog("Turn will end", "");
            $this->gamestate->nextState('nextPlayer');
        } else {
            // Update the UI about remaining actions
            self::notifyAllPlayers('actionsRemaining', '', [
                'actionsRemaining' => $actionsRemaining
            ]);
            $this->gamestate->nextState('stayInState');
        }
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

        if ($attackingUnit['type'] == 'artillery') {
            // Check if the defending unit is in the same row or column
            if ($attackingUnit['x'] != $defendingUnit['x'] && $attackingUnit['y'] != $defendingUnit['y']) {
                throw new BgaUserException(self::_("Artillery can only attack units in the same row or column"));
            }
        } else {
            // Check if units are orthogonally adjacent; not applicable to choppers
            if ($attackingUnit['type'] != 'chopper' && !$this->areUnitsAdjacent($attackingUnit, $defendingUnit)) {
                throw new BgaUserException(self::_("The units must be orthogonally adjacent"));
            }
        }

        // Check if the attacking unit is in formation or fortified
        if (
            !$this->isUnitInFormation($attackingUnit)
            && !$attackingUnit['is_fortified']
            && $attackingUnit['type'] != 'artillery'
        ) {
            throw new BgaUserException(self::_("The attacking unit must be in formation"));
        }

        // If defending unit is fortified, attacking unit must be fortified too
        if ($defendingUnit['is_fortified']) {
            if (!$attackingUnit['is_fortified']) {
                throw new BgaUserException(self::_("The attacking unit must be fortified"));
            }
        }

        if ($attackingUnit['type'] == 'chopper') {
            // Check if the chopper is attacking the unit directly beneath it
            if ($attackingUnit['x'] != $defendingUnit['x'] || $attackingUnit['y'] != $defendingUnit['y']) {
                throw new BgaUserException(self::_("Chopper can only attack the unit directly beneath it"));
            }

            // Check if the defending unit is also a chopper
            if ($defendingUnit['type'] == 'chopper') {
                throw new BgaUserException(self::_("Choppers cannot attack other choppers"));
            }
        } elseif ($defendingUnit['type'] == 'chopper') {
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

        // If infantry enlist was previous move, cancel the free infantry enlist
        // And decrease the actionremaining counter by 1
        $infantryEnlistCount = $this->getInfantryEnlistCount($player_id);
        if ($infantryEnlistCount == 1) {
            $this->setInfantryEnlistCount($player_id, 0);
            $actionsRemaining = $this->getGameStateValue('actionsRemaining') - 1;
            $this->setGameStateValue('actionsRemaining', $actionsRemaining);
        }

        // Decrease the action counter
        $this->decreaseActionCounter();

        // Update state of units
        $units = self::getAllUnits();
        self::serverLog("all units during fortify", $units);

        foreach ($units as $unit) {
            self::isUnitInFormation($unit);
        }

        // Check for game end conditions
        if ($this->checkGameEnd()) {
            return; // The game has ended, no need to proceed
        }
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
                $battleshipFormation = $this->checkBattleshipFormation($unit, $adjacentUnits);
                self::serverLog("battleship formation", $battleshipFormation);
                return $battleshipFormation !== null;
            case 'infantry':
                $adjacentUnits = $this->getOrthogonallyAdjacentUnits($unit);
                $infantryFormation = $this->checkInfantryFormation($unit, $adjacentUnits);
                self::serverLog("infantry formation", $infantryFormation);

                return $infantryFormation !== null;
            case 'tank':
                $tankFormation = $this->checkTankFormation($unit, $adjacentUnits);
                self::serverLog("tank formation", $tankFormation);
                return $tankFormation !== null;
            case 'chopper':
                    $chopperFormation = $this->checkChopperFormation($unit);
                    self::serverLog("chopper formation", $chopperFormation);
                    return $chopperFormation !== null;
            default:
                return false; // Unknown unit type
        }
    }

    private function areUnitsAdjacent($unit1, $unit2)
    {
        $this->serverLog("Entered areUnitsAdjacent method", "");
        $this->serverLog("unit1 x", $unit1['x']);
        $this->serverLog("unit1 y", $unit1['y']);
        $this->serverLog("unit2 x", $unit2['x']);
        $this->serverLog("unit2 y", $unit2['y']);
        $dx = abs($unit1['x'] - $unit2['x']);
        $dy = abs($unit1['y'] - $unit2['y']);
        return ($dx + $dy == 1);
    }

    private function moveUnitToReinforcementTrack($unit)
    {
        // First, shift all existing units down one position
        $sql = "UPDATE reinforcement_track SET position = position + 1 WHERE position < 5";
        self::DbQuery($sql);

        // Check if the unit is already in the reinforcement track
        $sql = "SELECT * FROM reinforcement_track WHERE unit_id = '" . $unit['unit_id'] . "'";
        $existingUnit = self::getObjectFromDB($sql);

        if ($existingUnit) {
            // If the unit is already in the track, update its position to 1
            $sql = "UPDATE reinforcement_track 
                    SET position = 1, 
                    is_fortified = " . ($unit['is_fortified'] ? '1' : '0') . ",
                    type = '" . $unit['type'] . "'
                WHERE unit_id = '" . $unit['unit_id'] . "'";
            self::DbQuery($sql);
        } else {
            // If the unit is not in the track, insert it at position 1
            $sql = "INSERT INTO reinforcement_track (unit_id, position, is_fortified, player_id, type) 
                    VALUES ('" . $unit['unit_id'] . "', 1, " . ($unit['is_fortified'] ? '1' : '0') . ", " . $unit['player_id'] . ", '" . $unit['type'] . "')";
            self::DbQuery($sql);
        }

        // Check if any unit has moved to position 5 (since we shifted all down by 1)
        $sql = "SELECT rt.*, u.type, rt.player_id 
            FROM reinforcement_track rt
            LEFT JOIN units u ON rt.unit_id = u.unit_id
            WHERE rt.position > 4";
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
        // Fetch complete unit information from the reinforcement_track table
        $sql = "SELECT rt.*, rt.type, rt.player_id 
                FROM reinforcement_track rt
                LEFT JOIN units u ON rt.unit_id = u.unit_id
                WHERE rt.unit_id = '{$unit['unit_id']}'";
        $completeUnit = self::getObjectFromDB($sql);

        // $parts = explode("_", $completeUnit['unit_id']);
        // $type = $parts[0];

        if (!$completeUnit) {
            throw new BgaSystemException("Unit not found in reinforcement track: " . $unit['unit_id']);
        }

        // Add the unit back to the units table
        // This is important for refresh page scenario
        $sql = "INSERT INTO units (type, player_id, x, y, unit_id, is_fortified) 
                VALUES ('{$completeUnit['type']}', {$completeUnit['player_id']}, -1, -1, 
                '{$completeUnit['unit_id']}', '{$completeUnit['is_fortified']}')";
        self::DbQuery($sql);

        // Remove the unit from the reinforcement track
        $sql = "DELETE FROM reinforcement_track WHERE unit_id = '{$completeUnit['unit_id']}'";
        self::DbQuery($sql);

        // Notify clients about the unit returning to supply
        self::notifyAllPlayers('unitReturnedToSupply', clienttranslate('A ${unit_type} has returned to ${player_name}\'s supply'), [
            'unit_type' => $completeUnit['type'],
            'player_name' => self::getPlayerNameById($completeUnit['player_id']),
            'unit_id' => $completeUnit['unit_id'],
            'is_fortified' => $completeUnit['is_fortified'],
            'player_id' => $completeUnit['player_id']
        ]);
    }

    private function getReinforcementTrackState()
    {
        $sql = "SELECT * FROM reinforcement_track";

        // $this->serverLog("sql", $sql);
        $reinforcementTrack = self::getCollectionFromDb($sql);
        // $this->serverLog("reinforcementTrack", $reinforcementTrack);

        return $reinforcementTrack;
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

    // Game end conditions

    private function checkGameEnd()
    {
        $this->serverLog("entered checkGameEnd method", "");

        $players = self::loadPlayersBasicInfos();
        $activePlayerId = self::getActivePlayerId();
        $endVolley = false;

        // Check for 2x2 fortification
        if (
            $this->check2x2Fortification($activePlayerId)
            || $this->check2x2Fortification($this->getPlayerAfter($activePlayerId))
        ) {
            $endVolley = true;
            self::notifyAllPlayers('debug', '2x2 fortification achieved', array());
        }

        // Check if all 12 units are fortified
        if (!$endVolley && ($this->checkAllUnitsFortified($activePlayerId)
            || $this->checkAllUnitsFortified($this->getPlayerAfter($activePlayerId)))) {
            $endVolley = true;
            self::notifyAllPlayers('debug', 'All units fortified', array());
        }

        $this->calculateAndUpdatePoints($activePlayerId);
        $this->calculateAndUpdatePoints($this->getPlayerAfter($activePlayerId));

        if ($endVolley) {
            $this->incrementVolleyCount();
            $this->incrementPlayerVolleyWins($activePlayerId);

            $volleyCount = $this->getVolleyCount();
            $playerWins = $this->getPlayerVolleyWins($activePlayerId);

            // $this->serverLog("volleyCount", $volleyCount);
            // $this->serverLog("playerVolleyWins", $playerWins);

            // Check if a player has won 2 volleys
            if ($playerWins == 2) {
                // End the game
                $this->gamestate->nextState('endGame');
                return true;
            }

            // Start a new volley if the game hasn't ended
            if ($volleyCount < 3) {
                self::notifyAllPlayers('playerWin', '', array(
                    'volleyCount' => $this->volleyCount,
                    'players' => $players,
                    'volleyWinner' => $activePlayerId
                ));

                $this->gamestate->nextState('newVolley');
                return true;
            } else {
                // End the game if 3 volleys have been played
                $this->gamestate->nextState('endGame');
                return true;
            }
        }

        return false;
    }

    // Helper methods for database operations
    private function incrementVolleyCount()
    {
        $sql = "INSERT INTO game_progress (volley_count) VALUES (1) 
            ON DUPLICATE KEY UPDATE volley_count = volley_count + 1";
        self::DbQuery($sql);
    }

    private function getVolleyCount()
    {
        return self::getUniqueValueFromDB("SELECT DISTINCT volley_count FROM game_progress");
    }

    private function incrementPlayerVolleyWins($playerId)
    {
        $sql = "INSERT INTO player_volley_wins (player_id, wins) VALUES ($playerId, 1)
            ON DUPLICATE KEY UPDATE wins = wins + 1";
        self::DbQuery($sql);
    }

    private function getPlayerVolleyWins($playerId)
    {
        return self::getUniqueValueFromDB("SELECT wins FROM player_volley_wins WHERE player_id = $playerId");
    }

    private function check2x2Fortification($playerId)
    {
        $board = $this->getBoard();
        for ($x = 0; $x < 4; $x++) {
            for ($y = 0; $y < 4; $y++) {
                if ($this->checkFortifiedSquare($board, $x, $y, $playerId)) {
                    return true;
                }
            }
        }
        return false;
    }

    // Function for checking 2x2 fortification
    // Do not consider disabld units (occupied by chopper on top) for fortification. 
    private function checkFortifiedSquare($board, $x, $y, $playerId)
    {
        self::serverLog("inside checkFortifiedSquare method", "");
        for ($i = $x; $i < $x + 2; $i++) {
            for ($j = $y; $j < $y + 2; $j++) {
                // Check if the square exists and belongs to the player
                if (!isset($board[$i][$j]) || $board[$i][$j]['player_id'] != $playerId) {
                    return false;
                }

                $unit = $board[$i][$j];
                self::serverLog("unit for checking win condition", $unit);

                // Check if the unit is fortified
                if (!$unit['is_fortified']) {
                    return false;
                }

                // If the unit is occupied, it should only be considered if it's a fortified chopper
                if ($unit['is_occupied']) {
                    $topUnit = $this->getTopUnit($i, $j);
                    if ($topUnit['type'] !== 'chopper' || !$topUnit['is_fortified']) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    // Helper function to get the top unit in a stack
    private function getTopUnit($x, $y)
    {
        // self::serverLog("inside getTopUnit method", "");
        $sql = "SELECT * FROM units WHERE x = $x AND y = $y ORDER BY is_stacked DESC LIMIT 1";
        return self::getObjectFromDB($sql);
    }

    private function checkAllUnitsFortified($playerId)
    {
        // $this->serverLog("Entered checkAllUnitsFortified method", "");

        if ($this->getGameVariant() == 1 || $this->getGameVariant() == 2 || $this->getGameVariant() == 4) {
            $totalUnits = 12;
        } else {
            $totalUnits = 14;
        }

        // $this->serverLog("totalUnits", $totalUnits);

        $sql = "SELECT COUNT(*) as count FROM units WHERE player_id = $playerId AND is_fortified = 1";
        $fortifiedUnits = self::getUniqueValueFromDB($sql);

        // $this->serverLog("fortifiedUnits", $fortifiedUnits);

        $sql = "SELECT COUNT(*) as count FROM reinforcement_track WHERE player_id = $playerId AND is_fortified = 1";
        $reinforcementfortifiedUnits = self::getUniqueValueFromDB($sql);

        // $this->serverLog("reinforcementfortifiedUnits", $reinforcementfortifiedUnits);

        return $totalUnits == $fortifiedUnits + $reinforcementfortifiedUnits;
    }

    private function getBoard()
    {
        self::serverLog("Inside getBoard method", "");

        $result = self::getAllUnits();

        $board = array();
        foreach ($result as $unit) {
            $board[$unit['x']][$unit['y']] = $unit;
        }

        return $board;
    }

    function stNewVolley()
    {
        // Reset the game for a new volley
        $this->serverLog("Resetting the game for new volley", "");
        $this->setupNewVolley();
        //$this->gamestate->nextState('');
    }

    function stFinalScore()
    {
        // Calculate final scores
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $playerId => $player) {
            $score = isset($this->playerVolleyWins[$playerId]) ? $this->playerVolleyWins[$playerId] : 0;
            $sql = "UPDATE player SET player_score = $score WHERE player_id = $playerId";
            self::DbQuery($sql);
        }

        $this->gamestate->nextState('');
    }

    private function setupNewVolley()
    {
        // 1. Swap player colors
        $players = self::loadPlayersBasicInfos();
        // $colors = array('red', 'green');
        // $i = 0;

        // Get current colors
        $currentColors = array();
        foreach ($players as $playerId => $player) {
            $currentColors[$playerId] = $player['player_color'];
        }

        // Swap colors
        foreach ($players as $playerId => $player) {
            $currentColor = $currentColors[$playerId];
            $newColor = ($currentColor == 'red') ? 'green' : 'red';

            $sql = "UPDATE player SET player_color='$newColor' WHERE player_id=$playerId";
            self::DbQuery($sql);
        }

        // foreach ($players as $playerId => $player) {
        //     $newColor = $colors[($i + 1) % 2];
        //     $sql = "UPDATE player SET player_color='$newColor' WHERE player_id=$playerId";
        //     self::DbQuery($sql);
        //     $i++;
        // }
        self::reloadPlayersBasicInfos();
        $players = self::loadPlayersBasicInfos();


        // 2. Clear the board
        self::DbQuery("DELETE FROM units");
        // $this->serverLog("deleted all units", "");
        // 3. Clear the reinforcement track
        self::DbQuery("DELETE FROM reinforcement_track");
        // $this->serverLog("deleted all reinforcement_track", "");

        // 5. Reset game state values
        self::setGameStateValue('isFirstRound', 1);
        self::setGameStateValue('isVeryFirstTurn', 1);
        self::setGameStateValue('actionsRemaining', 1);

        // 6. Notify players about the new volley
        self::notifyAllPlayers('newVolley', clienttranslate('A new volley begins! Players have switched colors.'), array(
            'volleyCount' => $this->volleyCount,
            'players' => $players
        ));

        // 7. Update player panels
        foreach ($players as $playerId => $player) {
            self::notifyAllPlayers('updatePlayerPanel', '', array(
                'player_id' => $playerId,
                'player_color' => $player['player_color']
            ));
        }

        // 8. Reset the active player to the appropriate first player for this volley
        $volleyCount = $this->getVolleyCount();
        $playerIds = array_keys($players);

        // Determine which player should start this volley
        if ($volleyCount % 2 == 0) {
            // Even-numbered volleys (including volley 2) start with the original first player
            $newFirstPlayerId = $playerIds[0];
        } else {
            // Odd-numbered volleys (including volley 3) start with the original second player
            $newFirstPlayerId = $playerIds[1];
        }

        // Set the new active player
        $this->gamestate->changeActivePlayer($newFirstPlayerId);
        self::giveExtraTime($newFirstPlayerId);

        // Log the new first player for debugging
        $this->serverLog("New first player for volley $volleyCount", $newFirstPlayerId);

        // 9. Prepare for the first turn of the new volley
        $this->gamestate->nextState('');
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
