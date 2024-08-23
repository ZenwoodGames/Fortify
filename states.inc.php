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
 * states.inc.php
 *
 * Fortify game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

require_once("modules/php/constants.inc.php");

$machinestates = array(

    // The initial state. Please do not modify.
    ST_GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => ST_PLAYER_F_TURN)
    ),

    // Player must enlist in first turn
    ST_PLAYER_F_TURN => array(
        "name" => "playerFirstTurn",
        "description" => clienttranslate('${actplayer} must take an action'),
        "descriptionmyturn" => clienttranslate('${you} must take an action'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "updateGameProgression" => "getGameProgression",
        "possibleactions" => array("enlist", "move", "fortify", "attack", "pass", "skipEnlist"),
        "transitions" => array(
            "nextPlayer" => ST_NEXT_PLAYER,
            "playerFirstTurn" => ST_PLAYER_F_TURN,
            "stayInState" => ST_PLAYER_TURN,
            "endTurn" => ST_NEXT_PLAYER
        )
    ),

    ST_PLAYER_F_ENLIST => array(
        "name" => "playerFirstEnlist",
        "description" => clienttranslate('${actplayer} must enlist a unit'),
        "descriptionmyturn" => clienttranslate('${you} must enlist a unit'),
        "type" => "activeplayer",
        "possibleactions" => array("enlist", "skipEnlist"),
        "updateGameProgression" => "getGameProgression",
        "transitions" => array(
            "stayInState" => ST_PLAYER_F_ENLIST,
            "nextPlayer" => ST_NEXT_PLAYER,
            "playerFirstTurn" => ST_PLAYER_F_TURN
        )
    ),

    // Player's turn
    ST_PLAYER_TURN => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must take up to two Actions'),
        "descriptionmyturn" => clienttranslate('${you} must take up to two Actions'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "updateGameProgression" => "getGameProgression",
        "possibleactions" => array("enlist", "move", "fortify", "attack", "pass", "endTurn", "skipEnlist"),
        "transitions" => array(
            "nextPlayer" => ST_NEXT_PLAYER,
            "endTurn" => ST_NEXT_PLAYER,
            "stayInState" => ST_PLAYER_TURN,
            "newVolley" => ST_NEW_VOLLEY,
            "endGame" => ST_END_GAME 
        )
    ),

    ST_NEXT_PLAYER => array(
        'name' => 'nextPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextPlayer',
        "updateGameProgression" => "getGameProgression",
        "transitions" => array(
            "playerFirstTurn" => ST_PLAYER_F_TURN,
            "playerFirstEnlist" => ST_PLAYER_F_ENLIST,
            "playerTurn" => ST_PLAYER_TURN,
            "endGame" => ST_END_GAME,
            "newVolley" => ST_NEW_VOLLEY
        )
    ),

    ST_NEW_VOLLEY => array(
        "name" => "newVolley",
        "description" => clienttranslate("Starting a new volley"),
        "type" => "game",
        "action" => "stNewVolley",
        "updateGameProgression" => "getGameProgression",
        "transitions" => array(
            "" => ST_PLAYER_F_TURN
        )
    ),

    ST_END_GAME => array(
        "name" => "endGame",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stEndGame",
        "args" => "argEndGame",
        "updateGameProgression" => "getGameProgression",
        "transitions" => array("" => ST_FINAL_SCORE)
    ),

    ST_FINAL_SCORE => array(
        "name" => "finalScore",
        "description" => clienttranslate("Game end"),
        "type" => "game",
        "updateGameProgression" => "getGameProgression",
        "action" => "stFinalScore",
        "transitions" => array("" => ST_END_GAME)
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);

$machinestates[ST_PLAYER_TURN]['transitions']['endGame'] = ST_END_GAME;
$machinestates[ST_NEXT_PLAYER]['transitions']['endGame'] = ST_END_GAME;
$machinestates[ST_NEXT_PLAYER]['transitions']['newVolley'] = ST_NEW_VOLLEY;
