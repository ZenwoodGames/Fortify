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
                this.removeSlotHighlight();

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
                            const landSlot = document.querySelectorAll('.land');
                            landSlot.forEach(slot => {
                                slot.classList.add('highlighted');
                            });
                            this.selectedUnit = event.target.id;
                            break;
                    }
                    //if (gameState !== 'enlist') return;

                    // Deselect previously selected token
                    this.selectedUnit = document.querySelectorAll('.selected');
                    if (this.selectedUnit && this.selectedUnit.length > 0) {
                        this.selectedUnit.forEach(sUnit => {
                            sUnit.classList.remove('selected');
                        });
                    }
                    const highlightesShoreSpaces = document.querySelectorAll('.highlighted');
                    if (this.highlightesShoreSpaces && this.highlightesShoreSpaces.length > 0) {
                        this.highlightesShoreSpaces.forEach(highlightesShoreSpace => {
                            highlightesShoreSpace.classList.remove('highlighted');
                        });
                    }
                    // Select the clicked Unit
                    this.selectedUnit = event.target;
                    this.selectedUnit.classList.add('selected');

                    // Highlight shore spaces
                    const shoreSpaces = document.querySelectorAll('.shore');
                    shoreSpaces.forEach(space => {
                        space.classList.add('highlighted');
                    });
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

                // Remove existing highlight
                this.removeButtonHighlight();

                let btnEnlist = document.getElementById("btnEnlist");
                btnEnlist.classList.add("btn-active");
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
            isSlotOccupied: function (slot) {

                if (slot.classList.contains("unit"))
                    return true;
                else
                    return false;
            },
            fortify: function (event) {

                this.removeButtonHighlight();

                event.currentTarget.classList.add("btn-active");
            },
            move: function (event) {

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
