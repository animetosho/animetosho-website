-- create triggers in toto_repl database to update latest anime/episode indexes
-- Note: MySQL ignores NULL fields for aggregate functions, so we don't put 'idx_dateline IS NOT NULL' clauses (which makes the query slower too)

DELIMITER ||

CREATE TRIGGER IF NOT EXISTS trig_ep_insert
AFTER INSERT ON toto_toto
FOR EACH ROW BEGIN
  DECLARE aniep_dateline BIGINT;
  DECLARE anidb_aid INT;
  IF NEW.eid != 0 THEN
    REPLACE INTO anito.toto_ep_latest VALUES(NEW.eid, -(SELECT MAX(idx_dateline) FROM toto_toto WHERE eid=NEW.eid));
    SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto WHERE eid=NEW.eid);
    SET anidb_aid = (SELECT aid FROM anidb.ep WHERE id=NEW.eid);
    IF anidb_aid IS NOT NULL THEN
      REPLACE INTO anito.toto_aniep_latest VALUES(NEW.eid, anidb_aid, NEW.eid, -aniep_dateline);
    END IF;
  ELSE
    IF NEW.aid = 0 THEN
      SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=0);
    ELSE
      SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto USE INDEX(aid_filter) WHERE aid=NEW.aid AND eid=0);
    END IF;
    REPLACE INTO anito.toto_aniep_latest VALUES(-NEW.aid, NEW.aid, 0, -aniep_dateline);
  END IF;
  REPLACE INTO anito.toto_anime_latest VALUES(NEW.aid, -(SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=NEW.aid));
END ||

CREATE TRIGGER IF NOT EXISTS trig_ep_update
AFTER UPDATE ON toto_toto
FOR EACH ROW BEGIN
  DECLARE aniep_dateline BIGINT;
  DECLARE anidb_aid INT;
  IF OLD.eid != 0 THEN
    REPLACE INTO anito.toto_ep_latest VALUES(OLD.eid, -(SELECT MAX(idx_dateline) FROM toto_toto WHERE eid=OLD.eid));
    SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto WHERE eid=OLD.eid);
    IF aniep_dateline IS NULL THEN
      DELETE FROM anito.toto_aniep_latest WHERE aeid=OLD.eid;
    ELSE
      SET anidb_aid = (SELECT aid FROM anidb.ep WHERE id=OLD.eid);
      IF anidb_aid IS NOT NULL THEN
        REPLACE INTO anito.toto_aniep_latest VALUES(OLD.eid, anidb_aid, OLD.eid, -aniep_dateline);
      END IF;
    END IF;
  ELSE
    IF OLD.aid = 0 THEN
      SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=0);
    ELSE
      SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto USE INDEX(aid_filter) WHERE aid=OLD.aid AND eid=0);
    END IF;
    IF aniep_dateline IS NULL THEN
      DELETE FROM anito.toto_aniep_latest WHERE aeid=-OLD.aid;
    ELSE
      REPLACE INTO anito.toto_aniep_latest VALUES(-OLD.aid, OLD.aid, 0, -aniep_dateline);
    END IF;
  END IF;
  IF NEW.eid != OLD.eid AND NEW.eid != 0 THEN REPLACE INTO anito.toto_ep_latest VALUES(NEW.eid, -(SELECT MAX(idx_dateline) FROM toto_toto WHERE eid=NEW.eid));
  END IF;
  REPLACE INTO anito.toto_anime_latest VALUES(OLD.aid, -(SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=OLD.aid));
  IF NEW.aid != OLD.aid THEN REPLACE INTO anito.toto_anime_latest VALUES(NEW.aid, -(SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=NEW.aid));
  END IF;
  IF NEW.aid != OLD.aid OR NEW.eid != OLD.eid THEN
    IF NEW.eid != 0 THEN
      SET anidb_aid = (SELECT aid FROM anidb.ep WHERE id=NEW.eid);
      IF anidb_aid IS NOT NULL THEN
        REPLACE INTO anito.toto_aniep_latest VALUES(NEW.eid, anidb_aid, NEW.eid, -(SELECT MIN(idx_dateline) FROM toto_toto WHERE eid=NEW.eid));
      END IF;
    ELSEIF NEW.aid = 0 THEN
      REPLACE INTO anito.toto_aniep_latest VALUES(0, 0, 0, -(SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=0));
    ELSE
      REPLACE INTO anito.toto_aniep_latest VALUES(-NEW.aid, NEW.aid, 0, -(SELECT MIN(idx_dateline) FROM toto_toto USE INDEX(aid_filter) WHERE aid=NEW.aid AND eid=0));
    END IF;
  END IF;
END ||

CREATE TRIGGER IF NOT EXISTS trig_ep_delete
AFTER DELETE ON toto_toto
FOR EACH ROW BEGIN
  DECLARE aniep_dateline BIGINT;
  DECLARE anidb_aid INT;
  IF OLD.eid != 0 THEN
    REPLACE INTO anito.toto_ep_latest VALUES(OLD.eid, -(SELECT MAX(idx_dateline) FROM toto_toto WHERE eid=OLD.eid));
    SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto WHERE eid=OLD.eid);
    IF aniep_dateline IS NULL THEN
      DELETE FROM anito.toto_aniep_latest WHERE aeid=OLD.eid;
    ELSE
      SET anidb_aid = (SELECT aid FROM anidb.ep WHERE id=OLD.eid);
      IF anidb_aid IS NOT NULL THEN
        REPLACE INTO anito.toto_aniep_latest VALUES(OLD.eid, anidb_aid, OLD.eid, -aniep_dateline);
      END IF;
    END IF;
  ELSE
    IF OLD.aid = 0 THEN
      SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=0);
    ELSE
      SET aniep_dateline = (SELECT MIN(idx_dateline) FROM toto_toto USE INDEX(aid_filter) WHERE aid=OLD.aid AND eid=0);
    END IF;
    IF aniep_dateline IS NULL THEN
      DELETE FROM anito.toto_aniep_latest WHERE aeid=-OLD.aid;
    ELSE
      REPLACE INTO anito.toto_aniep_latest VALUES(-OLD.aid, OLD.aid, 0, -aniep_dateline);
    END IF;
  END IF;
  REPLACE INTO anito.toto_anime_latest VALUES(OLD.aid, -(SELECT MIN(idx_dateline) FROM toto_toto WHERE aid=OLD.aid));
END ||

DELIMITER ;


-- for Sphinx to work, track toto_toto updates
ALTER TABLE `toto_toto`
ADD COLUMN IF NOT EXISTS `_updated`  timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
ADD INDEX IF NOT EXISTS `sphinx_delta` (`_updated`);

