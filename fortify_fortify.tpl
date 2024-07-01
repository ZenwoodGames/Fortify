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
    <!-- Player decks and reinforcement track -->
    <div id="player_area">
        <div id="player_deck_top" class="player_deck">
            <div class="unit_deck infantry_deck"></div>
            <div class="unit_deck battleship_deck"></div>
            <div class="unit_deck tank_deck"></div>
        </div>
        <div id="board_container">
        <div class="action-menu">
                <button class="action-button">MOVE</button>
                <button class="action-button">ATTACK</button>
                <button class="action-button">FORTIFY</button>
                <button class="action-button">ENLIST</button>
            </div>
            <div id="board">
                <div id="slots_container">
                    <!-- Board slots for placing units -->
                    <div class="board_slot" data-x="0" data-y="0"></div>
                    <div class="board_slot" data-x="1" data-y="0"></div>
                    <div class="board_slot" data-x="2" data-y="0"></div>
                    <div class="board_slot" data-x="3" data-y="0"></div>

                    <div class="board_slot" data-x="0" data-y="1"></div>
                    <div class="board_slot" data-x="1" data-y="1"></div>
                    <div class="board_slot" data-x="2" data-y="1"></div>
                    <div class="board_slot" data-x="3" data-y="1"></div>

                    <div class="board_slot" data-x="0" data-y="2"></div>
                    <div class="board_slot" data-x="1" data-y="2"></div>
                    <div class="board_slot" data-x="2" data-y="2"></div>
                    <div class="board_slot" data-x="3" data-y="2"></div>

                    <div class="board_slot" data-x="0" data-y="3"></div>
                    <div class="board_slot" data-x="1" data-y="3"></div>
                    <div class="board_slot" data-x="2" data-y="3"></div>
                    <div class="board_slot" data-x="3" data-y="3"></div>

                    <div class="board_slot" data-x="0" data-y="4"></div>
                    <div class="board_slot" data-x="1" data-y="4"></div>
                    <div class="board_slot" data-x="2" data-y="4"></div>
                    <div class="board_slot" data-x="3" data-y="4"></div>
                </div>
                <div id="reinforcement_track">
                    <!-- Reinforcement track slots -->
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot heart_slot"></div>
                </div>
            </div>
            <div id="units_container">
                <!-- Unit placeholders; actual units will be dynamically added -->
            </div>
        </div>
        <div id="player_deck_bottom" class="player_deck">
            <div class="unit_deck infantry_deck"></div>
            <div class="unit_deck battleship_deck"></div>
            <div class="unit_deck tank_deck"></div>
        </div>

    </div>
</div>

<script type="text/javascript">
    // var gamedatas = JSON.parse('{GAME_STATE}');
    // console.log("Game State:", gamedatas);
    // initBoard(gamedatas);
</script>

{OVERALL_GAME_FOOTER}