<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Fortify implementation : © Nirmatt Gopal nrmtgpl@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * fortify.action.php
 *
 * Fortify main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/fortify/fortify/myAction.html", ...)
 *
 */


class action_fortify extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if ($this->isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = $this->getArg("table", AT_posint, true);
    } else {
      $this->view = "fortify_fortify";
      $this->trace("Complete reinitialization of board game");
    }
  }

  // TODO: defines your action entry points there


  /*
    
    Example:
  	
    public function myAction()
    {
        $this->setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = $this->getArg( "myArgument1", AT_posint, true );
        $arg2 = $this->getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        $this->ajaxResponse( );
    }
    
    */

  public function enlist()
  {
    self::setAjaxMode();

    // Retrieve arguments
    $unitType = self::getArg("unitType", AT_alphanum, true);
    $x = self::getArg("x", AT_int, true);
    $y = self::getArg("y", AT_int, true);
    $unitId = self::getArg("unitId", AT_alphanum, true);
    $is_fortified = self::getArg("is_fortified", AT_bool, true);

    // Call the enlist method on the game instance
    $this->game->enlist($unitType, $x, $y, $unitId, $is_fortified);

    self::ajaxResponse();
  }

  public function endTurn()
  {
    self::setAjaxMode();
    $result = $this->game->endTurn();
    self::ajaxResponse();
  }

  public function move()
  {
    self::setAjaxMode();

    // Retrieve arguments
    $unitId = self::getArg("unitId", AT_alphanum, true);
    $unitType = self::getArg("unitType", AT_alphanum, true);
    $toX = self::getArg("toX", AT_int, true);
    $toY = self::getArg("toY", AT_int, true);

    // Call the move method on the game instance
    $this->game->move($unitId, $unitType, $toX, $toY);

    self::ajaxResponse();
  }

  public function fortify()
  {
    self::setAjaxMode();

    // Retrieve arguments
    $unitId = self::getArg("unitId", AT_alphanum, true);

    // Call the fortify method on the game instance
    $this->game->fortify($unitId);

    self::ajaxResponse();
  }

  public function attack()
  {
    self::setAjaxMode();

    // Retrieve arguments
    $attackingUnitId = self::getArg("attackingUnitId", AT_alphanum, true);
    $defendingUnitId = self::getArg("defendingUnitId", AT_alphanum, true);

    // Call the attack method on the game instance
    $this->game->attack($attackingUnitId, $defendingUnitId);

    self::ajaxResponse();
  }


  public function skipEnlist()
  {
    self::setAjaxMode();

    $this->game->skipEnlist();

    self::ajaxResponse();
  }
}
