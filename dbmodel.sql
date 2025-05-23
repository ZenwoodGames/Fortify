-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Fortify implementation : © Nirmatt Gopal nrmtgpl@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
-- dbmodel.sql
-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here
-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.
-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):
-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';
CREATE TABLE IF NOT EXISTS `units` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM(
        'infantry',
        'tank',
        'battleship',
        'chopper',
        'artillery'
    ) NOT NULL,
    `player_id` INT NOT NULL,
    `x` INT NOT NULL,
    `y` INT NOT NULL,
    `unit_id` VARCHAR(24) NOT NULL,
    `is_fortified` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_occupied` INT NOT NULL DEFAULT 0,
    `is_stacked` INT NOT NULL DEFAULT 0,
    `in_formation` BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS `reinforcement_track` (
    `unit_id` VARCHAR(24) NOT NULL,
    `position` INT NOT NULL,
    `is_fortified` BOOLEAN NOT NULL DEFAULT FALSE,
    `player_id` INT NOT NULL,
    `type` ENUM(
        'infantry',
        'tank',
        'battleship',
        'chopper',
        'artillery'
    ) NOT NULL,
    PRIMARY KEY (`unit_id`)
);

CREATE TABLE IF NOT EXISTS `game_progress` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `volley_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `player_volley_wins` (
    `player_id` INT(10) UNSIGNED NOT NULL,
    `wins` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`player_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

ALTER TABLE
    `player`
ADD
    `infantry_enlist_count` INT UNSIGNED NOT NULL DEFAULT '0';