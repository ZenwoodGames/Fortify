/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Fortify implementation : © <Your name here> <Your email address here>
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

                console.log("Starting game setup");

                // Setting up player boards
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];
                    // TODO: Setting up players boards if needed
                }

                this.playerColor = gamedatas.players[this.player_id].color;

                // Initialize the game board
                this.initBoard(gamedatas);

                // Initialize player decks
                this.initPlayerDecks(gamedatas);

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                // Add event listeners to the units
                dojo.query('.unit').connect('onclick', this, dojo.hitch(this, 'handleUnitClick'));

                // const units = document.querySelectorAll('.unit');
                // units.forEach(unit => {
                //     unit.addEventListener('click', this.handleUnitClick);
                // });

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

                debugger;
                // Initialize the reinforcement track
                if (gamedatas.reinforcementTrack)
                    this.updateReinforcementTrack(gamedatas.reinforcementTrack);

                // Add event listener for the attack button
                dojo.connect($('btnAttack'), 'onclick', this, 'onAttackButtonClick');

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
                    slot.appendChild(unitDiv);
                    unitDiv.style = "margin: 2px 0 0 7px;"
                }
                if (is_fortified == 1)
                    this.updateToFortifiedUnit(unitDiv);
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

                if (possibleActions.indexOf('enlist') == 0)
                    dojo.query('#btnEnlist').connect('onclick', this, dojo.hitch(this, 'enlist'));

                if (possibleActions.indexOf('fortify') == 0)
                    dojo.query('#btnFortify').connect('onclick', this, dojo.hitch(this, 'fortify'));

                if (possibleActions.indexOf('attack') == 0)
                    dojo.query('#btnAttack').connect('onclick', this, dojo.hitch(this, 'attack'));

                if (possibleActions.indexOf('move') == 0)
                    dojo.query('#btnMove').connect('onclick', this, dojo.hitch(this, 'move'));
            },

            handleUnitClick: function (event) {
                debugger;
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
                debugger;
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
                        switch (event.target.classList[1]) {
                            case 'battleship':
                                const waterSlot = document.querySelectorAll('.water');
                                waterSlot.forEach(slot => {
                                    slot.classList.add('highlighted');
                                });
                                break;
                            case 'infantry':
                            case 'tank':
                                const landSlot = document.querySelectorAll('.land');
                                landSlot.forEach(slot => {
                                    slot.classList.add('highlighted');
                                });

                                break;
                        }
                    }
                    else {
                        // If unit is on the board, the unit can move/attack
                        //this.highlightValidMoves();
                    }

                    // Deselect previously selected token
                    this.deselectUnit();

                    // this.selectedUnit = document.querySelectorAll('.selected');
                    // if (this.selectedUnit && this.selectedUnit.length > 0) {
                    //     this.selectedUnit.forEach(sUnit => {
                    //         sUnit.classList.remove('selected');
                    //     });
                    // }
                    // const highlightesShoreSpaces = document.querySelectorAll('.highlighted');
                    // if (this.highlightesShoreSpaces && this.highlightesShoreSpaces.length > 0) {
                    //     this.highlightesShoreSpaces.forEach(highlightesShoreSpace => {
                    //         highlightesShoreSpace.classList.remove('highlighted');
                    //     });
                    // }

                    // this.selectedUnit.classList.add('selected');

                    dojo.addClass(event.target.id, 'selected');
                    // // Highlight shore spaces
                    // const shoreSpaces = document.querySelectorAll('.shore');
                    // shoreSpaces.forEach(space => {
                    //     space.classList.add('highlighted');
                    // });
                }
            },

            handleSlotClick: function (event) {
                debugger;
                let slot = event.target;

                // Fortify action
                if (this.isSlotOccupied(slot) && this.fortifyMode && this.getUnitDetails(event.target).player_id == this.player_id) {
                    this.fortify(this.selectedUnit.id);
                    return;
                }

                /////////////////////////////////////////////////////////////////////////////////////////
                ///////////////////////////////////// Enlist ////////////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////////////
                // If unit is selected and is not on the board, then it is an enlist
                if (this.selectedUnit && !this.isUnitOnBoard(this.selectedUnit)) {
                    if (!this.isSlotOccupied(slot)) {
                        this.finishEnlist(this.selectedUnit.classList[1],
                            slot.dataset.x, slot.dataset.y,
                            this.selectedUnit.id);
                        return;
                    }
                }
                ////////////////////////////////////// End Enlist region //////////////////////////////////

                /////////////////////////////////////////////////////////////////////////////////////////
                /////////////////////////////////////// Attack ////////////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////////////

                if (this.isSlotOccupied(slot)) {
                    if (this.selectedUnit && event.target != this.selectedUnit) {
                        debugger;
                        this.attack(this.selectedUnit.id, event.target.id);
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
                            this.highlightValidTargets(this.getUnitDetails(this.selectedUnit));
                        }
                    }
                    else {
                        // If slot is not occupied, move the token
                        if (event.currentTarget.classList.contains('highlighted')) {
                            var unitId = this.selectedUnit.id;
                            var toX = parseInt(event.currentTarget.dataset.x);
                            var toY = parseInt(event.currentTarget.dataset.y);

                            this.moveUnit(unitId, toX, toY);
                        }
                    }
                }
                ////////////////////////////////////// End Move region //////////////////////////////////

                switch (this.gameState) {
                    case 'moving':
                        var toX = parseInt(event.currentTarget.dataset.x);
                        var toY = parseInt(event.currentTarget.dataset.y);
                        var unitId = this.selectedUnit.id;

                        if (event.currentTarget.classList.contains('highlighted')) {
                            this.moveUnit(unitId, toX, toY);
                        }
                        break;
                    case 'firstEnlisting':
                    case 'enlist':
                        if (!this.isSlotOccupied(event.target)) {
                            this.finishEnlist(this.selectedUnit.classList[1],
                                event.target.dataset.x, event.target.dataset.y,
                                this.selectedUnit.id);

                            // this.selectedUnit.classList.remove("selected");
                            // this.selectedUnit.style = "margin: 2px 0 0 7px;"
                            // event.target.appendChild(this.selectedUnit);
                        }
                        else {
                            // Remove unit selection
                            //this.removeUnitHighlight();
                            //this.removeSlotHighlight();
                        }
                        break;
                }
                // this.selectedUnit = '';
                // this.removeSlotHighlight();
            },



            selectUnit: function (unit) {
                this.clearHighlights();
                //this.selectedUnit = unit;
                dojo.addClass(unit.unit_id, 'selected');

                //this.highlightValidMoves(unit);
                //this.highlightValidAttackTargets(unit);
            },

            deselectUnit: function () {
                dojo.query('.selected').removeClass('selected');
            },

            initBoard: function (gamedatas) {
                // const unitsContainer = document.getElementById('units_container');

                // // Ensure units is an array before using forEach
                // if (Array.isArray(gamedatas.units)) {
                //     gamedatas.units.forEach(unit => {
                //         const unitElement = document.createElement('div');
                //         unitElement.id = unit.id;
                //         unitElement.className = `unit ${unit.type} ${unit.player}`;
                //         unitElement.style.top = `${unit.y * 100}px`;
                //         unitElement.style.left = `${unit.x * 100}px`;
                //         unitsContainer.appendChild(unitElement);
                //     });
                // } else {
                //     console.error("Units data is not an array:", gamedatas.units);
                // }
            },

            initPlayerDecks: function (gamedatas) {
                // Example initialization; modify based on actual data structure
                const playerDeckBottom = document.getElementById('player_deck_bottom');
                const playerDeckTop = document.getElementById('player_deck_top');

                // Assuming gamedatas.decks contains arrays of units for each deck
                const bottomDecks = {
                    infantry: gamedatas.decks.bottom.infantry,
                    battleship: gamedatas.decks.bottom.battleship,
                    tank: gamedatas.decks.bottom.tank
                };

                const topDecks = {
                    infantry: gamedatas.decks.top.infantry,
                    battleship: gamedatas.decks.top.battleship,
                    tank: gamedatas.decks.top.tank
                };

                this.populateDeck(playerDeckBottom, bottomDecks);
                this.populateDeck(playerDeckTop, topDecks);
            },

            populateDeck: function (deckElement, decks) {
                for (const type in decks) {
                    const unitDeck = deckElement.querySelector(`.${type}_deck`);
                    decks[type].forEach(function (unit, i) {

                        const unitElement = document.createElement('div');
                        unitElement.id = `${unit.type}_${unit.player}_00${i}`
                        unitElement.className = `unit ${unit.type} ${unit.player}`;
                        unitElement.setAttribute("data-color", `${unit.player}`);
                        unitDeck.appendChild(unitElement);
                    });
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

                // if (this.checkAction('enlist')) {
                // Remove existing highlight
                this.removeAllHighlights();

                this.gameState = 'enlist';
                this.updateActionButtons('enlist');
                // }
            },
            finishEnlist: function (unitType, x, y, unitId) {

                if (this.checkAction('enlist')) {
                    this.ajaxcall("/fortify/fortify/enlist.html", {
                        unitType: unitType,
                        x: x,
                        y: y,
                        unitId: unitId,
                        lock: true
                    }, this, function (result) {
                        this.removeSlotHighlight();
                        this.removeUnitHighlight();
                    }, function (is_error) {

                        // What to do after the server call in any case
                    });
                }
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

                if (slot.classList.contains("unit"))
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
                // if (!this.checkAction('fortify')) {
                //     return;
                // }

                if (!this.fortifyMode) {
                    this.fortifyMode = true;
                    dojo.addClass('btnFortify', 'active');
                    this.showMessage(_("Select a unit to fortify"), 'info');

                    // Add click listeners to all units
                    // dojo.query('.unit').forEach(dojo.hitch(this, function(unitNode) {
                    //     dojo.connect(unitNode, 'onclick', this, 'onUnitClick');
                    // }));
                } else {
                    this.exitFortifyMode();
                }
            },

            exitFortifyMode: function () {
                this.fortifyMode = false;
                // dojo.removeClass('btnFortify', 'active');
                // this.showMessage(_("Fortify mode deactivated"), 'info');

                // // Remove click listeners from all units
                // dojo.query('.unit').forEach(dojo.hitch(this, function(unitNode) {
                //     dojo.disconnect(unitNode, 'onclick', this, 'onUnitClick');
                // }));
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
                        dojo.removeClass('btnFortify', 'active');
                        this.fortifyMode = false;
                    }, function (is_error) {
                        // Error handling
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

                if (!this.checkAction('attack')) {
                    return;
                }

                if (!this.attackMode) {
                    this.attackMode = true;
                    dojo.addClass('btnAttack', 'active');
                    this.showMessage(_("Select a unit to attack with"), 'info');
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
                var adjacentUnits = this.getAdjacentUnits(attackingUnit, true);

                adjacentUnits.forEach(unit => {
                    if (unit.player_id !== this.player_id) {
                        // Check if the attacking unit is fortified or in formation
                        if (attackingUnit.is_fortified || this.isUnitInFormation(attackingUnit)) {
                            // If the defending unit is fortified, only highlight if the attacking unit is also fortified
                            if (!unit.is_fortified || (unit.is_fortified && attackingUnit.is_fortified)) {
                                this.highlightUnit(unit.unit_id);
                            }
                        }
                    }
                });
            },

            isUnitInFormation: function (unit) {
                var adjacentUnits = this.getAdjacentUnits(unit, false);

                switch (unit.type) {
                    case 'battleship':
                        return this.checkBattleshipFormation(unit, adjacentUnits) !== null;
                    case 'infantry':
                        return this.checkInfantryFormation(unit, adjacentUnits) !== null;
                    case 'tank':
                        return this.checkTankFormation(unit, adjacentUnits) !== null;
                    default:
                        return false;
                }
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
                    if (adjacentX >= 0 && adjacentX < 4 && adjacentY >= 0 && adjacentY < 5) {
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
                    dojo.disconnect(unitElement, 'onclick');
                });
            },

            attack: function (attackingUnitId, defendingUnitId) {
                this.ajaxcall("/fortify/fortify/attack.html", {
                    attackingUnitId: attackingUnitId,
                    defendingUnitId: defendingUnitId,
                    lock: true
                }, this, function (result) {
                    // Attack successful
                    debugger;
                    this.selectedUnit = null;
                }, function (is_error) {
                    // Error handling
                    debugger;
                });
            },

            updateReinforcementTrack: function (reinforcementTrack) {
                debugger;
                // Clear existing units from reinforcement track
                //dojo.query('#reinforcement_track .reinforcement_slot').forEach(function (slot) {
                //    dojo.empty(slot);
                //});

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
                
                var deckId = 'player_deck_' + (unit.player_id == this.player_id ? 'bottom' : 'top');
                var deck = $(deckId);
                if (deck) {
                    var unitDiv = this.createUnitDiv(unit.unit_id, unit.type, this.gamedatas.players[unit.player_id].color);
                    if (unit.is_fortified == '1') {
                        dojo.addClass(unitDiv, 'fortified');
                    }
                    dojo.place(unitDiv, deck);
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

                let unit = this.getUnitDetails(selectedUnit);
                var x = unit.x;
                var y = unit.y;

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
                    switch (unit.type) {
                        case 'infantry':
                        case 'tank':
                            slotType = "land";
                            break;
                        case 'battleship':
                            slotType = "water";
                            break;
                    }
                    var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]:is(.${slotType}, .shore)`);
                    if (slot && !slot.hasChildNodes()) {
                        slot.classList.add('highlighted');
                    }
                });

                // Highlight spaces orthogonally adjacent to friendly units
                var friendlyUnits = document.querySelectorAll(`.unit.${this.playerColor}`);
                friendlyUnits.forEach(unit => {
                    var unitX = parseInt(unit.parentNode.dataset.x);
                    var unitY = parseInt(unit.parentNode.dataset.y);
                    orthogonalDirections.forEach(dir => {
                        var newX = unitX + dir.dx;
                        var newY = unitY + dir.dy;
                        var slotType = '';
                        switch (unit.type) {
                            case 'infantry':
                            case 'tank':
                                slotType = "land";
                                break;
                            case 'battleship':
                                slotType = "water";
                                break;
                        }
                        var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]:is(.${slotType}, .shore)`);
                        if (slot && !slot.hasChildNodes() && (newX !== x || newY !== y)) {
                            slot.classList.add('highlighted');
                        }
                    });
                });
            },

            moveUnit: function (unitId, toX, toY) {
                if (this.checkAction('move')) {
                    this.ajaxcall("/fortify/fortify/move.html", {
                        unitId: unitId,
                        toX: toX,
                        toY: toY,
                        lock: true
                    }, this, function (result) {
                        this.removeUnitHighlight();
                        this.removeSlotHighlight();
                    }
                        , function (is_error) {
                            console.log(is_error);
                            // What to do after the server call in any case
                        });
                }
            },

            tryAttack: function (attackingUnitId, defendingUnitId) {
                if (this.checkAction('attack')) {
                    this.ajaxcall("/fortify/fortify/attack.html", {
                        attackingUnitId: attackingUnitId,
                        defendingUnitId: defendingUnitId,
                        lock: true
                    }, this, function (result) {
                        // Handle successful attack
                        this.clearSelection();
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
            /* Example:
            
            onMyMethodToCall1: function( evt )
            {
                console.log( 'onMyMethodToCall1' );
                
                // Preventing default browser reaction
                dojo.stopEvent( evt );
    
                // Check that this action is possible (see "possibleactions" in states.inc.php)
                if( ! this.checkAction( 'myAction' ) )
                {   return; }
    
                this.ajaxcall( "/fortify/fortify/myAction.html", { 
                                                                        lock: true, 
                                                                        myArgument1: arg1, 
                                                                        myArgument2: arg2,
                                                                        ...
                                                                     }, 
                             this, function( result ) {
                                
                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)
                                
                             }, function( is_error) {
    
                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)
    
                             } );        
            },        
            
            */


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
            },
            notif_actionsRemaining: function (notif) {
                this.updateActionCounter(notif.args.actionsRemaining);
            },
            notif_unitEnlisted: function (notif) {

                console.log('Notification received: unitEnlisted', notif);

                // Create or move the unit on the board
                var unitId = notif.args.unitId;
                var unitType = notif.args.unit_type;
                var x = notif.args.x;
                var y = notif.args.y;
                var playerColor = notif.args.player_color;

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
                    unitDiv.style.margin = "2px 0 0 7px";
                }

                // If this is not the active player's move, remove the unit from their deck
                if (notif.args.player_id != this.player_id) {
                    var deckElement = $('player_deck_' + (playerColor == 'red' ? 'bottom' : 'top'));
                    var unitInDeck = deckElement.querySelector('.' + unitType + '.' + playerColor);
                    if (unitInDeck) {
                        dojo.destroy(unitInDeck);
                    }
                }
                //this.resetGameState();
            },

            notif_unitMoved: function (notif) {

                // Move the unit on the client side
                var unit = $(notif.args.unit_Id);
                var toSlot = $('board_slot_' + notif.args.toX + '_' + notif.args.toY);
                if (unit && toSlot) {
                    toSlot.appendChild(unit);
                }

                this.resetGameState();
            },

            notif_unitsFortified: function (notif) {
                var unitElement = $(notif.args.unit.unit_id);
                if (unitElement) {
                    this.updateToFortifiedUnit(unitElement, notif.args.unit.type, this.gamedatas.players[notif.args.player_id].color);
                }

                this.showMessage(_("${player_name} fortified a ${unit_type}").replace('${player_name}', notif.args.player_name).replace('${unit_type}', notif.args.unit_type));
            },

            notif_unitAttacked: function (notif) {
                debugger;
                // Remove the defending unit from the board
                //dojo.destroy(notif.args.defending_unit_id);

                // Update the reinforcement track
                this.updateReinforcementTrack(notif.args.reinforcementTrack);
                this.clearHighlights();
                this.showMessage(_("${player_name} attacked ${defending_unit_type} with ${attacking_unit_type}")
                    .replace('${player_name}', notif.args.player_name)
                    .replace('${defending_unit_type}', notif.args.defending_unit_type)
                    .replace('${attacking_unit_type}', notif.args.attacking_unit_type));

                this.exitAttackMode();
            },

            notif_unitReturnedToSupply: function (notif) {
                debugger;
                this.showMessage(_("A ${unit_type} has returned to ${player_name}'s supply")
                    .replace('${unit_type}', notif.args.unit_type)
                    .replace('${player_name}', notif.args.player_name));

                // Update the player's supply in the UI
                this.updatePlayerSupply(unit);
            },

            createUnitDiv: function (unitId, unitType, playerColor) {
                var unitDiv = dojo.create('div', {
                    id: unitId,
                    class: 'unit ' + unitType + ' ' + playerColor
                });
                return unitDiv;
            }
        });
    });
