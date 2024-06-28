{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Fortify implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    fortify_fortify.tpl

    This is the HTML template of your game.

    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.

    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks

    Please REMOVE this comment before publishing your game on BGA
-->

<div id="game_play_area">
    <!-- Container for the game board -->
    <div id="board_container">
        <div id="board">
        
        </div>

        <!-- Unit placeholders -->
        <div id="units_container">
            <!-- Example unit placeholder; actual units will be dynamically added -->
            <!-- <div class="unit" id="unit_1" style="top: 100px; left: 200px;"></div> -->
        </div>
    </div>
</div>

<script type="text/javascript">
        var gamedatas = {GAME_STATE};
        console.log("Game State:", gamedatas);
        initBoard(gamedatas);
    </script>

{OVERALL_GAME_FOOTER}