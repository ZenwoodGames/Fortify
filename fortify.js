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
                dojo.connect($('btnMove'), 'onclick', this, 'startMoveAction');

                this.addEventListenserForActionButtons(gamedatas.gamestate.possibleactions);

                for (var i in gamedatas.units) {
                    var unit = gamedatas.units[i];
                    this.placeUnitOnBoard(unit.unit_id, unit.type, unit.x, unit.y, unit.player_id);
                }

                console.log("Ending game setup");
            },

            placeUnitOnBoard: function (unitId, unitType, x, y, playerId) {
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
            },

            handleSlotClick: function (event) {
                debugger;


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

                            this.selectedUnit.classList.remove("selected");
                            this.selectedUnit.style = "margin: 2px 0 0 7px;"
                            event.target.appendChild(this.selectedUnit);
                        }
                        else {
                            // Remove unit selection
                            //this.removeUnitHighlight();
                            //this.removeSlotHighlight();
                        }
                        break;
                }
                this.removeSlotHighlight();
            },
            removeUnitHighlight: function () {
                this.selectedUnit = document.querySelectorAll('.selected');
                if (this.selectedUnit && this.selectedUnit.length > 0) {
                    this.selectedUnit.forEach(sUnit => {
                        sUnit.classList.remove('selected');
                    });
                }
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

                // Check if the player is selecting the correct color
                if (currentPlayerColor !== unitColor) {
                    this.showMessage(_("You can only select tokens of your own color"), "error");
                    return;
                }

                this.removeSlotHighlight();
                if (this.isCurrentPlayerActive()) {
                    switch (event.target.classList[1]) {
                        case 'battleship':
                            const waterSlot = document.querySelectorAll('.water');
                            waterSlot.forEach(slot => {
                                slot.classList.add('highlighted');
                            });
                            this.selectedUnit = event.target.id;
                            break;
                        case 'infantry':
                        case 'tank':
                            if (this.gamestate === 'moving') {
                                this.highlightValidMoves();
                            }
                            else {
                                const landSlot = document.querySelectorAll('.land');
                                landSlot.forEach(slot => {
                                    slot.classList.add('highlighted');
                                });
                                this.selectedUnit = event.target.id;
                            }
                            break;
                    }
                    //if (gameState !== 'enlist') return;

                    // Deselect previously selected token


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
                    // Select the clicked Unit
                    this.selectedUnit = event.target;
                    this.selectedUnit.classList.add('selected');

                    // // Highlight shore spaces
                    // const shoreSpaces = document.querySelectorAll('.shore');
                    // shoreSpaces.forEach(space => {
                    //     space.classList.add('highlighted');
                    // });
                }
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
                debugger;
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
                    // case 'playerFirstEnlist':
                    //     this.enlist();
                    //     break;
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
                        /*               
                                         Example:
                         
                                         case 'myGameState':
                                            
                                            // Add 3 action buttons in the action status bar:
                                            
                                            this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                                            this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                                            this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                                            break;
                        */
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
                debugger;
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

                        // What to do after the server call if it succeeded
                        // (most of the time: nothing)
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
            fortify: function (event) {

                this.removeButtonHighlight();

                event.currentTarget.classList.add("btn-active");
            },
            startMoveAction: function (event) {
                debugger;
                // Remove existing highlights
                //this.removeAllHighlights();

                // Highlight valid move locations
                this.highlightValidMoves();

                // Set the game state to 'moving'
                this.gameState = 'moving';

                // Update UI to show that we're in 'move' mode
                this.updateActionButtons('move');
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

                var x = parseInt(selectedUnit.parentNode.dataset.x);
                var y = parseInt(selectedUnit.parentNode.dataset.y);

                // Highlight orthogonal adjacent empty spaces
                var orthogonalDirections = [
                    { dx: 0, dy: -1 }, // up
                    { dx: 0, dy: 1 },  // down
                    { dx: -1, dy: 0 }, // left
                    { dx: 1, dy: 0 }   // right
                ];

                orthogonalDirections.forEach(dir => {
                    debugger;
                    var newX = x + dir.dx;
                    var newY = y + dir.dy;
                    var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]`);
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
                        var slot = document.querySelector(`.board-slot[data-x="${newX}"][data-y="${newY}"]`);
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
                        // What to do after the server call if it succeeded
                        // (most of the time: nothing)
                    }
                    , function (is_error) {
                        console.log(is_error);
                        // What to do after the server call in any case
                    });
                }
            },

            attack: function (event) {

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
            },
            notif_actionsRemaining: function (notif) {
                this.updateActionCounter(notif.args.actionsRemaining);
            },
            notif_unitEnlisted: function (notif) {
                debugger;
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
                debugger;
                // Move the unit on the client side
                var unit = $(notif.args.unit_Id);
                var toSlot = $('board_slot_' + notif.args.toX + '_' + notif.args.toY);
                if (unit && toSlot) {
                    toSlot.appendChild(unit);
                }

                this.resetGameState();
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
