CREATE TABLE IF NOT EXISTS `toto_toto_meta` (
  `id` INT(10) UNSIGNED NOT NULL,
  `comments` int(11) unsigned not null default 0,
  `comments_top` int(11) unsigned not null default 0,
  `comments_locked` tinyint(3) not null default 0,
  PRIMARY KEY(`id`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_users` (
  `uid` INT(10) UNSIGNED NOT NULL auto_increment,
  `username` VARCHAR(40) NOT NULL,
  `pwd` BINARY(40) NOT NULL,
  `token` BINARY(16) NOT NULL,
  `email` VARCHAR(220) NOT NULL DEFAULT '',
  `emailverified` TINYINT(3) NOT NULL DEFAULT 0,
  `numcomments` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `accesslevel` TINYINT(3) NOT NULL DEFAULT 10,
  `regdate` BIGINT(30) NOT NULL DEFAULT 0,
  `lastvisit` BIGINT(30) NOT NULL DEFAULT 0,
  `avatar` BLOB DEFAULT NULL,
  `tagline` VARCHAR(500) NOT NULL DEFAULT "",
  `datefmt` VARCHAR(10) NOT NULL DEFAULT "d/m/Y",
  `timefmt` VARCHAR(10) NOT NULL DEFAULT "H:i:s",
  `timezone_offset` FLOAT(3) NULL NULL DEFAULT 0,  -- since there's .5 of hours, we divide this num by 2
  `timezone_dst` TINYINT(3) NOT NULL DEFAULT 0,
  
  `disp_blacklist` TEXT NOT NULL,
  
  PRIMARY KEY (`uid`),
  UNIQUE KEY (`username`),
  KEY (`email`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_users_verify_email` (
  `uid` INT(10) UNSIGNED NOT NULL,
  `email` VARCHAR(220) NOT NULL,
  `hash` BINARY(16) NOT NULL,
  `dateline` BIGINT(30) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`,`hash`),
  KEY (`dateline`),
  KEY (`uid`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_users_verify_pwdreset` (
  `uid` INT(10) UNSIGNED NOT NULL,
  `hash` BINARY(16) NOT NULL,
  `dateline` BIGINT(30) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`,`hash`),
  KEY (`dateline`),
  KEY (`uid`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_sessions` (
  `sid` CHAR(32) NOT NULL,
  `uid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `time` BIGINT(30) UNSIGNED NOT NULL DEFAULT 0,
  `action` VARCHAR(30) NOT NULL DEFAULT "",
  `subaction` VARCHAR(120) NOT NULL DEFAULT "",
  `uri` VARCHAR(150) NOT NULL DEFAULT "",
  PRIMARY KEY (`sid`, `uid`),
  KEY (`time`)
) ENGINE=Memory;

CREATE TABLE IF NOT EXISTS `toto_captcha` (
  `hash` BINARY(16) NOT NULL,
  `answerkey` INT(11) NOT NULL,
  `dateline` BIGINT(30) UNSIGNED NOT NULL,
  
  PRIMARY KEY (`hash`),
  KEY (`dateline`)
) ENGINE=Memory; -- if too big, change to Aria

CREATE TABLE IF NOT EXISTS `toto_parsecache` (
  `title` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `dateline` bigint(30) NOT NULL DEFAULT 0,
  PRIMARY KEY (`title`),
  KEY (`dateline`)
) ENGINE=Aria;



CREATE TABLE IF NOT EXISTS `toto_comments` (
  `cid` INT(10) UNSIGNED NOT NULL auto_increment,
  `uid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(40) NOT NULL DEFAULT "",
  
  `mid` INT(10) UNSIGNED NOT NULL,
  `message_type` SMALLINT(5) NOT NULL DEFAULT 0, -- comment type
  `message` TEXT NOT NULL,
  `replyto` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- the cid of the comment this comment is a reply to (ie the parent)
  `replydepth` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0, -- how many parents there are
  `replytotop` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- the topmost parent
  `numreplies` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- number of children
  `repliesdirect` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- number of direct children
  `parentlist` VARBINARY(100) NOT NULL DEFAULT "", -- packed 32-bit integers representing cids of this comment's parents (implies max 25 (50???) depth)
  `dateline` BIGINT(30) UNSIGNED NOT NULL,
  `edit_dateline` BIGINT(30) UNSIGNED NOT NULL DEFAULT 0,
  `edit_lock` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `rating` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`cid`),
  KEY (`mid`),
  KEY (`replytotop`,`replydepth`),
  KEY (`replyto`),
  KEY `type` (`message_type`, `dateline`),
  KEY `dateline` (`dateline`),
  KEY `mid_dateline` (`mid`, `dateline`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_comment_votes` (
  `cid` INT(10) UNSIGNED NOT NULL,
  `uid` INT(10) UNSIGNED NOT NULL,
  `dateline` BIGINT(30) UNSIGNED NOT NULL,
  `vote` TINYINT(3) NOT NULL DEFAULT 0,
  `idx_mid` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`cid`,`uid`),
  KEY (`idx_mid`,`uid`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_feedback_comments` (
  `cid` INT(10) UNSIGNED NOT NULL auto_increment,
  `uid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(40) NOT NULL DEFAULT "",
  
  `message` TEXT NOT NULL,
  `replyto` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- the cid of the comment this comment is a reply to (ie the parent)
  `replydepth` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0, -- how many parents there are
  `replytotop` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- the topmost parent
  `numreplies` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- number of children
  `repliesdirect` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- number of direct children
  `parentlist` VARBINARY(100) NOT NULL DEFAULT "", -- packed 32-bit integers representing cids of this comment's parents (implies max 25 (50???) depth)
  `dateline` BIGINT(30) UNSIGNED NOT NULL,
  `edit_dateline` BIGINT(30) UNSIGNED NOT NULL DEFAULT 0,
  `edit_lock` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`cid`),
  KEY (`replydepth`),
  KEY (`replydepth`,`replytotop`),
  KEY (`replyto`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_modtalk_comments` (
  `cid` INT(10) UNSIGNED NOT NULL auto_increment,
  `uid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(40) NOT NULL DEFAULT "",
  
  `message` TEXT NOT NULL,
  `replyto` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- the cid of the comment this comment is a reply to (ie the parent)
  `replydepth` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0, -- how many parents there are
  `replytotop` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- the topmost parent
  `numreplies` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- number of children
  `repliesdirect` INT(10) UNSIGNED NOT NULL DEFAULT 0, -- number of direct children
  `parentlist` VARBINARY(100) NOT NULL DEFAULT "", -- packed 32-bit integers representing cids of this comment's parents (implies max 25 (50???) depth)
  `dateline` BIGINT(30) UNSIGNED NOT NULL,
  `edit_dateline` BIGINT(30) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`cid`),
  KEY (`replydepth`),
  KEY (`replydepth`,`replytotop`),
  KEY (`replyto`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_activity_log` (
  `action` ENUM("view","feedback","modtalk","register","contact") NOT NULL,
  `id` INT(10) UNSIGNED NOT NULL,
  `ip` VARCHAR(40) NOT NULL DEFAULT "",
  `uagent` VARCHAR(300) NOT NULL DEFAULT "",
  `dateline` BIGINT(30) NOT NULL DEFAULT 0,
  PRIMARY KEY (`action`,`id`),
  KEY (`dateline`)
) ENGINE=Aria;


--  index of all comments, to accelerate the 'View Comments' page
CREATE TABLE IF NOT EXISTS `toto_comments_all` (
  `section` CHAR(10) NOT NULL,
  `cid` INT(10) UNSIGNED NOT NULL,
  `message_type` SMALLINT(5) NOT NULL, -- -1=feedback, -2=modtalk
  `dateline` BIGINT(30) UNSIGNED NOT NULL,
  `top_dateline` BIGINT(30) UNSIGNED NOT NULL, -- dateline of the topmost parent
  PRIMARY KEY (`section`,`cid`),
  KEY (`dateline`),
  KEY (`message_type`,`dateline`)
) ENGINE=Aria;

CREATE TRIGGER IF NOT EXISTS trig_comments_all_comments
AFTER INSERT ON toto_comments
FOR EACH ROW
INSERT INTO toto_comments_all SET section="", cid=NEW.cid, message_type=NEW.message_type, dateline=NEW.dateline, top_dateline=IF(NEW.replyto=0, NEW.dateline, (SELECT dateline FROM toto_comments WHERE NEW.replytotop=cid));

-- this isn't exactly correct, but works for now
CREATE TRIGGER IF NOT EXISTS trig_comments_all_comments_upd
AFTER UPDATE ON toto_comments
FOR EACH ROW
UPDATE toto_comments_all SET message_type=NEW.message_type WHERE cid=NEW.cid AND section="";

CREATE TRIGGER IF NOT EXISTS trig_comments_all_comments_del
BEFORE DELETE ON toto_comments
FOR EACH ROW
DELETE FROM toto_comments_all WHERE section="" AND cid=OLD.cid;

CREATE TRIGGER IF NOT EXISTS trig_comments_all_feedback
AFTER INSERT ON toto_feedback_comments
FOR EACH ROW
INSERT INTO toto_comments_all SET section="feedback", cid=NEW.cid, message_type=-1, dateline=NEW.dateline, top_dateline=IF(NEW.replyto=0, NEW.dateline, (SELECT dateline FROM toto_feedback_comments WHERE NEW.replytotop=cid));

CREATE TRIGGER IF NOT EXISTS trig_comments_all_feedback_del
BEFORE DELETE ON toto_feedback_comments
FOR EACH ROW
DELETE FROM toto_comments_all WHERE section="feedback" AND cid=OLD.cid;

CREATE TRIGGER IF NOT EXISTS trig_comments_all_modtalk
AFTER INSERT ON toto_modtalk_comments
FOR EACH ROW
INSERT INTO toto_comments_all SET section="modtalk", cid=NEW.cid, message_type=-2, dateline=NEW.dateline, top_dateline=IF(NEW.replyto=0, NEW.dateline, (SELECT dateline FROM toto_modtalk_comments WHERE NEW.replytotop=cid));

CREATE TRIGGER IF NOT EXISTS trig_comments_all_modtalk_del
BEFORE DELETE ON toto_modtalk_comments
FOR EACH ROW
DELETE FROM toto_comments_all WHERE section="modtalk" AND cid=OLD.cid;

-- episodes/anime index
CREATE TABLE IF NOT EXISTS `toto_ep_latest` (
  `eid` int(11) unsigned not null,
  `dateline` bigint(30) NULL,
  primary key (`eid`),
  key (`dateline`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_anime_latest` (
  `aid` int(11) unsigned not null,
  `dateline` bigint(30) NULL,
  primary key (`aid`),
  key (`dateline`)
) ENGINE=Aria;

CREATE TABLE IF NOT EXISTS `toto_aniep_latest` (
  `aeid` int(11) not null, -- negative = aid, positive = eid
  `aid` int(11) unsigned not null,
  `eid` int(11) unsigned not null,
  `dateline` bigint(30) NULL, -- practically should never be NULL
  primary key (`aeid`),
  key (`aid`),
  key (`dateline`)
) ENGINE=Aria;

