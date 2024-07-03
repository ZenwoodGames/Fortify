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
                <button class="action-button" id="btnMove">MOVE</button>
                <button class="action-button" id="btnAttack">ATTACK</button>
                <button class="action-button" id="btnFortify">FORTIFY</button>
                <button class="action-button" id="btnEnlist">ENLIST</button>
            </div>
            <div id="board">
                <div id="slots_container">
                    <!-- Board slots for placing units -->
                    <div id="board_slot_0_0" class="board-slot water" data-x="0" data-y="0"></div>
                    <div id="board_slot_1_0" class="board-slot water" data-x="1" data-y="0"></div>
                    <div id="board_slot_2_0" class="board-slot water" data-x="2" data-y="0"></div>
                    <div id="board_slot_3_0" class="board-slot shore" data-x="3" data-y="0"></div>

                    <div id="board_slot_0_1" class="board-slot water" data-x="0" data-y="1"></div>
                    <div id="board_slot_1_1" class="board-slot water" data-x="1" data-y="1"></div>
                    <div id="board_slot_2_1" class="board-slot shore" data-x="2" data-y="1"></div>
                    <div id="board_slot_3_1" class="board-slot land" data-x="3" data-y="1"></div>

                    <div id="board_slot_0_2" class="board-slot water" data-x="0" data-y="2"></div>
                    <div id="board_slot_1_2" class="board-slot shore" data-x="1" data-y="2"></div>
                    <div id="board_slot_2_2" class="board-slot land" data-x="2" data-y="2"></div>
                    <div id="board_slot_3_2" class="board-slot land" data-x="3" data-y="2"></div>

                    <div id="board_slot_0_3" class="board-slot shore" data-x="0" data-y="3"></div>
                    <div id="board_slot_1_3" class="board-slot land" data-x="1" data-y="3"></div>
                    <div id="board_slot_2_3" class="board-slot land" data-x="2" data-y="3"></div>
                    <div id="board_slot_3_3" class="board-slot land" data-x="3" data-y="3"></div>

                    <div id="board_slot_0_4" class="board-slot land" data-x="0" data-y="4"></div>
                    <div id="board_slot_1_4" class="board-slot land" data-x="1" data-y="4"></div>
                    <div id="board_slot_2_4" class="board-slot land" data-x="2" data-y="4"></div>
                    <div id="board_slot_3_4" class="board-slot land" data-x="3" data-y="4"></div>
                </div>
                <div id="reinforcement_track">
                    <!-- Reinforcement track slots -->
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot"></div>
                    <div class="reinforcement_slot heart_slot"></div>
                </div>
                <button class="action-button" id="btnEndTurn">END TURN</button>
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