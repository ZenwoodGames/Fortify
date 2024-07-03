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
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
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

        // $units = array(
        //     array('type' => 'infantry', 'player_id' => 1, 'x' => 0, 'y' => 0),
        //     array('type' => 'tank', 'player_id' => 1, 'x' => 1, 'y' => 0),
        //     array('type' => 'battleship', 'player_id' => 1, 'x' => 2, 'y' => 0),
        //     array('type' => 'infantry', 'player_id' => 2, 'x' => 0, 'y' => 4),
        //     array('type' => 'tank', 'player_id' => 2, 'x' => 1, 'y' => 4),
        //     array('type' => 'battleship', 'player_id' => 2, 'x' => 2, 'y' => 4),
        // );

        // foreach ($units as $unit) {
        //     $this->DbQuery("INSERT INTO units (type, player_id, x, y) VALUES ('" . $unit['type'] . "', " . $unit['player_id'] . ", " . $unit['x'] . ", " . $unit['y'] . ")");
        // }

        $this->activeNextPlayer();

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
        $sql = "SELECT id, type, player_id, x, y, unit_id FROM units";
        $result['units'] = self::getObjectListFromDB($sql);

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
        // Active next player
        $player_id = self::activeNextPlayer();
        self::giveExtraTime($player_id);

        // Log the active player
        self::info("Active player set to: " . $player_id);

        // Determine if this is the first turn
        $sql = "SELECT COUNT(*) FROM units";
        $unitCount = self::getUniqueValueFromDB($sql);

        if ($unitCount == 0) {
            // If no units have been placed, it's still the first turn
            $this->gamestate->nextState("firstTurn");
        } else {
            // Otherwise, go to the regular player turn
            $this->gamestate->nextState("");
        }
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */



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

    function playToken($token_id)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        //$this->checkAction( 'playCard' ); 

        $player_id = $this->getActivePlayerId();

        // Add your game logic to play a card there 


        // Notify all players about the card played
        // $this->notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
        //     'player_id' => $player_id,
        //     'player_name' => $this->getActivePlayerName(),
        //     'card_name' => $card_name,
        //     'card_id' => $card_id
        // ) );

    }

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
        $validUnitTypes = ['infantry', 'tank', 'battleship'];
        if (!in_array($unitType, $validUnitTypes)) {
            throw new BgaUserException(self::_("Invalid unit type"));
        }

        // Validate the coordinates
        if ($x < 0 || $x > 3 || $y < 0 || $y > 4) {
            throw new BgaUserException(self::_("Invalid coordinates"));
        }

        // Check if the space is empty
        $sql = "SELECT COUNT(*) FROM units WHERE x = $x AND y = $y";
        $count = self::getUniqueValueFromDB($sql);
        if ($count > 0) {
            throw new BgaUserException(self::_("This space is already occupied"));
        }

        // Check if the player has available units of this type
        $sql = "SELECT COUNT(*) FROM units WHERE player_id = $player_id AND type = '$unitType'";
        $count = self::getUniqueValueFromDB($sql);
        if ($count >= 4) {
            throw new BgaUserException(self::_("You have no more units of this type available"));
        }

        // Add the unit to the board
        $sql = "INSERT INTO units (type, player_id, x, y, unit_id) VALUES ('$unitType', $player_id, $x, $y, '$unitId')";
        self::DbQuery($sql);

        // Notify all players about the new unit
        self::notifyAllPlayers('unitEnlisted', clienttranslate('${player_name} enlists a ${unit_type} at (${x},${y})'), [
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'unit_type' => $unitType,
            'x' => $x,
            'y' => $y,
            'unitId' => $unitId
        ]);

        // Move to the next player
        // $this->gamestate->nextState('next');
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
