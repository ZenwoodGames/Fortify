{OVERALL_GAME_HEADER}
<div id="game_play_area">
    <!-- Player decks and reinforcement track -->
    <div id="player_area">
        <div id="player_deck_top" class="player_deck">
            <div id="infantry_deck" class="unit_deck infantry_deck"></div>
            <div id="infantry_deck_fortified" class="unit_deck infantry_deck"></div>
            <div id="battleship_deck" class="unit_deck battleship_deck"></div>
            <div id="battleship_deck_fortified" class="unit_deck battleship_deck"></div>
            <div id="tank_deck" class="unit_deck tank_deck"></div>
            <div id="tank_deck_fortified" class="unit_deck tank_deck"></div>
            <div id="chopper_deck" class="unit_deck chopper_deck"></div>
            <div id="artillery_deck_fortified" class="unit_deck artillery_deck"></div>
            <div id="help-button">?</div>
        </div>
        <div id="action-counter"></div>
        <div id="board_container">
            <div class="action-menu">
                <!--<button class="action-button" id="btnMove">MOVE</button>-->
                <button class="action-button" id="btnFortify">FORTIFY</button>
                <button class="action-button" id="btnAttack" style="display: none;">ATTACK BOTTOM UNIT</button>
                <button class="action-button" id="btnSkipEnlist" style="display: none;">SKIP ENLIST</button>
            </div>
            <div id="pointTextContainer">
                <div id="points_display" class="whiteblock">
                        <h3 id="points_title"></h3>
                        <div id="points_container">
                            <!-- Points will be inserted here by JavaScript -->
                        </div>
                </div>
            </div>
            <div id="board">
                <div id="slots_container">
                    <!-- Board slots for placing units -->
                </div>
                <div id="reinforcement_track">
                    <!-- Reinforcement track slots -->
                    <div id="reinforcement_slot_1" class="reinforcement_slot"></div>
                    <div id="reinforcement_slot_2" class="reinforcement_slot"></div>
                    <div id="reinforcement_slot_3" class="reinforcement_slot"></div>
                    <div id="reinforcement_slot_4" class="reinforcement_slot"></div>
                    <div id="reinforcement_slot_5" class="reinforcement_slot heart_slot"></div>
                </div>
            </div>

        </div>
        <div id="player_deck_bottom" class="player_deck">
            <div id="infantry_deck" class="unit_deck infantry_deck"></div>
            <div id="infantry_deck_fortified" class="unit_deck infantry_deck"></div>
            <div id="battleship_deck" class="unit_deck battleship_deck"></div>
            <div id="battleship_deck_fortified" class="unit_deck battleship_deck"></div>
            <div id="tank_deck" class="unit_deck tank_deck"></div>
            <div id="tank_deck_fortified" class="unit_deck tank_deck"></div>
            <div id="chopper_deck" class="unit_deck chopper_deck"></div>
            <div id="artillery_deck_fortified" class="unit_deck artillery_deck"></div>
        </div>

    </div>
</div>
<div id="reference-cards">
    <img id="reference-card-1" class="reference-card" src="" alt="Reference Card 1">
    <img id="reference-card-2" class="reference-card" src="" alt="Reference Card 2">
</div>
<div id="oversurface"></div>
{OVERALL_GAME_FOOTER}