/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Fortify implementation : © Nirmatt Gopal nrmtgpl@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * fortify.js
 *
 * Fortify user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
    function (dojo, declare) {
        return declare("bgagame.fortify", ebg.core.gamegui, {
            constructor: function () {
                console.log('fortify constructor');
                let selectedUnit = null;
                let playerColor = null;
                let gameState = '';
                let fortifyMode = false;
                let attackMode = false;
                let gameVariant = null;
                let selectedSpecialUnit = null;
                let infantryOnlyMode = false;
                let boardSize = '';
                let unitMarginStyle = '';
            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                debugger;
                console.log("Starting game setup");

                this.playerColor = gamedatas.players[this.player_id].color;

                // Store the game variant
                this.gameVariant = gamedatas.gameVariant;

                // Initialize infantry enlistment count
                this.infantryEnlistCount = 0;

                // Special warfare mode is selected
                if (this.gameVariant == 3) {
                    dojo.style($('player_deck_top'), 'width', '600px');
                    dojo.style($('player_deck_bottom'), 'width', '600px');
                }

                // Initialize the game board
                this.initBoard(gamedatas);

                if (gamedatas.gameVariant == 4 || gamedatas.gameVariant == 5) {
                    this.boardSize = '5x5';
                    let board = $('board');

                    board.style.width = '460px';
                    board.style.height = '460px';

                    this.unitMarginStyle = '2px 0px 0px 0px';

                    let reinforcementTrack = $('reinforcement_track');
                    reinforcementTrack.style.right = '-10px';
                    reinforcementTrack.style.top = '44px';

                    dojo.query('.reinforcement_slot').forEach(reinforcementTrack => {
                        reinforcementTrack.style.height = '64px';
                    });
                }
                else {
                    this.unitMarginStyle = '2px 0px 0px 7px ';
                    this.boardSize = '4x5';
                }
                this.generateBoardSlots(this.boardSize);

                // Initialize player decks
                this.initPlayerDecks(gamedatas);

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                // Add event listeners to the units
                dojo.query('.unit').connect('onclick', this, dojo.hitch(this, 'handleUnitClick'));

                // Add event listener for slots
                dojo.query('.board-slot').connect('onclick', this, dojo.hitch(this, 'handleSlotClick'));
                //dojo.connect($('btnMove'), 'onclick', this, 'startMoveAction');
                dojo.connect($('btnFortify'), 'onclick', this, 'onFortifyButtonClick');

                dojo.hitch(this, this.highlightValidMoves)();

                this.addEventListenserForActionButtons(gamedatas.gamestate.possibleactions);

                for (var i in gamedatas.units) {
                    var unit = gamedatas.units[i];
                    this.placeUnitOnBoard(unit.unit_id, unit.type, unit.x, unit.y, unit.player_id, unit.is_fortified);
                }

                // Initialize the reinforcement track
                if (gamedatas.reinforcementTrack)
                    this.updateReinforcementTrack(gamedatas.reinforcementTrack);

                // Add event listener for the attack button
                dojo.connect($('btnAttack'), 'onclick', this, 'onAttackButtonClick');

                $('points_title').innerHTML = gamedatas.POINTS_TITLE;
                this.setupPointsDisplay(gamedatas.players);

                console.log("Ending game setup");
            },

            placeUnitOnBoard: function (unitId, unitType, x, y, playerId, is_fortified) {
                var unitDiv = $(unitId);
                if (!unitDiv) {
                    // If the unit doesn't exist (e.g., after a refresh), create it
                    unitDiv = this.createUnitDiv(unitId, unitType, playerId);
                }

                // Move the unit to the correct position on the board
                var slot = $('board_slot_' + x + '_' + y);
                if (slot) {
                    if (unitType == 'chopper') {
                        if (this.isSlotOccupied(slot))
                            unitDiv.style = "margin: 0 -75px";
                        else
                            unitDiv.style = `margin: ${this.unitMarginStyle}`;
                    }
                    else {
                        if (this.isSlotOccupied(slot)) {
                            // Chopper already got attached
                            slot.firstChild.style = "margin: 0 -75px";
                            unitDiv.style = `margin: ${this.unitMarginStyle}`;
                            slot.insertBefore(unitDiv, slot.firstChild);
                            return;
                        }
                        unitDiv.style = `margin: ${this.unitMarginStyle}`;
                    }
                    slot.appendChild(unitDiv);
                }
                if (is_fortified == 1){
                    debugger;
                    this.updateToFortifiedUnit(unitDiv);
                    if(x == -1 && y == -1){
                        var playerDeck;
                        if(unitDiv.classList[2] == 'red'){
                            playerDeck = dojo.query('#player_deck_bottom > #infantry_deck_fortified')[0];
                        }
                        else{
                            playerDeck = dojo.query('#player_deck_top > #infantry_deck_fortified')[0];
                        }
                        dojo.place(unitDiv, playerDeck);
                    }
                }
            },

            removeUnitHighlight: function () {
                this.selectedUnit = document.querySelectorAll('.selected');
                if (this.selectedUnit && this.selectedUnit.length > 0) {
                    this.selectedUnit.forEach(sUnit => {
                        sUnit.classList.remove('selected');
                    });
                }
                this.selectedUnit = null;
            },

            removeSlotHighlight: function () {

                const highlightedSlots = document.querySelectorAll('.highlighted');
                highlightedSlots.forEach(slot => {
                    slot.classList.remove('highlighted');
                });
            },

            addEventListenserForActionButtons: function (possibleActions) {
                if (possibleActions) {
                    if (possibleActions.indexOf('enlist') == 0)
                        dojo.query('#btnEnlist').connect('onclick', this, dojo.hitch(this, 'enlist'));

                    if (possibleActions.indexOf('fortify') == 0)
                        dojo.query('#btnFortify').connect('onclick', this, dojo.hitch(this, 'fortify'));

                    if (possibleActions.indexOf('attack') == 0)
                        dojo.query('#btnAttack').connect('onclick', this, dojo.hitch(this, 'attack'));

                    if (possibleActions.indexOf('move') == 0)
                        dojo.query('#btnMove').connect('onclick', this, dojo.hitch(this, 'move'));
                }
            },

            handleUnitClick: function (event) {
                debugger;
                if (event.target.parentNode.classList.contains("reinforcement_slot")) {
                    return;
                }

                if (this.infantryOnlyMode && !event.target.classList.contains('infantry')) {
                    this.showMessage(_("You must select an infantry unit"), 'error');
                    return;
                }

                // Get the current player's color
                var currentPlayerColor = this.playerColor;

                // Get the clicked unit's color
                var unitColor = event.target.classList.contains('red') ? 'red' : 'green';

                // There are no selected units and check if the player is selecting the correct color
                if (!this.selectedUnit) {
                    if (currentPlayerColor !== unitColor) {
                        this.showMessage(_("You can only select tokens of your own color"), "error");
                        return;
                    }
                }
                else {
                    // A friendly unit is already selected and player clicks on enemy unit,
                    // then it is an attack
                    if (currentPlayerColor !== unitColor) {
                        // Check if attacking unit is in formation or check if attacking unit is fortified

                        return;
                    }
                }

                // Select the clicked Unit
                this.selectedUnit = event.target;

                this.removeSlotHighlight();

                if (this.isCurrentPlayerActive()) {

                    var clickedUnitId = event.currentTarget.id;
                    var clickedUnit = this.getUnitDetails(clickedUnitId);

                    if (clickedUnit.player_id == this.player_id) {
                        // Clicked on a friendly unit
                        //this.selectUnit(clickedUnit);
                    } else if (this.selectedUnit) {
                        // Clicked on an enemy unit while a friendly unit is selected
                        //this.tryAttack(this.selectedUnit.unit_id, clickedUnitId);
                    }

                    // If unit is not on board, then only possible move is enlist
                    if (!this.isUnitOnBoard(this.selectedUnit)) {
                        if (this.selectedUnit.classList[1] == 'chopper') {
                            this.highlightFriendlyBattleships();
                            this.selectedSpecialUnit = event.target;
                        }
                        else {
                            debugger;
                            var friendlyUnits = document.querySelectorAll(`.board-slot > .unit.${this.playerColor}`);
                            if (friendlyUnits && friendlyUnits.length > 0) {
                                // Set to store unique adjacent units
                                const adjacentUnits = new Set();

                                // Directions for orthogonal adjacency
                                const directions = [
                                    { dx: -1, dy: 0 },  // Left
                                    { dx: 1, dy: 0 },   // Right
                                    { dx: 0, dy: -1 },  // Up
                                    { dx: 0, dy: 1 }    // Down
                                ];

                                friendlyUnits.forEach(unit => {
                                    // Get the x and y coordinates of the friendly unit
                                    const boardSlot = unit.closest('.board-slot');
                                    const [x, y] = boardSlot.id.split('_').slice(-2).map(Number);

                                    // Check if slot contains chopper - the bottom unit will be disabled
                                    // Check each orthogonal direction
                                    directions.forEach(({ dx, dy }) => {
                                        const adjacentSlot = document.getElementById(`board_slot_${x + dx}_${y + dy}`);
                                        if (adjacentSlot) {
                                            const adjacentUnit = adjacentSlot.querySelector('.unit');
                                            if (!adjacentUnit) {
                                                debugger;
                                                switch (this.selectedUnit.classList[1]) {
                                                    case 'infantry':
                                                    case 'tank':
                                                        if (adjacentSlot.classList.contains('shore') || adjacentSlot.classList.contains('land')) {
                                                            adjacentSlot.classList.add('highlighted');
                                                        }
                                                        break;
                                                    case 'battleship':
                                                        if (adjacentSlot.classList.contains('shore') || adjacentSlot.classList.contains('water')) {
                                                            adjacentSlot.classList.add('highlighted');
                                                        }
                                                        break;
                                                    case 'artillery':
                                                        if (!adjacentSlot.hasChildNodes() && adjacentSlot.classList.contains('land')) {
                                                            adjacentSlot.classList.add('highlighted');
                                                        }
                                                        break;
                                                }
                                            }
                                        }
                                    });
                                });
                            }
                            else {
                                if (this.selectedUnit.classList[1] != 'artillery') {
                                    dojo.query('.shore:not(:has(.unit))').forEach(shore => {
                                        dojo.addClass(shore.id, 'highlighted')
                                    });
                                }
                            }
                        }
                    }
                    else {
                        // If unit is on the board, the unit can move/attack
                        if (event.target.classList[1] == 'chopper') {
                            if (this.selectedUnit.classList[1] == 'chopper') {
                                //this.highlightFriendlyBattleships();
                                this.selectedSpecialUnit = event.target;
                            }
                            if (this.selectedSpecialUnit && this.selectedSpecialUnit.parentNode.children.length > 1) {
                                dojo.style($('btnAttack'), 'display', 'block');
                            }
                            else {
                                dojo.style($('btnAttack'), 'display', 'none');
                            }
                            if (!this.fortifyMode) {

                                this.highlightFriendlyBattleships();
                            }
                            else {
                                // Unhide attack button if a unit is available on the bottom of the chopper

                            }
                            this.selectedSpecialUnit = event.target;
                        }
                    }

                    // Deselect previously selected token
                    this.deselectUnit();

                    dojo.addClass(event.target.id, 'selected');
                }
                else {
                    this.showMessage(_("This is not your turn"), 'info');
                }
            },

            handleSlotClick: function (event) {
                debugger;
                if (this.isCurrentPlayerActive()) {
                    let slot;
                    if (event.target.classList.contains("unit") || this.isSlotOccupied(event.target))
                        slot = event.target.parentNode;
                    else
                        slot = event.target;

                    // Fortify action
                    if (this.isSlotOccupied(slot) && this.fortifyMode
                        && this.getUnitDetails(event.target).player_id == this.player_id) {
                        if (this.getUnitDetails(event.target).is_fortified == true) {
                            this.showMessage(_("Select a non-fortified unit."), 'info');
                            this.exitFortifyMode();
                            this.deselectUnit();
                            return;
                        }
                        this.fortify(this.selectedUnit.id);
                        return;
                    }

                    /////////////////////////////////////////////////////////////////////////////////////////
                    ///////////////////////////////////// Enlist ////////////////////////////////////////////
                    /////////////////////////////////////////////////////////////////////////////////////////
                    // If unit is selected and is not on the board, then it is an enlist
                    if (this.selectedUnit && !this.isUnitOnBoard(this.selectedUnit)) {
                        if (!this.isSlotOccupied(slot) && slot.classList.contains('highlighted')) {
                            var unitType = this.selectedUnit.classList[1];
                            var x = parseInt(slot.dataset.x);
                            var y = parseInt(slot.dataset.y);
                            var unitId = this.selectedUnit.id;
                            var isFortified = this.selectedUnit.classList.contains('fortified');

                            this.finishEnlist(unitType, x, y, unitId, isFortified);
                            return;
                        }
                    }

                    // Handle chopper enlist
                    if (this.selectedSpecialUnit && (this.selectedSpecialUnit != this.selectedUnit) && this.selectedSpecialUnit.classList.contains('chopper')) {
                        if (this.selectedUnit && this.selectedUnit.classList.contains('battleship')) {
                            if (this.isUnitOnBoard(this.selectedSpecialUnit)) {
                                // If slot is not occupied, move the token
                                if (this.selectedSpecialUnit.parentNode != slot) {
                                    var unitId = this.selectedSpecialUnit.id;
                                    var toX = parseInt(event.currentTarget.dataset.x);
                                    var toY = parseInt(event.currentTarget.dataset.y);
                                    var unitType = '';

                                    this.selectedSpecialUnit ? unitType = this.selectedSpecialUnit.classList[1] : this.selectedUnit.classList[1];

                                    this.moveUnit(unitId, unitType, toX, toY);
                                }
                            }
                            else {
                                this.finishEnlist(this.selectedSpecialUnit.classList[1],
                                    slot.dataset.x, slot.dataset.y,
                                    this.selectedSpecialUnit.id, this.selectedUnit.classList.contains('fortified'));
                            }
                            return;
                        }
                    }

                    ////////////////////////////////////// End Enlist region //////////////////////////////////

                    /////////////////////////////////////////////////////////////////////////////////////////
                    /////////////////////////////////////// Attack ////////////////////////////////////////////
                    /////////////////////////////////////////////////////////////////////////////////////////

                    if (this.isSlotOccupied(slot)) {
                        if (this.selectedUnit && event.target != this.selectedUnit) {
                            if (this.selectedSpecialUnit) {
                                var unitId = this.selectedSpecialUnit.id;
                                var toX = parseInt(event.currentTarget.dataset.x);
                                var toY = parseInt(event.currentTarget.dataset.y);
                                var unitType = '';

                                this.selectedSpecialUnit ? unitType = this.selectedSpecialUnit.classList[1] : this.selectedUnit.classList[1];

                                this.moveUnit(unitId, unitType, toX, toY);
                            }
                            else {
                                if (event.target.classList.contains('highlight-target'))
                                    this.attack(this.selectedUnit.id, event.target.id);
                                else
                                    this.showMessage(_("Not a possible move!"), 'info');
                            }
                        }
                    }

                    /////////////////////////////////////////////////////////////////////////////////////////
                    /////////////////////////////////////// Move ////////////////////////////////////////////
                    /////////////////////////////////////////////////////////////////////////////////////////
                    // If unit is selected and is on the board, then it is a move
                    if (this.selectedUnit && this.isUnitOnBoard(this.selectedUnit)) {
                        // If slot is occupied and selected unit is friendly, then highlight movable slots
                        if (this.isSlotOccupied(slot)) {
                            if (this.getUnitDetails(this.selectedUnit).player_id == this.player_id) {
                                this.highlightValidMoves();
                                if (!this.selectedUnit.classList.contains('chopper')){
                                    debugger;
                                    this.highlightValidTargets(this.getUnitDetails(this.selectedUnit));
                                }
                                else {
                                    // this.showMessage(_("A Chopper can only attack an enemy unit that is directly beneath it"), 'info');
                                }
                            }
                        }
                        else {
                            // If slot is not occupied, move the token
                            if (this.selectedSpecialUnit && this.selectedSpecialUnit.parentNode != slot && slot.classList.contains('highlighted')) {
                                var unitId = this.selectedUnit.id;
                                var toX = parseInt(event.currentTarget.dataset.x);
                                var toY = parseInt(event.currentTarget.dataset.y);
                                var unitType = '';

                                this.selectedSpecialUnit ? unitType = this.selectedSpecialUnit.classList[1] : this.selectedUnit.classList[1];

                                this.moveUnit(unitId, unitType, toX, toY);
                            }
                            else {
                                if (event.target.classList.contains('highlighted')) {
                                    var unitId = this.selectedUnit.id;
                                    var toX = parseInt(event.currentTarget.dataset.x);
                                    var toY = parseInt(event.currentTarget.dataset.y);
                                    var unitType = '';

                                    unitType = this.selectedUnit.classList[1];

                                    this.moveUnit(unitId, unitType, toX, toY);
                                }
                            }
                        }
                    }

                    if (this.selectedSpecialUnit && this.selectedSpecialUnit.classList.contains('chopper')) {
                        // Selected a slot other than one occupied by chopper and does not contain another chopper
                        if (this.selectedSpecialUnit.parentNode != slot && !event.target.classList.contains('chopper')) {
                            var toX = parseInt(event.currentTarget.dataset.x);
                            var toY = parseInt(event.currentTarget.dataset.y);
                            var unitId = this.selectedSpecialUnit.id;
                            var unitType = '';

                            this.selectedSpecialUnit ? unitType = this.selectedSpecialUnit.classList[1] : this.selectedUnit.classList[1];
                            if (event.currentTarget.classList.contains('highlighted')) {
                                this.moveUnit(unitId, unitType, toX, toY);
                            }
                        } else {
                            // Highlight all valid spaces for chopper move

                        }
                        return;
                    }
                    ////////////////////////////////////// End Move region //////////////////////////////////
                }
            },

            deselectUnit: function () {
                dojo.query('.selected').removeClass('selected');
            },

            initBoard: function (gamedatas) {
                if (gamedatas.gameVariant == 4 || gamedatas.gameVariant == 5) {
                    dojo.addClass($('board'), 'board5');
                }
                else {
                    dojo.addClass($('board'), 'board4');

                }
            },

            initPlayerDecks: function (gamedatas) {
                debugger;
                const playerDeckBottom = document.getElementById('player_deck_bottom');
                const playerDeckTop = document.getElementById('player_deck_top');

                const bottomDecks = {
                    infantry: gamedatas.decks.bottom.infantry,
                    battleship: gamedatas.decks.bottom.battleship,
                    tank: gamedatas.decks.bottom.tank,
                    chopper: gamedatas.decks.bottom.chopper,
                    artillery: gamedatas.decks.bottom.artillery
                };

                const topDecks = {
                    infantry: gamedatas.decks.top.infantry,
                    battleship: gamedatas.decks.top.battleship,
                    tank: gamedatas.decks.top.tank,
                    chopper: gamedatas.decks.top.chopper,
                    artillery: gamedatas.decks.top.artillery
                };

                this.populateDeck(playerDeckBottom, bottomDecks);
                this.populateDeck(playerDeckTop, topDecks);
            },

            populateDeck: function (deckElement, decks) {
                debugger;
                for (const type in decks) {
                    const unitDeck = deckElement.querySelector(`.${type}_deck`);
                    if (decks[type]) {
                        decks[type].forEach(function (unit, i) {
                            const unitElement = document.createElement('div');
                            unitElement.id = `${unit.type}_${unit.player}_00${i}`
                            unitElement.className = `unit ${unit.type} ${unit.player}`;
                            unitElement.setAttribute("data-color", `${unit.player}`);
                            unitDeck.appendChild(unitElement);
                        })
                    } else {
                        dojo.destroy(`${type}_deck`);
                        dojo.destroy(`${type}_deck_fortified`);
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {

                console.log('Entering state: ' + stateName);
                switch (stateName) {
                    case 'playerFirstEnlist':
                        this.onEnterPlayerFirstEnlist(args);
                        break;
                    case 'playerTurn':
                        this.infantryEnlistCount = 0;
                        this.updateActionCounter(args.args.actionsRemaining);
                        break;
                    case 'playerFirstTurn':
                        this.enlist()
                        break;
                    case 'enlist':
                        break;
                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        
                        break;
                   */


                    case 'dummmy':
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'playerTurn':
                            //this.addActionButton('btnFortify', _('Fortify'), 'onFortifyButtonClick');
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            
            */

            updateActionCounter: function (actionsRemaining) {
                // Update the UI to show the number of actions remaining
                var actionCounterElement = $('action-counter');
                if (actionCounterElement) {
                    actionCounterElement.innerHTML = _('Actions remaining: ${actionsRemaining}')
                        .replace('${actionsRemaining}', actionsRemaining);
                }
            },

            removeAllHighlights: function () {
                // Remove highlights from all board slots
                var highlightedSlots = document.querySelectorAll('.board-slot.highlighted');
                highlightedSlots.forEach(function (slot) {
                    slot.classList.remove('highlighted');
                });

                // Remove selection from all units
                var selectedUnits = document.querySelectorAll('.unit.selected');
                selectedUnits.forEach(function (unit) {
                    unit.classList.remove('selected');
                });
            },

            // Converts the given unit to fortified version
            updateToFortifiedUnit: function (unitElement, unitType, playerColor) {
                dojo.addClass(unitElement, 'fortified');
            },

            // Gets the details of the provided unit
            getUnitDetails: function (unitId) {
                var unitElement = $(unitId);
                return {
                    unit_id: unitId,
                    x: parseInt(unitElement.parentNode.dataset.x),
                    y: parseInt(unitElement.parentNode.dataset.y),
                    type: this.getUnitType(unitElement),
                    player_id: this.getUnitPlayerId(unitElement),
                    is_fortified: unitElement.classList.contains('fortified')
                };
            },

            // Finds if the unit is on the board or not
            isUnitOnBoard: function (unit) {
                if (unit.parentNode.classList.contains('board-slot'))
                    return true;
                else
                    return false;
            },

            // Add this new function to highlight friendly battleships
            highlightFriendlyBattleships: function () {
                debugger;
                var friendlyBattleships = dojo.query('.board-slot > .battleship.' + this.playerColor);
                if (friendlyBattleships && friendlyBattleships.length > 0) {
                    friendlyBattleships.forEach(function (battleship) {
                        dojo.addClass(battleship.parentNode, 'highlighted');
                    });
                }
                else {
                    this.showMessage(_("No friendly battleship available to enlist."), 'info');
                }
            },

            highlightValidUnitsForFortification() {
                // Remove existing highlights
                dojo.query('.unit.highlight-fortify').removeClass('highlight-fortify');

                // Highlight valid units for fortification
                dojo.query('.unit.' + this.player_color).forEach(unit => {
                    debugger;
                    if (this.isValidForFortification(unit)) {
                        dojo.addClass(unit, 'highlight-fortify');
                    }
                });
            },

            isValidForFortification(unit) {
                debugger;
                if (dojo.hasClass(unit, 'chopper')) {
                    // Special rule for Choppers
                    var chopperSpace = unit.parentNode;
                    var x = parseInt(chopperSpace.dataset.x);
                    var y = parseInt(chopperSpace.dataset.y);

                    // Check the space below for a friendly fortified battleship
                    var spaceBelow = dojo.query('.board-slot[data-x="' + x + '"][data-y="' + (y + 1) + '"]')[0];
                    if (spaceBelow) {
                        var battleshipBelow = dojo.query('.unit.battleship.' + this.player_color + '.fortified', spaceBelow)[0];
                        return !!battleshipBelow;
                    }
                    return false;
                } else {
                    // For other unit types, use existing logic (assuming it's implemented)
                    // This might involve checking for valid formations
                    return this.checkValidFormation(unit);
                }
            },

            divYou: function () {
                var color = this.gamedatas.players[this.player_id].color;
                var color_bg = "";
                if (this.gamedatas.players[this.player_id] && this.gamedatas.players[this.player_id].color_back) {
                    color_bg = "background-color:#" + this.gamedatas.players[this.player_id].color_back + ";";
                }
                var you = "<span style=\"font-weight:bold;color:" + color + ";" + color_bg + "\">" + __("lang_mainsite", "You") + "</span>";
                return you;
            },

            setupPointsDisplay: function (players) {
                var pointsContainer = $('points_container');
                for (var playerId in players) {
                    var player = players[playerId];
                    var pointsDiv = dojo.create('div', {
                        id: 'player_points_' + playerId,
                        class: 'player_points',
                        innerHTML: '<span style="color:#' + player.color + ';">' + player.name + '</span>: ' +
                            '<span class="points_value" id="points_value_' + playerId + '">' + player.score + '</span> ' +
                            this.gamedatas.POINTS_LABEL
                    }, pointsContainer);
                }
            },

            updatePointsDisplay: function (playerId, points) {
                // Update the points display for the given player
                var pointsElement = $('points_value_' + playerId);
                if (pointsElement) {
                    pointsElement.innerHTML = points;
                }
            },

            generateBoardSlots: function (gridSize) {
                // Define the structure of the board based on grid size
                let boardStructure;
                // Get the container element
                const slotsContainer = document.getElementById('slots_container');

                if (gridSize === '4x5') {
                    boardStructure = [
                        ['water', 'water', 'water', 'shore'],
                        ['water', 'water', 'shore', 'land'],
                        ['water', 'shore', 'land', 'land'],
                        ['shore', 'land', 'land', 'land'],
                        ['land', 'land', 'land', 'land']
                    ];
                } else if (gridSize === '5x5') {
                    boardStructure = [
                        ['water', 'water', 'shore', 'land', 'land'],
                        ['water', 'water', 'shore', 'land', 'land'],
                        ['water', 'water', 'shore', 'land', 'land'],
                        ['water', 'water', 'shore', 'land', 'land'],
                        ['water', 'water', 'shore', 'land', 'land']
                    ];

                    slotsContainer.style.width = '83%';
                    slotsContainer.style.height = '81%';
                    slotsContainer.style.left = '29px';
                    slotsContainer.style.top = '41px';
                } else {
                    console.error('Invalid grid size. Use "4x5" or "5x5".');
                    return;
                }



                // Clear any existing content
                slotsContainer.innerHTML = '';

                // Set the grid layout based on the size
                slotsContainer.style.gridTemplateColumns = `repeat(${boardStructure[0].length}, 1fr)`;


                // Loop through the board structure and create slots
                for (let y = 0; y < boardStructure.length; y++) {
                    for (let x = 0; x < boardStructure[y].length; x++) {
                        // Create a new div element for the slot
                        const slot = document.createElement('div');

                        // Set the id attribute
                        slot.id = `board_slot_${x}_${y}`;

                        // Set the class attributes
                        slot.className = `board-slot ${boardStructure[y][x]}`;

                        // Set data attributes
                        slot.dataset.x = x;
                        slot.dataset.y = y;

                        // Append the slot to the container
                        slotsContainer.appendChild(slot);
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*
            
                Here, you are defining methods to handle player's action (ex: results of mouse click on 
                game objects).
                
                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server
            
            */
            enlist: function () {
                this.removeAllHighlights();

                this.gameState = 'enlist';
                this.updateActionButtons('enlist');
            },

            finishEnlist: function (unitType, x, y, unitId, is_fortified) {

                if (this.checkAction('enlist')) {
                    this.ajaxcall("/fortify/fortify/enlist.html", {
                        unitType: unitType,
                        x: x,
                        y: y,
                        unitId: unitId,
                        is_fortified: is_fortified,
                        lock: true
                    }, this, function (result) {
                        this.removeSlotHighlight();
                        this.removeUnitHighlight();
                        this.selectedUnit = null;
                        this.selectedSpecialUnit = null;

                        // Check if this was the first infantry enlistment
                        if (result.infantryEnlistCount == 1) {
                            this.showMessage(_("You can enlist another infantry unit for free"), 'info');
                            this.restrictToInfantryEnlistment();
                        }
                    }, function (is_error) {

                        // What to do after the server call in any case
                    });
                }
            },
            restrictToInfantryEnlistment: function () {
                // Disable all action buttons except for enlist
                //dojo.query('.action-button').forEach(function (button) {
                //    if (button.id !== 'btnEnlist') {
                //        dojo.style(button, 'display', 'none');
                //    }
                //});

                // Highlight only infantry units in the player's supply
                //dojo.query('.unit').removeClass('highlighted');
                //dojo.query('.unit.infantry:not(.board-slot .unit)').addClass('highlighted');

                // Disable selection of non-infantry units
                this.infantryOnlyMode = true;
            },
            onEnterPlayerFirstEnlist: function (args) {
                this.gameState = 'firstEnlisting';
                this.updateActionButtons('enlist');
                //this.highlightValidFirstEnlistSpaces();
            },

            highlightValidFirstEnlistSpaces: function () {
                // Highlight only shore spaces for the first enlistment
                var shoreSpaces = document.querySelectorAll('.board-slot.shore');
                shoreSpaces.forEach(space => {
                    space.classList.add('highlighted');
                });
            },

            isSlotOccupied: function (slot) {

                if (slot.childElementCount > 0)
                    return true;
                else
                    return false;
            },
            resetGameState: function () {
                this.gameState = '';
                this.selectedUnit = null;
                this.removeAllHighlights();
                this.updateActionButtons('');
            },
            onFortifyButtonClick: function (evt) {
                if (!this.checkAction('fortify')) {
                    return;
                }

                if (!this.fortifyMode) {
                    this.fortifyMode = true;
                    dojo.addClass('btnFortify', 'active');
                    this.showMessage(_("Select a unit to fortify"), 'info');
                    this.highlightValidUnitsForFortification();

                    this.removeSlotHighlight();
                    this.clearHighlights();
                } else {
                    this.exitFortifyMode();
                }
            },

            exitFortifyMode: function () {
                this.fortifyMode = false;
                dojo.removeClass('btnFortify', 'active');
                this.showMessage(_("Fortify mode deactivated"), 'info');
            },

            fortify: function (unitId) {

                if (this.fortifyMode) {
                    //var unitId = evt.currentTarget.id;

                    this.ajaxcall("/fortify/fortify/fortify.html", {
                        unitId: unitId,
                        lock: true
                    }, this, function (result) {
                        this.removeUnitHighlight();
                        this.removeSlotHighlight();
                        this.exitFortifyMode();
                    }, function (is_error) {
                        if (is_error) {
                            this.removeUnitHighlight();
                            this.removeSlotHighlight();
                            this.exitFortifyMode();
                        }
                    });
                }
            },

            startMoveAction: function (event) {

                // Remove existing highlights
                //this.removeAllHighlights();

                // Highlight valid move locations
                this.highlightValidMoves();

                // Set the game state to 'moving'
                this.gameState = 'moving';

                // Update UI to show that we're in 'move' mode
                this.updateActionButtons('move');
            },

            onAttackButtonClick: function (evt) {
                // This method is only reserved for chopper attack.
                // Foritifed chopper must be sitting on top of another enemy unit to attack
                if (!this.checkAction('attack')) {
                    return;
                }
                debugger;
                var parentSlot = this.selectedSpecialUnit.parentNode;
                var siblings = Array.from(parentSlot.children);
                var chopperIndex = siblings.indexOf(this.selectedSpecialUnit);
                var elementAbove;

                if (chopperIndex > 0) {
                    elementAbove = siblings[chopperIndex - 1];
                }
                if (!this.attackMode) {
                    this.attackMode = true;
                    dojo.addClass('btnAttack', 'active');
                    this.attack(this.selectedSpecialUnit.id, elementAbove.id);
                } else {
                    this.exitAttackMode();
                }
            },

            exitAttackMode: function () {
                this.attackMode = false;
                dojo.removeClass('btnAttack', 'active');
                this.showMessage(_("Attack mode deactivated"), 'info');
            },

            highlightValidTargets: function (attackingUnit) {
                if (attackingUnit.type === 'artillery') {
                    this.highlightArtilleryTargets(attackingUnit);
                } else {
                    this.highlightAdjacentTargets(attackingUnit);
                }
            },

            highlightArtilleryTargets: function (artilleryUnit) {
                // Get all units on the board
                var allUnits = this.getAllUnitsOnBoard();

                allUnits.forEach(unit => {
                    if (artilleryUnit.is_fortified && unit.player_id != this.player_id) {
                        // Check if the unit is in the same row or column as the artillery
                        if (unit.x === artilleryUnit.x || unit.y === artilleryUnit.y) {
                            // Check fortification rules
                            if (!unit.is_fortified || (unit.is_fortified && artilleryUnit.is_fortified)) {
                                this.highlightUnit(unit.unit_id);
                            }
                        }
                    }
                });
            },

            highlightAdjacentTargets: function (attackingUnit) {
                debugger;
                var adjacentUnits = this.getAdjacentUnits(attackingUnit, true);

                adjacentUnits.forEach(unit => {
                    if (unit.player_id != this.player_id) {
                        // Check if the attacking unit is fortified or in formation
                        if (attackingUnit.is_fortified) {
                            // If the defending unit is fortified, only highlight if the attacking unit is also fortified
                            if (!unit.is_fortified || (unit.is_fortified && attackingUnit.is_fortified)) {
                                this.highlightUnit(unit.unit_id);
                            }
                        }
                    }
                });
            },

            getAllUnitsOnBoard: function () {
                // This function should return all units currently on the board
                debugger;
                var allUnitsOnBoard = document.querySelectorAll('.board-slot > .unit');
                return Array.from(allUnitsOnBoard).map(unit => {
                    return this.getUnitDetails(unit);
                });
            },

            getAdjacentUnits: function (unit, isEnemyUnit) {
                var adjacentUnits = [];
                var directions = [
                    { dx: -1, dy: 0 },  // Left
                    { dx: 1, dy: 0 },   // Right
                    { dx: 0, dy: -1 },  // Up
                    { dx: 0, dy: 1 }    // Down
                ];

                directions.forEach(dir => {

                    var adjacentX = parseInt(unit.x) + dir.dx;
                    var adjacentY = parseInt(unit.y) + dir.dy;

                    // Check if the adjacent position is within the board boundaries
                    if (adjacentX >= 0 && adjacentX < 5 && adjacentY >= 0 && adjacentY < 5) {
                        var adjacentSlot = $('board_slot_' + adjacentX + '_' + adjacentY);
                        var adjacentUnit = adjacentSlot.querySelector('.unit');

                        if (adjacentUnit) {
                            if (isEnemyUnit && adjacentUnit.dataset.color == this.playerColor)
                                return;

                            adjacentUnits.push({
                                unit_id: adjacentUnit.id,
                                x: adjacentX,
                                y: adjacentY,
                                type: this.getUnitType(adjacentUnit),
                                player_id: this.getUnitPlayerId(adjacentUnit),
                                is_fortified: adjacentUnit.classList.contains('fortified')
                            });
                        }
                    }
                });

                return adjacentUnits;
            },

            getUnitType: function (unitElement) {
                if (unitElement.classList.contains('infantry')) return 'infantry';
                if (unitElement.classList.contains('tank')) return 'tank';
                if (unitElement.classList.contains('battleship')) return 'battleship';
                if (unitElement.classList.contains('chopper')) return 'chopper';
                if (unitElement.classList.contains('artillery')) return 'artillery';
                return 'unknown';
            },

            getUnitPlayerId: function (unitElement) {
                if (unitElement.classList.contains('red')) return Object.values(this.gamedatas.players).find(element => element.color === 'red').id;
                if (unitElement.classList.contains('green')) return Object.values(this.gamedatas.players).find(element => element.color === 'green').id;
                return null;
            },

            highlightUnit: function (unitId) {
                var unitElement = $(unitId);
                if (unitElement) {
                    dojo.addClass(unitElement, 'highlight-target');

                    // Add a click event listener for the highlighted unit
                    dojo.connect(unitElement, 'onclick', this, function (evt) {
                        if (this.attackMode && this.selectedAttackingUnit) {
                            this.attack(this.selectedAttackingUnit, unitId);
                            this.clearHighlights();
                        }
                    });
                }
            },

            clearHighlights: function () {
                // Remove highlights and click listeners from all units
                dojo.query('.unit.highlight-target').forEach(unitElement => {
                    dojo.removeClass(unitElement, 'highlight-target');
                    //dojo.disconnect(unitElement, 'onclick');
                });
            },

            attack: function (attackingUnitId, defendingUnitId) {
                this.ajaxcall("/fortify/fortify/attack.html", {
                    attackingUnitId: attackingUnitId,
                    defendingUnitId: defendingUnitId,
                    lock: true
                }, this, function (result) {
                    debugger;
                    this.selectedSpecialUnit ? dojo.style(this.selectedSpecialUnit, 'margin', this.unitMarginStyle) : null;

                    this.removeUnitHighlight();
                    this.removeSlotHighlight();
                    this.selectedUnit = null;
                    this.selectedSpecialUnit = null;
                    dojo.removeClass('btnAttack', 'active');
                    dojo.style($('btnAttack'), 'display', 'none');
                    this.clearHighlights();
                }, function (is_error) {
                    if (is_error) {
                        this.removeUnitHighlight();
                        this.removeSlotHighlight();
                        dojo.removeClass('btnAttack', 'active');
                        dojo.style($('btnAttack'), 'display', 'none');
                        this.attackMode = false;
                        this.clearHighlights();
                    }
                });
            },

            updateReinforcementTrack: function (reinforcementTrack) {
                // Move units to reinforcement track
                for (var position in reinforcementTrack) {
                    var unit = reinforcementTrack[position];
                    var slotId = 'reinforcement_slot_' + unit.position;
                    var slot = $(slotId);

                    if (slot) {
                        var unitDiv = $(unit.unit_id);
                        if (unitDiv) {
                            // If the unit div exists, move it to the reinforcement track slot
                            dojo.setAttr(unitDiv, 'style', '');
                            dojo.addClass(unitDiv, 'reinforcement');
                            dojo.place(unitDiv, slot);

                            // Update fortified status if needed
                            if (unit.is_fortified == '1') {
                                dojo.addClass(unitDiv, 'fortified');
                            } else {
                                dojo.removeClass(unitDiv, 'fortified');
                            }
                        } else {
                            // If the unit div doesn't exist (shouldn't happen normally), create it
                            // unitDiv = this.createUnitDiv(unit.unit_id, unit.type, this.gamedatas.players[unit.player_id].color);
                            // if (unit.is_fortified == '1') {
                            //     dojo.addClass(unitDiv, 'fortified');
                            // }
                            // dojo.place(unitDiv, slot);
                        }
                    }
                }
            },

            updatePlayerSupply: function (unit) {
                debugger;
                var playerDeckId = 'player_deck_' + (unit.unit_id.dataset.color == 'red' ? 'bottom' : 'top');
                var playerDeck = $(playerDeckId);

                if (playerDeck) {
                    var deckId = unit.type + '_deck';
                    if (unit.is_fortified == '1') {
                        deckId += '_fortified';
                    }

                    var deck = dojo.query('#' + playerDeckId + ' > #' + deckId)[0];

                    if (deck) {
                        var unitDiv;
                        if (unit.unit_id) {
                            unitDiv = unit.unit_id;
                            dojo.removeClass(unitDiv, 'reinforcement');
                        }
                        else {
                            unitDiv = this.createUnitDiv(unit.unit_id, unit.type, this.gamedatas.players[unit.player_id].color);
                            if (unit.is_fortified == '1') {
                                dojo.addClass(unitDiv, 'fortified');
                            }
                        }

                        dojo.place(unitDiv, deck);
                    } else {
                        console.error('Deck not found:', deckId);
                    }
                } else {
                    console.error('Player deck not found:', playerDeckId);
                }
            },

            updateActionButtons: function (activeAction) {
                var actions = ['enlist', 'move', 'fortify', 'attack'];
                actions.forEach(action => {
                    var button = $('btn' + action.charAt(0).toUpperCase() + action.slice(1));
                    if (button) {
                        if (action === activeAction) {
                            button.classList.add('active');
                        } else {
                            button.classList.remove('active');
                        }
                    }
                });
            },

            highlightValidMoves: function () {

                var selectedUnit = this.selectedUnit;
                if (!selectedUnit) return;

                let selectedUnitDetails = this.getUnitDetails(selectedUnit);
                var x = selectedUnitDetails.x;
                var y = selectedUnitDetails.y;

                if (selectedUnitDetails.type === 'chopper') {
                    debugger;
                    // For choppers, highlight all empty spaces
                    document.querySelectorAll('.board-slot:not(:has(.unit))').forEach(slot => {
                        slot.classList.add('highlighted');
                    });

                    // Highlight friendly units not occupied by choppers
                    document.querySelectorAll(`.board-slot:not(.highlighted) > .unit.${this.playerColor}:not(.selected)`).forEach(unit => {
                        if (this.isUnitOnBoard(unit) && !unit.parentNode.querySelector('.unit.chopper')) {
                            unit.parentNode.classList.add('highlighted')
                        }
                        //let battleshipSlot = battleship.closest('.board-slot');
                        //let chopperOnBattleship = battleship ? battleship.querySelector('.unit.chopper') : null;
                        //if (!chopperOnBattleship) {
                        //    battleshipSlot ? battleshipSlot.classList.add('highlighted') : null;
                        //}
                    });

                    // Highlight enemy units not occupied by choppers
                    document.querySelectorAll(`.unit:not(.${this.playerColor})`).forEach(unit => {
                        if (this.isUnitOnBoard(unit) && !unit.parentNode.querySelector('.unit.chopper')) {
                            unit.parentNode.classList.add('highlighted')
                        }
                        //let battleshipSlot = battleship.closest('.board-slot');
                        //let chopperOnBattleship = battleship ? battleship.querySelector('.unit.chopper') : null;
                        //if (!chopperOnBattleship) {
                        //    battleshipSlot ? battleshipSlot.classList.add('highlighted') : null;
                        //}
                    });
                }
                else {
                    // Special movement rule for tanks
                    if (selectedUnitDetails.type === 'tank') {
                        // Highlight all empty land and shore spaces for tanks
                        document.querySelectorAll('.board-slot:not(:has(.unit))').forEach(slot => {
                            if (slot.classList.contains('land') || slot.classList.contains('shore')) {
                                slot.classList.add('highlighted');
                            }
                        });
                    } else {
                        // Highlight orthogonal adjacent empty spaces
                        var orthogonalDirections = [
                            { dx: 0, dy: -1 }, // up
                            { dx: 0, dy: 1 },  // down
                            { dx: -1, dy: 0 }, // left
                            { dx: 1, dy: 0 }   // right
                        ];

                        orthogonalDirections.forEach(dir => {

                            var newX = x + dir.dx;
                            var newY = y + dir.dy;
                            var slotType = '';
                            switch (selectedUnitDetails.type) {
                                case 'infantry':
                                case 'tank':
                                    slotType = "land";
                                    break;
                                case 'battleship':
                                    slotType = "water";
                                    break;
                                case 'artillery':
                                    slotType = "land";
                                    break;
                            }
                            // Artillery is a land only unit
                            if (selectedUnitDetails.type == 'artillery')
                                var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]:is(.${slotType})`);
                            else
                                var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]:is(.${slotType}, .shore)`);

                            if (slot && !slot.hasChildNodes()) {
                                slot.classList.add('highlighted');
                            }
                        });


                        // Highlight spaces orthogonally adjacent to friendly units on board
                        var friendlyUnits = document.querySelectorAll(`.board-slot > .unit.${this.playerColor}`);
                        friendlyUnits.forEach(unit => {
                            var unitX = parseInt(unit.parentNode.dataset.x);
                            var unitY = parseInt(unit.parentNode.dataset.y);
                            orthogonalDirections.forEach(dir => {
                                var newX = unitX + dir.dx;
                                var newY = unitY + dir.dy;
                                var slotType = '';
                                switch (selectedUnitDetails.type) {
                                    case 'infantry':
                                    case 'tank':
                                        slotType = "land";
                                        break;
                                    case 'battleship':
                                        slotType = "water";
                                        break;
                                    case 'artillery':
                                        slotType = "land";
                                        break;
                                }
                                if (selectedUnitDetails.type == 'artillery')
                                    var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]:is(.${slotType})`);
                                else
                                    var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]:is(.${slotType}, .shore)`);

                                if (slot && !slot.hasChildNodes() && (newX !== x || newY !== y)) {
                                    slot.classList.add('highlighted');
                                }
                            });
                        });
                    }
                }
            },

            moveUnit: function (unitId, unitType, toX, toY) {
                if (this.checkAction('move')) {
                    this.ajaxcall("/fortify/fortify/move.html", {
                        unitId: unitId,
                        unitType: unitType,
                        toX: toX,
                        toY: toY,
                        lock: true
                    }, this, function (result) {
                        this.removeUnitHighlight();
                        this.removeSlotHighlight();
                        this.selectedUnit = null;
                        this.selectedSpecialUnit = null;
                        dojo.removeClass('btnAttack', 'active');
                        dojo.style($('btnAttack'), 'display', 'none');
                        this.clearHighlights();
                    }, function (is_error) {
                        if (is_error) {
                            dojo.removeClass('btnAttack', 'active');
                            dojo.style($('btnAttack'), 'display', 'none');
                        }
                    });
                }
            },

            removeButtonHighlight: function () {
                let allActionButtons = document.querySelectorAll(".btn-active");

                if (allActionButtons && allActionButtons.length > 0) {
                    allActionButtons.forEach(actionButtons => {
                        actionButtons.classList.remove("btn-active");
                    });
                }
            },

            resetBoard: function (players) {
                debugger;
                //this.playerColor = this.gamedatas.players[this.player_id].color;

                // Store the game variant
                this.gameVariant = this.gamedatas.gameVariant;
                //his.gamedatas.players = players;

                Object.keys(this.gamedatas.players).forEach(id => {
                    if (players[id]) {
                        this.gamedatas.players[id].color = players[id].player_color;
                    }
                });

                // Remove all units from the board
                dojo.query('.board-slot .unit').forEach(dojo.destroy);
                // Remove all units from the deck
                dojo.query('.unit_deck .unit').forEach(dojo.destroy);

                // Clear the reinforcement track
                dojo.query('#reinforcement_track .reinforcement_slot').forEach(function (slot) {
                    dojo.empty(slot);
                });

                // Reset player decks
                //this.resetPlayerDecks();

                // Clear any highlights or selections
                dojo.query('.highlighted').removeClass('highlighted');
                dojo.query('.selected').removeClass('selected');

                // Reset any game state variables
                this.selectedUnit = null;
                this.selectedSpecialUnit = null;
                this.fortifyMode = false;
                this.attackMode = false;

                // Reset action buttons
                this.updateActionButtons('');

                this.initPlayerDecks(this.gamedatas);

                // Setup game notifications to handle (see "setupNotifications" method below)
                //this.setupNotifications();

                // Add event listeners to the units
                dojo.query('.unit').connect('onclick', this, dojo.hitch(this, 'handleUnitClick'));

                dojo.hitch(this, this.highlightValidMoves)();

                this.addEventListenserForActionButtons(this.gamedatas.gamestate.possibleactions);

                // Add event listener for the attack button
                dojo.connect($('btnAttack'), 'onclick', this, 'onAttackButtonClick');
            },

            resetPlayerDecks: function () {
                var players = this.gamedatas.players;
                for (var playerId in players) {
                    var color = players[playerId].color;
                    var deckId = (playerId == this.player_id) ? 'player_deck_bottom' : 'player_deck_top';
                    var deck = $(deckId);

                    // Clear existing units
                    dojo.empty(deck);

                    // Add new units to the deck
                    this.addUnitsToDeck(deck, 'infantry', color, 4);
                    this.addUnitsToDeck(deck, 'tank', color, 4);
                    this.addUnitsToDeck(deck, 'battleship', color, 4);

                    if (this.gameVariant == 3) {  // Special Warfare variant
                        this.addUnitsToDeck(deck, 'chopper', color, 1);
                        this.addUnitsToDeck(deck, 'artillery', color, 1);
                    }
                }
            },

            addUnitsToDeck: function (deck, unitType, color, count) {
                for (var i = 1; i <= count; i++) {
                    var unitId = unitType + '_' + color + '_' + i;
                    var unitDiv = this.createUnitDiv(unitId, unitType, color);
                    dojo.place(unitDiv, deck);
                }
            },


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your fortify.game.php file.
            
            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                dojo.subscribe('unitEnlisted', this, "notif_unitEnlisted");
                dojo.subscribe('actionsRemaining', this, "notif_actionsRemaining");
                dojo.subscribe('unitMoved', this, "notif_unitMoved");
                dojo.subscribe('unitsFortified', this, "notif_unitsFortified");
                dojo.subscribe('unitAttacked', this, "notif_unitAttacked");
                dojo.subscribe('unitReturnedToSupply', this, "notif_unitReturnedToSupply");
                dojo.subscribe('reinforcementTrackUpdated', this, "notif_reinforcementTrackUpdated");
                dojo.subscribe('newVolley', this, "notif_newVolley");
                dojo.subscribe('updatePlayerPanel', this, "notif_updatePlayerPanel");
                dojo.subscribe('pointsUpdated', this, "notif_pointsUpdated");
            },

            notif_newVolley: function (notif) {
                debugger;
                // Reset the board
                this.resetBoard(notif.args.players);
                //this.setup(this.gamedatas);
                // Update player colors and decks
                for (var playerId in notif.args.players) {
                    var player = notif.args.players[playerId];
                    //this.updatePlayerColor(playerId, player.player_color);
                    //this.resetPlayerDeck(playerId, player.player_color);
                }

                // Show a message about the new volley
                this.showMessage(_("A new volley begins! Players have switched colors."), 'info');
            },
            notif_updatePlayerPanel: function (notif) {
                this.updatePlayerColor(notif.args.player_id, notif.args.player_color);
            },

            updatePlayerColor: function (playerId, color) {
                // Update any other UI elements that depend on player color
                debugger;
                if (playerId == this.getCurrentPlayerId())
                    this.playerColor = color;
            },

            resetPlayerDeck: function (playerId, color) {
                // Clear existing deck
                var deckId = (playerId == this.player_id) ? 'player_deck_bottom' : 'player_deck_top';
                dojo.empty(deckId);

                // Add new units to the deck
                this.addUnitsToDeck(deckId, 'infantry', color, 4);
                this.addUnitsToDeck(deckId, 'tank', color, 4);
                this.addUnitsToDeck(deckId, 'battleship', color, 4);

                // Add special units if applicable
                if (this.gamedatas.gameVariant == 3) {
                    this.addUnitsToDeck(deckId, 'chopper', color, 1);
                    this.addUnitsToDeck(deckId, 'artillery', color, 1);
                }
            },

            addUnitsToDeck: function (deckId, unitType, color, count) {
                for (var i = 1; i <= count; i++) {
                    var unitId = unitType + '_' + color + '_' + i;
                    var unitDiv = this.createUnitDiv(unitId, unitType, color);
                    dojo.place(unitDiv, deckId);
                }
            },

            notif_actionsRemaining: function (notif) {
                this.updateActionCounter(notif.args.actionsRemaining);
            },

            notif_unitEnlisted: function (notif) {
                debugger;
                console.log('Notification received: unitEnlisted', notif);

                if (notif.args.infantryEnlistCount == 1) {
                    if (notif.args.player_id == this.player_id) {
                        this.showMessage(_("You can enlist another infantry unit for free"), 'info');
                        this.restrictToInfantryEnlistment();
                    } else {
                        this.showMessage(_(notif.args.player_name + " can enlist another infantry unit for free"), 'info');
                    }
                } else {
                    // Reset to normal mode
                    this.infantryOnlyMode = false;
                    //dojo.query('.action-button').style('display', 'inline-block');
                }

                // Create or move the unit on the board
                var unitId = notif.args.unitId;
                var unitType = notif.args.unit_type;
                var x = notif.args.x;
                var y = notif.args.y;
                var playerColor = notif.args.player_color;
                var specialUnitId = notif.args.special_unit_id;

                if (specialUnitId) {
                    unitId = specialUnitId;
                }

                // Check if the unit already exists (it might for the player who made the move)
                var unitDiv = $(unitId);
                if (!unitDiv) {
                    // If it doesn't exist, create it
                    unitDiv = this.createUnitDiv(unitId, unitType, playerColor);
                }

                // Move the unit to the correct position on the board
                var slot = $('board_slot_' + x + '_' + y);
                if (slot) {
                    slot.appendChild(unitDiv);
                    if (specialUnitId)
                        unitDiv.style.margin = "0 -75px";
                    else
                        unitDiv.style.margin = this.unitMarginStyle;
                }
            },

            notif_unitMoved: function (notif) {
                debugger;
                // Move the unit on the client side
                var unit = $(notif.args.unit_Id);
                var toSlot = $('board_slot_' + notif.args.toX + '_' + notif.args.toY);

                if (unit && toSlot) {
                    if (this.isSlotOccupied(toSlot)) {
                        unit.style = "margin: 0px -75px";
                    }
                    else {
                        unit.style = `margin: ${this.unitMarginStyle}`;
                    }
                    toSlot.appendChild(unit);
                }

                this.resetGameState();
            },

            notif_unitsFortified: function (notif) {
                var unitElement = $(notif.args.unit.unit_id);
                if (unitElement) {
                    this.updateToFortifiedUnit(unitElement, notif.args.unit.type, this.gamedatas.players[notif.args.player_id].color);
                }

                this.showMessage(_("${player_name} fortified a ${unit_type}").replace('${player_name}', notif.args.player_name).replace('${unit_type}', notif.args.unit_type), 'info');
            },

            notif_unitAttacked: function (notif) {
                // Update the reinforcement track
                debugger;
                if (notif.args.attacking_unit_type == 'chopper') {
                    dojo.style($(notif.args.attacking_unit_id), 'margin', this.unitMarginStyle);
                }

                this.updateReinforcementTrack(notif.args.reinforcementTrack);
                this.clearHighlights();
                this.showMessage(_("${player_name} attacked ${defending_unit_type} with ${attacking_unit_type}")
                    .replace('${player_name}', notif.args.player_name)
                    .replace('${defending_unit_type}', notif.args.defending_unit_type)
                    .replace('${attacking_unit_type}', notif.args.attacking_unit_type));

                this.exitAttackMode();
            },

            notif_reinforcementTrackUpdated: function (notif) {
                debugger;
                console.log('Notification: Reinforcement track updated', notif);

                // Update the reinforcement track with the new state
                for (var unitId in notif.args.reinforcementTrack) {
                    var unit = notif.args.reinforcementTrack[unitId];
                    var slotId = 'reinforcement_slot_' + unit.position;
                    var slot = $(slotId);

                    if (slot) {
                        var unitDiv = $(unit.unit_id);
                        if (unitDiv) {
                            // If the unit div exists, move it to the reinforcement track slot
                            dojo.place(unitDiv, slot);
                            dojo.addClass(unitDiv, 'reinforcement');

                            // Update fortified status if needed
                            if (unit.is_fortified == '1') {
                                dojo.addClass(unitDiv, 'fortified');
                            } else {
                                dojo.removeClass(unitDiv, 'fortified');
                            }
                        } else {
                            // If the unit div doesn't exist, create it
                            unitDiv = this.createUnitDiv(unit.unit_id, unit.type, this.gamedatas.players[unit.player_id].color);
                            if (unit.is_fortified == '1') {
                                dojo.addClass(unitDiv, 'fortified');
                            }
                            dojo.place(unitDiv, slot);
                        }
                    }
                }
            },

            notif_unitReturnedToSupply: function (notif) {
                debugger;
                this.showMessage(_("A ${unit_type} has returned to ${player_name}'s supply")
                    .replace('${unit_type}', notif.args.unit_type)
                    .replace('${player_name}', notif.args.player_name));
                var unit = this.getUnitDetails($(notif.args.unit_id));
                // Update the player's supply in the UI
                this.updatePlayerSupply(unit);
            },

            createUnitDiv: function (unitId, unitType, playerColor) {
                var unitDiv = dojo.create('div', {
                    id: unitId,
                    class: 'unit ' + unitType + ' ' + playerColor
                });
                return unitDiv;
            },

            notif_pointsUpdated: function (notif) {
                debugger;
                this.updatePointsDisplay(notif.args.playerId, notif.args.points);
            }
        });
    });
